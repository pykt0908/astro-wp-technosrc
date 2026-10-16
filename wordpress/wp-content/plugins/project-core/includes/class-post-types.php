<?php
/**
 * Custom Post Types and Taxonomies Registration
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Post_Types {

    public function init() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('manage_banner_posts_columns', array($this, 'set_banner_columns'));
        add_action('manage_banner_posts_custom_column', array($this, 'render_banner_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_banner_sort_order'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_banner_list_scripts'));
        add_action('wp_ajax_project_core_reorder_banners', array($this, 'ajax_reorder_banners'));
        add_action('wp_ajax_project_core_set_single_banner_order', array($this, 'ajax_set_single_banner_order'));
    }

    /**
     * Register Banner Custom Post Type
     */
    public function register_post_types() {
        $labels = array(
            'name'                  => __('Banners', 'project-core'),
            'singular_name'         => __('Banner', 'project-core'),
            'menu_name'             => __('Banners / แบนเนอร์', 'project-core'),
            'name_admin_bar'        => __('Banner', 'project-core'),
            'add_new'               => __('Add New Banner', 'project-core'),
            'add_new_item'          => __('Add New Banner', 'project-core'),
            'new_item'              => __('New Banner', 'project-core'),
            'edit_item'             => __('Edit Banner', 'project-core'),
            'view_item'             => __('View Banner', 'project-core'),
            'all_items'             => __('All Banners', 'project-core'),
            'search_items'          => __('Search Banners', 'project-core'),
            'not_found'             => __('No banners found.', 'project-core'),
            'not_found_in_trash'    => __('No banners found in Trash.', 'project-core'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false, // Only for Headless API, not direct single pages
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-images-alt2',
            'supports'           => array('title', 'thumbnail', 'page-attributes'), // page-attributes provides menu_order
            'show_in_rest'       => true,
            'rest_base'          => 'banners',
        );

        register_post_type('banner', $args);
    }

    /**
     * Register Taxonomies
     */
    public function register_taxonomies() {
        $labels = array(
            'name'              => __('Banner Positions / ตำแหน่งแบนเนอร์', 'project-core'),
            'singular_name'     => __('Banner Position', 'project-core'),
            'search_items'      => __('Search Positions', 'project-core'),
            'all_items'         => __('All Positions', 'project-core'),
            'edit_item'         => __('Edit Position', 'project-core'),
            'update_item'       => __('Update Position', 'project-core'),
            'add_new_item'      => __('Add New Position', 'project-core'),
            'new_item_name'     => __('New Position Name', 'project-core'),
            'menu_name'         => __('Positions / ตำแหน่ง', 'project-core'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
        );

        register_taxonomy('banner_position', array('banner'), $args);
    }

    /**
     * Define custom columns in Banners admin list
     */
    public function set_banner_columns($columns) {
        $new_columns = array();
        $new_columns['cb']               = $columns['cb'];
        $new_columns['banner_handle']    = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเพื่อสลับลำดับ', 'project-core') . '"></span>';
        $new_columns['banner_order']     = __('ลำดับ', 'project-core');
        $new_columns['banner_thumbnail'] = __('รูปภาพแบนเนอร์ (1709x650)', 'project-core');
        $new_columns['title']            = __('ชื่อแบนเนอร์ / หัวข้อ', 'project-core');
        $new_columns['banner_target']    = __('ลิงก์ปลายทาง (Target URL)', 'project-core');
        $new_columns['banner_status']    = __('สถานะแสดงผล', 'project-core');
        $new_columns['date']             = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents
     */
    public function render_banner_columns($column, $post_id) {
        switch ($column) {
            case 'banner_handle':
                echo '<span class="dashicons dashicons-move banner-drag-handle" style="cursor: grab; color: #787c82; font-size: 20px; line-height: 28px;" title="คลิกค้างแล้วลากเพื่อสลับลำดับ"></span>';
                break;

            case 'banner_order':
                $order = (int) get_post_field('menu_order', $post_id);
                echo '<div class="banner-order-control" style="display: flex; align-items: center; gap: 4px;">';
                echo '<input type="number" class="banner-quick-order-input" data-post-id="' . esc_attr($post_id) . '" value="' . esc_attr($order) . '" style="width: 52px; height: 28px; text-align: center; font-weight: bold; padding: 2px;" min="0" title="พิมพ์เปลี่ยนลำดับแล้วกด Enter หรือคลิกข้างนอกเพื่อบันทึกทันที" />';
                echo '<div style="display: flex; flex-direction: column; gap: 2px;">';
                echo '<button type="button" class="button banner-move-btn" data-action="up" data-post-id="' . esc_attr($post_id) . '" style="padding: 0 5px; line-height: 12px; height: 13px; font-size: 9px; min-height: 13px;" title="เลื่อนขึ้นด้านบน">▲</button>';
                echo '<button type="button" class="button banner-move-btn" data-action="down" data-post-id="' . esc_attr($post_id) . '" style="padding: 0 5px; line-height: 12px; height: 13px; font-size: 9px; min-height: 13px;" title="เลื่อนลงด้านล่าง">▼</button>';
                echo '</div>';
                echo '</div>';
                break;

            case 'banner_thumbnail':
                $img = get_the_post_thumbnail_url($post_id, 'medium');
                if (empty($img)) {
                    $img = get_post_meta($post_id, 'banner_desktop_image_url', true);
                }
                if (!empty($img)) {
                    echo '<div style="width: 140px; aspect-ratio: 1709/650; overflow: hidden; border-radius: 4px; border: 1px solid #c3c4c7; background: #000;">';
                    echo '<img src="' . esc_url($img) . '" style="width: 100%; height: 100%; object-fit: cover;" alt="" />';
                    echo '</div>';
                } else {
                    echo '<span style="color: #a7aaad;">— ยังไม่มีรูปภาพ —</span>';
                }
                break;

            case 'banner_target':
                $url = get_post_meta($post_id, 'banner_target_url', true);
                if (!empty($url)) {
                    echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" style="font-size: 12px; word-break: break-all;">' . esc_html($url) . ' ↗</a>';
                } else {
                    echo '<span style="color: #a7aaad;">(ไม่มีลิงก์)</span>';
                }
                break;

            case 'banner_status':
                $is_active = get_post_meta($post_id, 'banner_is_active', true);
                if ($is_active === '0') {
                    echo '<span style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: #fcf0f1; color: #d63638; font-weight: 600; font-size: 12px;">ปิดใช้งาน</span>';
                } else {
                    echo '<span style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: #ecf7ed; color: #007017; font-weight: 600; font-size: 12px;">เปิดใช้งาน</span>';
                }
                break;
        }
    }

    /**
     * Default sort order by menu_order ASC in WordPress Admin Banners table
     */
    public function admin_banner_sort_order($query) {
        if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'banner') {
            if (!$query->get('orderby')) {
                $query->set('orderby', 'menu_order');
                $query->set('order', 'ASC');
            }
        }
    }

    /**
     * Enqueue Drag & Drop sorting scripts in Banners list view
     */
    public function enqueue_banner_list_scripts($hook) {
        if ($hook === 'edit.php') {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'banner') {
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_style('dashicons');

                $nonce = wp_create_nonce('project_core_reorder_nonce');
                ?>
                <style>
                    .wp-list-table tr.ui-sortable-helper {
                        background: #ffffff !important;
                        box-shadow: 0 8px 24px rgba(0,0,0,0.18) !important;
                        display: table !important;
                    }
                    .wp-list-table tr.ui-sortable-placeholder {
                        background: #e7f3ff !important;
                        visibility: visible !important;
                        outline: 2px dashed #0047BA !important;
                    }
                    .banner-drag-handle:active {
                        cursor: grabbing !important;
                    }
                    .column-banner_handle {
                        width: 32px !important;
                        text-align: center;
                        padding-left: 4px !important;
                        padding-right: 4px !important;
                    }
                    .column-banner_order {
                        width: 95px !important;
                    }
                    .column-banner_thumbnail {
                        width: 155px !important;
                    }
                    #banner-reorder-notice {
                        display: none;
                        position: fixed;
                        top: 45px;
                        right: 35px;
                        z-index: 999999;
                        padding: 12px 22px;
                        background: #007017;
                        color: #ffffff;
                        font-weight: 600;
                        border-radius: 6px;
                        box-shadow: 0 6px 20px rgba(0,0,0,0.25);
                        font-size: 14px;
                    }
                </style>
                <div id="banner-reorder-notice">✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว</div>
                <script>
                jQuery(document).ready(function($) {
                    var $tbody = $('#the-list');
                    if (!$tbody.length) return;

                    function showNotice(msg) {
                        var $notice = $('#banner-reorder-notice');
                        $notice.text(msg || '✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                        setTimeout(function() {
                            $notice.fadeOut(400);
                        }, 2500);
                    }

                    function saveOrder() {
                        var postIds = [];
                        $tbody.find('tr').each(function(index) {
                            var rowId = $(this).attr('id');
                            if (rowId && rowId.indexOf('post-') === 0) {
                                var pid = rowId.replace('post-', '');
                                postIds.push(pid);
                                $(this).find('.banner-quick-order-input').val(index + 1);
                            }
                        });

                        if (!postIds.length) return;

                        $.post(ajaxurl, {
                            action: 'project_core_reorder_banners',
                            nonce: '<?php echo esc_js($nonce); ?>',
                            post_ids: postIds
                        }, function(res) {
                            if (res.success) {
                                showNotice('✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว (' + postIds.length + ' แบนเนอร์)');
                            }
                        });
                    }

                    // Drag and Drop sortable
                    $tbody.sortable({
                        items: 'tr',
                        handle: '.banner-drag-handle',
                        axis: 'y',
                        cursor: 'grabbing',
                        opacity: 0.9,
                        placeholder: 'ui-sortable-placeholder',
                        helper: function(e, tr) {
                            var $originals = tr.children();
                            var $helper = tr.clone();
                            $helper.children().each(function(index) {
                                $(this).width($originals.eq(index).width());
                            });
                            return $helper;
                        },
                        update: function() {
                            saveOrder();
                        }
                    });

                    // Up / Down arrow buttons
                    $tbody.on('click', '.banner-move-btn', function(e) {
                        e.preventDefault();
                        var $row = $(this).closest('tr');
                        var action = $(this).data('action');

                        if (action === 'up') {
                            var $prev = $row.prev('tr');
                            if ($prev.length) {
                                $row.insertBefore($prev);
                                saveOrder();
                            }
                        } else if (action === 'down') {
                            var $next = $row.next('tr');
                            if ($next.length) {
                                $row.insertAfter($next);
                                saveOrder();
                            }
                        }
                    });

                    // Inline input change
                    $tbody.on('change', '.banner-quick-order-input', function() {
                        var targetOrder = parseInt($(this).val(), 10);
                        var postId = $(this).data('post-id');
                        $.post(ajaxurl, {
                            action: 'project_core_set_single_banner_order',
                            nonce: '<?php echo esc_js($nonce); ?>',
                            post_id: postId,
                            order: targetOrder
                        }, function(res) {
                            if (res.success) {
                                showNotice('✓ บันทึกลำดับแล้ว กำลังจัดเรียงตารางใหม่...');
                                setTimeout(function() { location.reload(); }, 600);
                            }
                        });
                    });
                });
                </script>
                <?php
            }
        }
    }

    /**
     * AJAX endpoint: reorder multiple banners from drag and drop
     */
    public function ajax_reorder_banners() {
        check_ajax_referer('project_core_reorder_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $post_ids = isset($_POST['post_ids']) ? (array) $_POST['post_ids'] : array();
        foreach ($post_ids as $order => $post_id) {
            $post_id = absint($post_id);
            if ($post_id > 0) {
                wp_update_post(array(
                    'ID'         => $post_id,
                    'menu_order' => $order + 1,
                ));
            }
        }

        wp_send_json_success(array('message' => 'Reordered successfully'));
    }

    /**
     * AJAX endpoint: set single banner order
     */
    public function ajax_set_single_banner_order() {
        check_ajax_referer('project_core_reorder_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $order   = isset($_POST['order']) ? intval($_POST['order']) : 0;

        if ($post_id > 0) {
            wp_update_post(array(
                'ID'         => $post_id,
                'menu_order' => $order,
            ));
            wp_send_json_success(array('message' => 'Order updated'));
        }

        wp_send_json_error('Invalid post ID');
    }
}
