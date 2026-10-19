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
                    self.mountLayouts();
                });
            });
        },

        setupContext: function() {
            var self = this;

            // List View check
            if ($('body').hasClass('upload-php') && $('#posts-filter').length && !$('#wp-media-grid').length) {
                self.initListPageLayout();
            }

            // Grid View check on upload.php
            if ($('#wp-media-grid').length) {
                self.initGridPageLayout();
            }

            // Modal frames (e.g. Set Featured Image)
            if (typeof wp !== 'undefined' && wp.media) {
                if (wp.media.view && wp.media.view.MediaFrame) {
                    if (wp.media.view.MediaFrame.Select) {
                        var origSelect = wp.media.view.MediaFrame.Select.prototype.render;
                        wp.media.view.MediaFrame.Select.prototype.render = function() {
                            var res = origSelect.apply(this, arguments);
                            var frame = this;
                            setTimeout(function() {
                                if (frame.$el.closest('.media-modal').length) {
                                    self.initModalLayout(frame.$el);
                                }
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
                                if (frame.$el.closest('.media-modal').length) {
                                    self.initModalLayout(frame.$el);
                                }
                            }, 50);
                            return res;
                        };
                    }
                }
            }

            // MutationObserver to watch for Grid Frame or Modal Frame mounting
            if (window.MutationObserver) {
                var observer = new MutationObserver(function() {
                    if ($('#wp-media-grid').length && !$('.techno-mf-main-layout').length) {
                        self.initGridPageLayout();
                    }
                    $('.media-modal .media-frame').each(function() {
                        var $f = $(this);
                        if (!$f.find('.tmf-sidebar').length) {
                            self.initModalLayout($f);
                        }
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }

            // Close context menu on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.tmf-context-menu, .tmf-actions-trigger').length) {
                    $('.tmf-context-menu').hide();
                }
            });
        },

        mountLayouts: function() {
            var self = this;
            if ($('#wp-media-grid').length) {
                self.initGridPageLayout();
            }
            if ($('body').hasClass('upload-php') && $('#posts-filter').length && !$('#wp-media-grid').length) {
                self.initListPageLayout();
            }
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

        initListPageLayout: function() {
            var self = this;
            var $form = $('#posts-filter');
            if (!$form.length || $('.techno-mf-main-layout').length) return;

            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('techno_folder')) {
                self.currentFolder = urlParams.get('techno_folder');
            }

            var $layout = $('<div class="techno-mf-main-layout"></div>');
            if (self.collapsed) {
                $layout.addClass('tmf-sidebar-collapsed');
            }

            var $sidebar = self.buildSidebarHtml();
            var $content = $('<div class="techno-mf-content"></div>');

            $form.before($layout);
            $layout.append($sidebar);
            $layout.append($content);
            $content.append($form);

            self.bindSidebarEvents($layout);
            self.renderTree();
        },

        initGridPageLayout: function() {
            var self = this;
            var $grid = $('#wp-media-grid');
            if (!$grid.length || $grid.find('.techno-mf-main-layout').length) return;

            var $frame = $grid.find('.media-frame');
            if (!$frame.length) {
                // If frame is still initializing, retry in 50ms
                setTimeout(function() {
                    self.initGridPageLayout();
                }, 50);
                return;
            }

            var $layout = $('<div class="techno-mf-main-layout"></div>');
            if (self.collapsed) {
                $layout.addClass('tmf-sidebar-collapsed');
            }

            var $sidebar = self.buildSidebarHtml();
            var $content = $('<div class="techno-mf-content"></div>');

            $frame.before($layout);
            $layout.append($sidebar);
            $layout.append($content);
            $content.append($frame);

            self.bindSidebarEvents($layout);
            self.renderTree();
            self.setupDraggableAttachments($frame);
        },

        initModalLayout: function($modalFrame) {
            var self = this;
            if (!$modalFrame || !$modalFrame.length || $modalFrame.find('.tmf-sidebar').length) return;

            $modalFrame.addClass('techno-mf-has-modal-sidebar');
            if (self.collapsed) {
                $modalFrame.addClass('tmf-sidebar-collapsed');
            }

            var $sidebar = self.buildSidebarHtml();
            $modalFrame.prepend($sidebar);

            self.bindSidebarEvents($modalFrame);
            self.renderTree();
            self.setupDraggableAttachments($modalFrame);
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
                '<button type="button" class="button button-secondary tmf-btn-new-folder" id="tmf-btn-new-folder">' + i18n.newFolder + '</button>'
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
                $('.techno-mf-main-layout, .media-frame.techno-mf-has-modal-sidebar').toggleClass('tmf-sidebar-collapsed', self.collapsed);
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

            $root.on('dragleave', '.tmf-item', function(e) {
                var rect = this.getBoundingClientRect();
                var x = e.originalEvent.clientX;
                var y = e.originalEvent.clientY;
                if (x < rect.left || x >= rect.right || y < rect.top || y >= rect.bottom) {
                    $(this).removeClass('tmf-droppable-hover');
                }
            });

            $root.on('drop', '.tmf-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('tmf-droppable-hover');
                self.forceDismissUploader();

                var targetFolderId = $(this).data('folder-id');
                var data = e.originalEvent.dataTransfer.getData('text/plain');
                if (!data) return;

                var itemIds = data.split(',');
                if (itemIds.length) {
                    self.moveAttachments(itemIds, targetFolderId);
                }
                setTimeout(function() {
                    self.forceDismissUploader();
                }, 50);
            });
        },

        selectFolder: function(folderId) {
            var self = this;
            self.currentFolder = folderId;

            $('.tmf-item').removeClass('tmf-active');
            $('.tmf-item[data-folder-id="' + folderId + '"]').addClass('tmf-active');

            // If List View -> Navigate
            if ($('body').hasClass('upload-php') && $('#posts-filter').length && !$('#wp-media-grid').length) {
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
            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media.frame;
                var library = null;

                if (frame && frame.content && frame.content.get && frame.content.get()) {
                    var content = frame.content.get();
                    library = content.collection || (content.options && content.options.selection);
                }
                if (!library && frame && frame.state && typeof frame.state === 'function') {
                    var state = frame.state();
                    if (state && state.get) {
                        library = state.get('library');
                    }
                }

                if (library && library.props) {
                    library.props.set({ techno_folder: folderId });
                    library.props.set({ query: true }); // trigger refresh
                }
            }
        },

        setupDraggableAttachments: function($frame) {
            var self = this;

            // Ensure draggable="true" on all attachments and list rows on hover or touch
            $(document).on('mouseenter mouseover', '.attachments .attachment, #the-list tr', function() {
                if (!$(this).attr('draggable')) {
                    $(this).attr('draggable', 'true');
                }
            });

            // Make .attachment and list rows draggable
            $(document).on('dragstart', '.attachment, #the-list tr', function(e) {
                var $item = $(this);
                var id = $item.data('id');
                if (!id && $item.attr('id')) {
                    id = $item.attr('id').replace(/^(post-|attachment-)/, '');
                }
                if (!id) return;

                var ids = [id];
                // Check if multiple attachments are selected in bulk mode
                if ($item.hasClass('selected')) {
                    var selectedIds = [];
                    $('.attachment.selected').each(function() {
                        var sId = $(this).data('id') || ($(this).attr('id') ? $(this).attr('id').replace(/^(post-|attachment-)/, '') : '');
                        if (sId) selectedIds.push(sId);
                    });
                    if (selectedIds.length > 0) ids = selectedIds;
                } else if ($('#the-list').length) {
                    var checkedIds = [];
                    $('#the-list input[name="media[]"]:checked').each(function() {
                        checkedIds.push($(this).val());
                    });
                    if (checkedIds.indexOf(id.toString()) !== -1 && checkedIds.length > 1) {
                        ids = checkedIds;
                    }
                }

                e.originalEvent.dataTransfer.setData('text/plain', ids.join(','));
                e.originalEvent.dataTransfer.effectAllowed = 'move';

                $item.addClass('tmf-dragging');
                $('body').addClass('tmf-is-dragging');
            });

            $(document).on('dragend', function() {
                $('.tmf-dragging').removeClass('tmf-dragging');
                $('.tmf-droppable-hover').removeClass('tmf-droppable-hover');
                self.forceDismissUploader();
                setTimeout(function() {
                    self.forceDismissUploader();
                }, 50);
                setTimeout(function() {
                    self.forceDismissUploader();
                }, 200);
            });

            // Dismiss stuck WordPress uploader-window on click or mouseover
            $(document).on('click mouseenter', '.uploader-window', function() {
                self.forceDismissUploader();
            });

            // Capture-phase prevention: stops WordPress UploaderWindow from activating during internal drags
            window.addEventListener('dragenter', function(e) {
                if (document.body.classList.contains('tmf-is-dragging')) {
                    e.stopImmediatePropagation();
                    $('.uploader-window').hide().css({ display: 'none', opacity: 0 });
                }
            }, true);

            window.addEventListener('dragover', function(e) {
                if (document.body.classList.contains('tmf-is-dragging')) {
                    e.stopImmediatePropagation();
                    $('.uploader-window').hide().css({ display: 'none', opacity: 0 });
                }
            }, true);

            window.addEventListener('dragleave', function(e) {
                if (document.body.classList.contains('tmf-is-dragging')) {
                    e.stopImmediatePropagation();
                }
            }, true);

            // Initial pass for currently rendered items
            setTimeout(function() {
                $('.attachments .attachment, #the-list tr').attr('draggable', 'true');
            }, 300);
        },

        forceDismissUploader: function() {
            $('body').removeClass('tmf-is-dragging drag-over');
            $('.uploader-window').hide().css({
                'display': 'none',
                'opacity': 0
            }).removeClass('drag-over');

            $('*').filter(function() {
                return $(this).hasClass('drag-over');
            }).removeClass('drag-over').trigger('dropzone:leave');

            if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
                if (wp.media.frame._uploader && typeof wp.media.frame._uploader.hide === 'function') {
                    wp.media.frame._uploader.hide();
                }
            }
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

        openInputModal: function(options) {
            var self = this;
            var title = options.title || i18n.newFolder;
            var label = options.label || i18n.enterFolderName;
            var value = options.value || '';
            var placeholder = options.placeholder || '';
            var confirmText = options.confirmText || 'ตกลง';
            var cancelText = options.cancelText || 'ยกเลิก';

            $('#tmf-modal-container').remove();

            var html = '<div id="tmf-modal-container" class="tmf-modal-backdrop">' +
                '<div class="tmf-modal-dialog" role="dialog" aria-modal="true">' +
                    '<div class="tmf-modal-header">' +
                        '<h3 class="tmf-modal-title"><span class="dashicons dashicons-category"></span> ' + escapeHtml(title) + '</h3>' +
                        '<button type="button" class="tmf-modal-close" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button>' +
                    '</div>' +
                    '<form class="tmf-modal-form">' +
                        '<div class="tmf-modal-body">' +
                            (label ? '<label class="tmf-modal-label">' + escapeHtml(label) + '</label>' : '') +
                            '<input type="text" class="tmf-modal-input regular-text" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(placeholder) + '" autofocus autocomplete="off" />' +
                        '</div>' +
                        '<div class="tmf-modal-footer">' +
                            '<button type="button" class="button button-secondary tmf-modal-cancel">' + escapeHtml(cancelText) + '</button>' +
                            '<button type="submit" class="button button-primary tmf-modal-submit">' + escapeHtml(confirmText) + '</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>';

            $('body').append(html);
            var $container = $('#tmf-modal-container');
            var $input = $container.find('.tmf-modal-input');

            // Focus and select input
            setTimeout(function() {
                $input.focus().select();
            }, 60);

            function closeModal() {
                $container.fadeOut(120, function() {
                    $container.remove();
                });
                $(document).off('keydown.tmfModal');
            }

            $container.on('click', '.tmf-modal-close, .tmf-modal-cancel', function(e) {
                e.preventDefault();
                closeModal();
            });

            $container.on('click', function(e) {
                if ($(e.target).is('#tmf-modal-container')) {
                    closeModal();
                }
            });

            $container.find('.tmf-modal-form').on('submit', function(e) {
                e.preventDefault();
                var newVal = $input.val().trim();
                if (!newVal) {
                    $input.addClass('tmf-input-error').focus();
                    return;
                }
                closeModal();
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm(newVal);
                }
            });

            $(document).on('keydown.tmfModal', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        },

        openConfirmModal: function(options) {
            var self = this;
            var title = options.title || 'ยืนยันการดำเนินการ';
            var message = options.message || '';
            var confirmText = options.confirmText || 'ยืนยันการลบ';
            var cancelText = options.cancelText || 'ยกเลิก';
            var isDanger = options.isDanger !== false;

            $('#tmf-modal-container').remove();

            var html = '<div id="tmf-modal-container" class="tmf-modal-backdrop">' +
                '<div class="tmf-modal-dialog tmf-modal-confirm-dialog" role="dialog" aria-modal="true">' +
                    '<div class="tmf-modal-header">' +
                        '<h3 class="tmf-modal-title">' +
                            (isDanger ? '<span class="dashicons dashicons-warning tmf-color-danger"></span> ' : '<span class="dashicons dashicons-info"></span> ') +
                            escapeHtml(title) +
                        '</h3>' +
                        '<button type="button" class="tmf-modal-close" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button>' +
                    '</div>' +
                    '<div class="tmf-modal-body">' +
                        '<p class="tmf-modal-message">' + escapeHtml(message) + '</p>' +
                    '</div>' +
                    '<div class="tmf-modal-footer">' +
                        '<button type="button" class="button button-secondary tmf-modal-cancel">' + escapeHtml(cancelText) + '</button>' +
                        '<button type="button" class="button ' + (isDanger ? 'tmf-btn-danger' : 'button-primary') + ' tmf-modal-confirm-btn">' + escapeHtml(confirmText) + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            $('body').append(html);
            var $container = $('#tmf-modal-container');

            function closeModal() {
                $container.fadeOut(120, function() {
                    $container.remove();
                });
                $(document).off('keydown.tmfModal');
            }

            $container.on('click', '.tmf-modal-close, .tmf-modal-cancel', function(e) {
                e.preventDefault();
                closeModal();
            });

            $container.on('click', function(e) {
                if ($(e.target).is('#tmf-modal-container')) {
                    closeModal();
                }
            });

            $container.on('click', '.tmf-modal-confirm-btn', function(e) {
                e.preventDefault();
                closeModal();
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm();
                }
            });

            $(document).on('keydown.tmfModal', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                } else if (e.key === 'Enter') {
                    $container.find('.tmf-modal-confirm-btn').trigger('click');
                }
            });
        },

        promptCreateFolder: function(parentId) {
            var self = this;
            var isSub = parentId && parentId !== 0 && parentId !== '0';
            self.openInputModal({
                title: isSub ? 'สร้างโฟลเดอร์ย่อย' : 'สร้างโฟลเดอร์ใหม่',
                label: 'กรุณาระบุชื่อโฟลเดอร์:',
                placeholder: 'เช่น เอกสารสำคัญ, กิจกรรม 2567...',
                confirmText: 'สร้างโฟลเดอร์',
                onConfirm: function(name) {
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'techno_mf_create_folder',
                            nonce: nonce,
                            name: name,
                            parent: parentId || 0
                        },
                        success: function(res) {
                            if (res.success) {
                                self.showToast('✓ สร้างโฟลเดอร์ "' + res.data.name + '" เรียบร้อยแล้ว', 'success');
                                self.fetchTree();
                            } else {
                                self.showToast(res.data ? res.data.message : 'เกิดข้อผิดพลาด', 'error');
                            }
                        }
                    });
                }
            });
        },

        promptRenameFolder: function(folderId, currentName) {
            var self = this;
            self.openInputModal({
                title: 'เปลี่ยนชื่อโฟลเดอร์',
                label: 'ระบุชื่อโฟลเดอร์ใหม่:',
                value: currentName,
                confirmText: 'บันทึก',
                onConfirm: function(name) {
                    if (name === currentName) return;
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'techno_mf_rename_folder',
                            nonce: nonce,
                            id: folderId,
                            name: name
                        },
                        success: function(res) {
                            if (res.success) {
                                self.showToast('✓ เปลี่ยนชื่อโฟลเดอร์เรียบร้อยแล้ว', 'success');
                                self.fetchTree();
                            } else {
                                self.showToast(res.data ? res.data.message : 'เกิดข้อผิดพลาด', 'error');
                            }
                        }
                    });
                }
            });
        },

        confirmDeleteFolder: function(folderId, folderName) {
            var self = this;
            self.openConfirmModal({
                title: 'ยืนยันการลบโฟลเดอร์',
                message: 'คุณต้องการลบโฟลเดอร์ "' + folderName + '" ใช่หรือไม่?\n(ไฟล์และรูปภาพในโฟลเดอร์จะไม่ถูกลบ แต่จะกลับไปอยู่ที่ "ยังไม่มีโฟลเดอร์")',
                confirmText: '🗑️ ยืนยันการลบ',
                isDanger: true,
                onConfirm: function() {
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
                                self.showToast(res.data ? res.data.message : 'เกิดข้อผิดพลาด', 'error');
                            }
                        }
                    });
                }
            });
        },

        moveAttachments: function(itemIds, targetFolderId) {
            var self = this;
            self.forceDismissUploader();
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
                    self.forceDismissUploader();
                    if (res.success) {
                        var targetName = res.data && res.data.target_name ? res.data.target_name : '';
                        var count = res.data && res.data.moved_count ? res.data.moved_count : itemIds.length;
                        self.showToast('✓ ย้าย ' + count + ' ไฟล์ไปยัง "' + targetName + '" เรียบร้อยแล้ว', 'success');
                        self.fetchTree();

                        // If in List View
                        if ($('#the-list').length) {
                            if (self.currentFolder !== 'all') {
                                itemIds.forEach(function(id) {
                                    $('#post-' + id).fadeOut(250, function() { $(this).remove(); });
                                });
                            }
                        }

                        // If in Grid View
                        if (typeof wp !== 'undefined' && wp.media) {
                            var frame = wp.media.frame;
                            var library = null;
                            if (frame && frame.content && frame.content.get && frame.content.get()) {
                                var content = frame.content.get();
                                library = content.collection || (content.options && content.options.selection);
                            }
                            if (!library && frame && frame.state && typeof frame.state === 'function') {
                                var state = frame.state();
                                if (state && state.get) library = state.get('library');
                            }

                            if (library) {
                                if (library.props) {
                                    library.props.set({ query: true });
                                }
                                if (typeof library._requery === 'function') {
                                    library._requery(true);
                                } else if (typeof library.more === 'function') {
                                    library.reset();
                                    library.more();
                                }
                            }
                        }
                    } else {
                        self.showToast(res.data ? res.data.message : 'เกิดข้อผิดพลาดในการย้ายไฟล์', 'error');
                    }
                },
                error: function() {
                    self.forceDismissUploader();
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
