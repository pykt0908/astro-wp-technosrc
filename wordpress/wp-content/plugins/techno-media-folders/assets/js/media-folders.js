/**
 * Techno Media Folders - JavaScript
 * Modern tree sidebar, instant filtering, HTML5 drag & drop, and seamless WP integration
 */

(function($) {
    'use strict';

    if (typeof technoMFData === 'undefined') {
        return;
    }

    var i18n = technoMFData.i18n;
    var ajaxUrl = technoMFData.ajaxUrl;
    var nonce = technoMFData.nonce;

    var App = {
        currentFolder: 'all',
        foldersTree: [],
        totalCount: 0,
        uncategorizedCount: 0,
        collapsed: localStorage.getItem('techno_mf_collapsed') === 'true',

        init: function() {
            var self = this;
            $(document).ready(function() {
                self.setupContext();
                self.fetchTree(function() {
                    self.initSidebar();
                });
            });
        },

        setupContext: function() {
            var self = this;

            // In List view
            if ($('body').hasClass('upload-php') && $('.tablenav').length && !$('.media-frame').length) {
                var urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('mode') === 'list' || !urlParams.has('mode')) {
                    self.initListViewIntegration();
                }
            }

            // In Grid view or WP Media Modal
            if (typeof wp !== 'undefined' && wp.media) {
                if (wp.media.view && wp.media.view.MediaFrame) {
                    if (wp.media.view.MediaFrame.Manage) {
                        var origManage = wp.media.view.MediaFrame.Manage.prototype.render;
                        wp.media.view.MediaFrame.Manage.prototype.render = function() {
                            var res = origManage.apply(this, arguments);
                            var frame = this;
                            setTimeout(function() {
                                self.initGridSidebar(frame.$el);
                            }, 50);
                            return res;
                        };
                    }
                    if (wp.media.view.MediaFrame.Select) {
                        var origSelect = wp.media.view.MediaFrame.Select.prototype.render;
                        wp.media.view.MediaFrame.Select.prototype.render = function() {
                            var res = origSelect.apply(this, arguments);
                            var frame = this;
                            setTimeout(function() {
                                self.initGridSidebar(frame.$el);
                            }, 50);
                            return res;
                        };
                    }
                    if (wp.media.view.MediaFrame.Post) {
                        var origPost = wp.media.view.MediaFrame.Post.prototype.render;
                        wp.media.view.MediaFrame.Post.prototype.render = function() {
                            var res = origPost.apply(this, arguments);
                            var frame = this;
                            setTimeout(function() {
                                self.initGridSidebar(frame.$el);
                            }, 50);
                            return res;
                        };
                    }
                }
            }

            // MutationObserver to guarantee detection of .media-frame anytime it mounts
            if (window.MutationObserver) {
                var observer = new MutationObserver(function() {
                    var $frames = $('.media-frame');
                    $frames.each(function() {
                        var $f = $(this);
                        if (!$f.find('.tmf-sidebar').length) {
                            self.initGridSidebar($f);
                        }
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }

            // Polling backup for first 3 seconds
            var checkCount = 0;
            var checkTimer = setInterval(function() {
                checkCount++;
                var $frames = $('.media-frame');
                if ($frames.length && !$frames.first().find('.tmf-sidebar').length) {
                    self.initGridSidebar($frames.first());
                }
                if (checkCount > 15) clearInterval(checkTimer);
            }, 200);

            // Close context menu on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.tmf-context-menu, .tmf-actions-trigger').length) {
                    $('.tmf-context-menu').hide();
                }
            });
        },

        hookMediaFrame: function(frame) {
            var self = this;
            setTimeout(function() {
                if (frame.$el && frame.$el.length) {
                    self.initGridSidebar(frame.$el);
                }
            }, 100);
        },

        fetchTree: function(callback) {
            var self = this;
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techno_mf_get_tree',
                    nonce: nonce
                },
                success: function(res) {
                    if (res.success) {
                        self.foldersTree = res.data.tree || [];
                        self.totalCount = res.data.total_count || 0;
                        self.uncategorizedCount = res.data.uncategorized_count || 0;
                        if (callback) callback();
                        self.renderTree();
                    }
                }
            });
        },

        initSidebar: function() {
            var self = this;

            // List View
            if ($('.techno-mf-list-layout').length) {
                self.renderTree();
            }

            // Grid View
            if ($('.media-frame').length) {
                self.initGridSidebar($('.media-frame').first());
            }
        },

        initListViewIntegration: function() {
            var self = this;
            var $form = $('#posts-filter');
            if (!$form.length || $('.techno-mf-list-layout').length) return;

            // Read URL param
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('techno_folder')) {
                self.currentFolder = urlParams.get('techno_folder');
            }

            var $wrapper = $('<div class="techno-mf-list-layout"></div>');
            var $sidebar = self.buildSidebarHtml();
            $form.before($wrapper);
            $wrapper.append($sidebar);

            var $content = $('<div class="tmf-list-content"></div>');
            $content.append($form);
            $wrapper.append($content);

            self.bindSidebarEvents($wrapper);
            self.renderTree();
        },

        initGridSidebar: function($frame) {
            var self = this;
            if (!$frame || !$frame.length || $frame.find('.tmf-sidebar').length) return;

            $frame.addClass('techno-mf-has-sidebar');
            if (self.collapsed) {
                $frame.addClass('tmf-sidebar-collapsed');
            }

            var $sidebar = self.buildSidebarHtml();
            $frame.prepend($sidebar);

            self.bindSidebarEvents($frame);
            self.renderTree();
            self.setupDraggableAttachments($frame);
        },

        buildSidebarHtml: function() {
            var self = this;
            var $sidebar = $('<div class="tmf-sidebar"></div>');

            // Toggle button
            $sidebar.append(
                '<button type="button" class="tmf-sidebar-toggle" title="ย่อ/ขยายโฟลเดอร์">' +
                '<span class="dashicons ' + (self.collapsed ? 'dashicons-arrow-right-alt2' : 'dashicons-arrow-left-alt2') + '"></span>' +
                '</button>'
            );

            // Header
            var $header = $('<div class="tmf-header"></div>');
            $header.append(
                '<h3 class="tmf-title"><span class="dashicons dashicons-portfolio"></span> ' + i18n.folders + '</h3>' +
                '<button type="button" class="button button-secondary tmf-btn-new-folder">' + i18n.newFolder + '</button>'
            );
            $sidebar.append($header);

            // Search
            var $search = $('<div class="tmf-search-box"></div>');
            $search.append(
                '<span class="dashicons dashicons-search"></span>' +
                '<input type="text" placeholder="' + i18n.searchFolders + '" class="tmf-search-input" />'
            );
            $sidebar.append($search);

            // Tree container
            var $tree = $('<div class="tmf-tree-container"></div>');

            // Fixed Root Items
            var $allFiles = $(
                '<div class="tmf-item tmf-special-item" data-folder-id="all">' +
                '  <div class="tmf-item-left">' +
                '    <span class="tmf-expander tmf-empty"></span>' +
                '    <span class="dashicons dashicons-format-gallery tmf-icon"></span>' +
                '    <span class="tmf-item-name">' + i18n.allFiles + '</span>' +
                '  </div>' +
                '  <div class="tmf-item-right">' +
                '    <span class="tmf-count tmf-count-total">' + self.totalCount + '</span>' +
                '  </div>' +
                '</div>'
            );

            var $uncategorized = $(
                '<div class="tmf-item tmf-special-item" data-folder-id="uncategorized">' +
                '  <div class="tmf-item-left">' +
                '    <span class="tmf-expander tmf-empty"></span>' +
                '    <span class="dashicons dashicons-category tmf-icon" style="color:#d63638;"></span>' +
                '    <span class="tmf-item-name">' + i18n.uncategorized + '</span>' +
                '  </div>' +
                '  <div class="tmf-item-right">' +
                '    <span class="tmf-count tmf-count-uncategorized">' + self.uncategorizedCount + '</span>' +
                '  </div>' +
                '</div>'
            );

            $tree.append($allFiles);
            $tree.append($uncategorized);
            $tree.append('<div class="tmf-separator"></div>');
            $tree.append('<div class="tmf-custom-tree"></div>');

            $sidebar.append($tree);

            // Context Menu Container (single instance)
            if (!$('#tmf-context-menu').length) {
                var $contextMenu = $(
                    '<div id="tmf-context-menu" class="tmf-context-menu">' +
                    '  <div class="tmf-context-menu-item tmf-action-subfolder"><span class="dashicons dashicons-plus-alt2"></span> ' + i18n.newSubfolder + '</div>' +
                    '  <div class="tmf-context-menu-item tmf-action-rename"><span class="dashicons dashicons-edit"></span> ' + i18n.rename + '</div>' +
                    '  <div class="tmf-context-menu-item tmf-danger tmf-action-delete"><span class="dashicons dashicons-trash"></span> ' + i18n.delete + '</div>' +
                    '</div>'
                );
                $('body').append($contextMenu);
            }

            // Toast element
            if (!$('#tmf-toast').length) {
                $('body').append('<div id="tmf-toast" class="tmf-toast"></div>');
            }

            return $sidebar;
        },

        renderTree: function() {
            var self = this;
            $('.tmf-count-total').text(self.totalCount);
            $('.tmf-count-uncategorized').text(self.uncategorizedCount);

            var $customTree = $('.tmf-custom-tree');
            $customTree.empty();

            if (!self.foldersTree || !self.foldersTree.length) {
                $customTree.append('<div style="padding:12px;text-align:center;color:#8c8f94;font-size:12px;">ยังไม่มีโฟลเดอร์<br>กด "+ โฟลเดอร์ใหม่" เพื่อสร้าง</div>');
            } else {
                self.renderNodes(self.foldersTree, $customTree, 0);
            }

            // Set active class
            $('.tmf-item').removeClass('tmf-active');
            $('.tmf-item[data-folder-id="' + self.currentFolder + '"]').addClass('tmf-active');
        },

        renderNodes: function(nodes, $container, level) {
            var self = this;
            $.each(nodes, function(i, node) {
                var hasChildren = node.children && node.children.length > 0;
                var $item = $(
                    '<div class="tmf-item tmf-level-' + level + '" data-folder-id="' + node.id + '" data-folder-name="' + escapeHtml(node.name) + '">' +
                    '  <div class="tmf-item-left">' +
                    '    <span class="tmf-expander ' + (hasChildren ? '' : 'tmf-empty') + '">' +
                    '      <span class="dashicons dashicons-arrow-down-alt2"></span>' +
                    '    </span>' +
                    '    <span class="dashicons dashicons-category tmf-icon"></span>' +
                    '    <span class="tmf-item-name" title="' + escapeHtml(node.name) + '">' + escapeHtml(node.name) + '</span>' +
                    '  </div>' +
                    '  <div class="tmf-item-right">' +
                    '    <span class="tmf-count">' + node.count + '</span>' +
                    '    <span class="tmf-actions-trigger" title="เมนูจัดการ"><span class="dashicons dashicons-ellipsis"></span></span>' +
                    '  </div>' +
                    '</div>'
                );

                $container.append($item);

                if (hasChildren) {
                    var $childrenContainer = $('<div class="tmf-children-container"></div>');
                    $container.append($childrenContainer);
                    self.renderNodes(node.children, $childrenContainer, level + 1);
                }
            });
        },

        bindSidebarEvents: function($root) {
            var self = this;

            // Folder click -> Filter
            $root.on('click', '.tmf-item', function(e) {
                if ($(e.target).closest('.tmf-expander, .tmf-actions-trigger').length) return;
                var folderId = $(this).data('folder-id');
                self.selectFolder(folderId);
            });

            // Expander toggle
            $root.on('click', '.tmf-expander', function(e) {
                e.stopPropagation();
                var $expander = $(this);
                var $item = $expander.closest('.tmf-item');
                var $children = $item.next('.tmf-children-container');
                if ($children.length) {
                    $children.slideToggle(150);
                    $expander.find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-right-alt2');
                }
            });

            // New Folder button
            $root.on('click', '.tmf-btn-new-folder', function(e) {
                e.preventDefault();
                self.promptCreateFolder(0);
            });

            // 3-dots actions trigger
            $root.on('click', '.tmf-actions-trigger', function(e) {
                e.stopPropagation();
                var $item = $(this).closest('.tmf-item');
                var folderId = $item.data('folder-id');
                var folderName = $item.data('folder-name');
                self.showContextMenu($(this), folderId, folderName);
            });

            // Collapse Toggle
            $root.on('click', '.tmf-sidebar-toggle', function(e) {
                e.preventDefault();
                self.collapsed = !self.collapsed;
                localStorage.setItem('techno_mf_collapsed', self.collapsed);
                $('.techno-mf-has-sidebar').toggleClass('tmf-sidebar-collapsed', self.collapsed);
                $(this).find('.dashicons').toggleClass('dashicons-arrow-left-alt2 dashicons-arrow-right-alt2', !self.collapsed);
            });

            // Search input
            $root.on('input', '.tmf-search-input', function() {
                var query = $(this).val().toLowerCase().trim();
                $('.tmf-custom-tree .tmf-item').each(function() {
                    var name = $(this).data('folder-name') || '';
                    if (!query || name.toString().toLowerCase().indexOf(query) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // HTML5 Drag & Drop setup on folders
            $root.on('dragover dragenter', '.tmf-item', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('tmf-droppable-hover');
            });

            $root.on('dragleave dragend', '.tmf-item', function() {
                $(this).removeClass('tmf-droppable-hover');
            });

            $root.on('drop', '.tmf-item', function(e) {
                e.preventDefault();
                $(this).removeClass('tmf-droppable-hover');
                var targetFolderId = $(this).data('folder-id');

                var data = e.originalEvent.dataTransfer.getData('text/plain');
                if (!data) return;

                var itemIds = data.split(',');
                if (itemIds.length) {
                    self.moveAttachments(itemIds, targetFolderId);
                }
            });
        },

        selectFolder: function(folderId) {
            var self = this;
            self.currentFolder = folderId;

            $('.tmf-item').removeClass('tmf-active');
            $('.tmf-item[data-folder-id="' + folderId + '"]').addClass('tmf-active');

            // If List View -> Navigate
            if ($('body').hasClass('upload-php') && $('.tablenav').length && !$('.media-frame').length) {
                var url = new URL(window.location.href);
                if (folderId === 'all') {
                    url.searchParams.delete('techno_folder');
                } else {
                    url.searchParams.set('techno_folder', folderId);
                }
                window.location.href = url.toString();
                return;
            }

            // If Grid View -> Filter wp.media query
            if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
                var library = null;
                if (wp.media.frame.content && wp.media.frame.content.get && wp.media.frame.content.get()) {
                    var content = wp.media.frame.content.get();
                    library = content.collection || (content.options && content.options.selection);
                }
                if (!library && wp.media.frame.state) {
                    library = wp.media.frame.state().get('library');
                }

                if (library && library.props) {
                    library.props.set({ techno_folder: folderId });
                    library.props.set({ query: true }); // trigger refresh
                }
            }
        },

        setupDraggableAttachments: function($frame) {
            var self = this;

            // Make .attachment elements draggable using HTML5
            $(document).on('dragstart', '.attachment', function(e) {
                var $attachment = $(this);
                var id = $attachment.data('id') || ($attachment.attr('id') ? $attachment.attr('id').replace('post-', '') : '');
                if (!id) return;

                // Check if multiple attachments are selected
                var ids = [id];
                if ($attachment.hasClass('selected')) {
                    var selectedIds = [];
                    $('.attachment.selected').each(function() {
                        var selId = $(this).data('id') || ($(this).attr('id') ? $(this).attr('id').replace('post-', '') : '');
                        if (selId) selectedIds.push(selId);
                    });
                    if (selectedIds.length > 0) {
                        ids = selectedIds;
                    }
                }

                e.originalEvent.dataTransfer.setData('text/plain', ids.join(','));
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });
        },

        showContextMenu: function($trigger, folderId, folderName) {
            var self = this;
            var $menu = $('#tmf-context-menu');
            var offset = $trigger.offset();

            $menu.data('folder-id', folderId);
            $menu.data('folder-name', folderName);

            $menu.css({
                top: offset.top + 22,
                left: Math.max(10, offset.left - 110)
            }).fadeIn(100);

            $menu.off('click').on('click', '.tmf-context-menu-item', function(e) {
                e.stopPropagation();
                $menu.hide();

                if ($(this).hasClass('tmf-action-subfolder')) {
                    self.promptCreateFolder(folderId);
                } else if ($(this).hasClass('tmf-action-rename')) {
                    self.promptRenameFolder(folderId, folderName);
                } else if ($(this).hasClass('tmf-action-delete')) {
                    self.confirmDeleteFolder(folderId, folderName);
                }
            });
        },

        promptCreateFolder: function(parentId) {
            var self = this;
            var name = prompt(i18n.enterFolderName);
            if (!name || !name.trim()) return;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techno_mf_create_folder',
                    nonce: nonce,
                    name: name.trim(),
                    parent: parentId || 0
                },
                success: function(res) {
                    if (res.success) {
                        self.showToast('✓ สร้างโฟลเดอร์ "' + res.data.name + '" เรียบร้อยแล้ว', 'success');
                        self.fetchTree();
                    } else {
                        alert(res.data ? res.data.message : 'เกิดข้อผิดพลาด');
                    }
                }
            });
        },

        promptRenameFolder: function(folderId, currentName) {
            var self = this;
            var name = prompt(i18n.enterNewName, currentName);
            if (!name || !name.trim() || name.trim() === currentName) return;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techno_mf_rename_folder',
                    nonce: nonce,
                    id: folderId,
                    name: name.trim()
                },
                success: function(res) {
                    if (res.success) {
                        self.showToast('✓ เปลี่ยนชื่อโฟลเดอร์เรียบร้อยแล้ว', 'success');
                        self.fetchTree();
                    } else {
                        alert(res.data ? res.data.message : 'เกิดข้อผิดพลาด');
                    }
                }
            });
        },

        confirmDeleteFolder: function(folderId, folderName) {
            var self = this;
            if (!confirm(i18n.confirmDelete)) return;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techno_mf_delete_folder',
                    nonce: nonce,
                    id: folderId
                },
                success: function(res) {
                    if (res.success) {
                        self.showToast('✓ ลบโฟลเดอร์เรียบร้อยแล้ว', 'success');
                        if (self.currentFolder == folderId) {
                            self.selectFolder('all');
                        }
                        self.fetchTree();
                    } else {
                        alert(res.data ? res.data.message : 'เกิดข้อผิดพลาด');
                    }
                }
            });
        },

        moveAttachments: function(itemIds, targetFolderId) {
            var self = this;
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techno_mf_move_items',
                    nonce: nonce,
                    item_ids: itemIds,
                    target_folder: targetFolderId
                },
                success: function(res) {
                    if (res.success) {
                        self.showToast(i18n.moveSuccess + ' (' + res.data.moved_count + ' ไฟล์)', 'success');
                        self.fetchTree();

                        // If currently filtering by a specific folder and moved out of it, refresh view
                        if (self.currentFolder !== 'all') {
                            self.selectFolder(self.currentFolder);
                        }
                    } else {
                        alert(res.data ? res.data.message : 'เกิดข้อผิดพลาดในการย้ายไฟล์');
                    }
                }
            });
        },

        showToast: function(message, type) {
            var $toast = $('#tmf-toast');
            $toast.removeClass('tmf-toast-success tmf-toast-error');
            if (type === 'success') $toast.addClass('tmf-toast-success');
            if (type === 'error') $toast.addClass('tmf-toast-error');

            $toast.html(message).stop(true, true).fadeIn(200);
            setTimeout(function() {
                $toast.fadeOut(400);
            }, 3000);
        }
    };

    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    App.init();

})(jQuery);
