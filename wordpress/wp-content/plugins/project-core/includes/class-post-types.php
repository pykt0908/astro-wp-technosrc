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
}
