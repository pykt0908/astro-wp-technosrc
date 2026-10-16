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
        $new_columns['banner_thumbnail'] = __('รูปภาพแบนเนอร์ (1709x650)', 'project-core');
        $new_columns['title']            = __('ชื่อแบนเนอร์ / หัวข้อ', 'project-core');
        $new_columns['banner_target']    = __('ลิงก์ปลายทาง (Target URL)', 'project-core');
        $new_columns['banner_status']    = __('สถานะแสดงผล', 'project-core');
        $new_columns['banner_order']     = __('ลำดับ', 'project-core');
        $new_columns['date']             = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents
     */
    public function render_banner_columns($column, $post_id) {
        switch ($column) {
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

            case 'banner_order':
                echo (int) get_post_field('menu_order', $post_id);
                break;
        }
    }
}
