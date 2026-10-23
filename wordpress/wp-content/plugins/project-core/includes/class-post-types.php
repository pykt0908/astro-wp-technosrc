<?php
/**
 * Custom Post Types and Taxonomies Registration
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Post_Types
{

    public function init()
    {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('manage_banner_posts_columns', array($this, 'set_banner_columns'));
        add_action('manage_banner_posts_custom_column', array($this, 'render_banner_columns'), 10, 2);
        add_filter('manage_college_event_posts_columns', array($this, 'set_event_columns'));
        add_action('manage_college_event_posts_custom_column', array($this, 'render_event_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_banner_sort_order'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_banner_list_scripts'));
        add_action('admin_footer', array($this, 'render_banner_reorder_script'));
        add_action('wp_ajax_project_core_reorder_banners', array($this, 'ajax_reorder_banners'));

        // College Events Admin Filters
        add_action('restrict_manage_posts', array($this, 'filter_events_by_category'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_event_filter_query'));

        // Curriculum Admin Columns & Filters
        add_filter('manage_curriculum_posts_columns', array($this, 'set_curriculum_columns'));
        add_action('manage_curriculum_posts_custom_column', array($this, 'render_curriculum_columns'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'filter_curriculums'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_curriculum_filter_query'));

        // Personnel Admin Columns, Reorder & Filters
        add_filter('manage_personnel_posts_columns', array($this, 'set_personnel_columns'));
        add_action('manage_personnel_posts_custom_column', array($this, 'render_personnel_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_personnel_sort_order'));
        add_action('pre_get_terms', array($this, 'admin_personnel_category_sort_order'));
        add_action('restrict_manage_posts', array($this, 'filter_personnel_by_category'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_personnel_filter_query'));
        add_action('admin_menu', array($this, 'register_personnel_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_personnel_list_scripts'));
        add_action('admin_footer', array($this, 'render_personnel_reorder_script'));
        add_action('wp_ajax_project_core_reorder_personnel', array($this, 'ajax_reorder_personnel'));
        add_action('wp_ajax_project_core_reorder_personnel_categories', array($this, 'ajax_reorder_personnel_categories'));
        add_action('wp_ajax_project_core_reorder_dept_personnel', array($this, 'ajax_reorder_dept_personnel'));
        add_action('wp_ajax_project_core_auto_index_personnel', array($this, 'ajax_auto_index_personnel'));

        // SAR Reports Admin Columns & Filters
        add_filter('manage_sar_report_posts_columns', array($this, 'set_sar_report_columns'));
        add_action('manage_sar_report_posts_custom_column', array($this, 'render_sar_report_columns'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'filter_sar_reports_by_year'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_sar_report_filter_query'));

        // Educational MIS Admin Columns & Filters
        add_filter('manage_education_mis_posts_columns', array($this, 'set_education_mis_columns'));
        add_action('manage_education_mis_posts_custom_column', array($this, 'render_education_mis_columns'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'filter_education_mis_by_year'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_education_mis_filter_query'));

        // Downloads Admin Columns, Filters & Reorder
        add_filter('manage_download_file_posts_columns', array($this, 'set_download_file_columns'));
        add_action('manage_download_file_posts_custom_column', array($this, 'render_download_file_columns'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'filter_downloads_by_category'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_download_filter_query'));
        add_action('pre_get_terms', array($this, 'admin_download_category_sort_order'));
        add_action('download_category_add_form_fields', array($this, 'download_category_add_fields'));
        add_action('download_category_edit_form_fields', array($this, 'download_category_edit_fields'));
        add_action('created_download_category', array($this, 'save_download_category_fields'));
        add_action('edited_download_category', array($this, 'save_download_category_fields'));
        add_filter('manage_edit-download_category_columns', array($this, 'set_download_category_columns'));
        add_filter('manage_download_category_custom_column', array($this, 'render_download_category_columns'), 10, 3);
        add_filter('manage_edit-download_category_sortable_columns', array($this, 'set_download_category_sortable_columns'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_download_list_scripts'));
        add_action('admin_footer', array($this, 'render_download_reorder_script'));
        add_action('wp_ajax_project_core_reorder_downloads', array($this, 'ajax_reorder_downloads'));
        add_action('wp_ajax_project_core_reorder_download_categories', array($this, 'ajax_reorder_download_categories'));

        // Student Handbook Admin Columns, Filters & Reorder
        add_filter('manage_student_handbook_posts_columns', array($this, 'set_student_handbook_columns'));
        add_action('manage_student_handbook_posts_custom_column', array($this, 'render_student_handbook_columns'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'filter_handbooks_by_category'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_handbook_filter_query'));
        add_action('pre_get_terms', array($this, 'admin_handbook_category_sort_order'));
        add_action('handbook_category_add_form_fields', array($this, 'handbook_category_add_fields'));
        add_action('handbook_category_edit_form_fields', array($this, 'handbook_category_edit_fields'));
        add_action('created_handbook_category', array($this, 'save_handbook_category_fields'));
        add_action('edited_handbook_category', array($this, 'save_handbook_category_fields'));
        add_filter('manage_edit-handbook_category_columns', array($this, 'set_handbook_category_columns'));
        add_filter('manage_handbook_category_custom_column', array($this, 'render_handbook_category_columns'), 10, 3);
        add_filter('manage_edit-handbook_category_sortable_columns', array($this, 'set_handbook_category_sortable_columns'));

        // Open House Activities Admin Columns & Submenu
        add_action('admin_menu', array($this, 'register_openhouse_admin_menus'));
        add_filter('manage_openhouse_activity_posts_columns', array($this, 'set_openhouse_activity_columns'));
        add_action('manage_openhouse_activity_posts_custom_column', array($this, 'render_openhouse_activity_columns'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'filter_openhouse_activities_by_category'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_openhouse_activity_filter_query'));
    }

    /**
     * Register Custom Post Types (Banner & College Event)
     */
    public function register_post_types()
    {
        // Banner CPT
        $labels = array(
            'name' => __('Banners', 'project-core'),
            'singular_name' => __('Banner', 'project-core'),
            'menu_name' => __('Banners / แบนเนอร์', 'project-core'),
            'name_admin_bar' => __('Banner', 'project-core'),
            'add_new' => __('Add New Banner', 'project-core'),
            'add_new_item' => __('Add New Banner', 'project-core'),
            'new_item' => __('New Banner', 'project-core'),
            'edit_item' => __('Edit Banner', 'project-core'),
            'view_item' => __('View Banner', 'project-core'),
            'all_items' => __('All Banners', 'project-core'),
            'search_items' => __('Search Banners', 'project-core'),
            'not_found' => __('No banners found.', 'project-core'),
            'not_found_in_trash' => __('No banners found in Trash.', 'project-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false, // Only for Headless API, not direct single pages
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-images-alt2',
            'supports' => array('title', 'thumbnail', 'page-attributes'), // page-attributes provides menu_order
            'show_in_rest' => true,
            'rest_base' => 'banners',
        );

        register_post_type('banner', $args);

        // College Event CPT (ปฏิทินกิจกรรมวิทยาลัย)
        $event_labels = array(
            'name' => __('ปฏิทินกิจกรรม', 'project-core'),
            'singular_name' => __('กิจกรรม', 'project-core'),
            'menu_name' => __('ปฏิทินวิทยาลัย', 'project-core'),
            'name_admin_bar' => __('กิจกรรม', 'project-core'),
            'add_new' => __('เพิ่มกิจกรรมใหม่', 'project-core'),
            'add_new_item' => __('เพิ่มกิจกรรมใหม่', 'project-core'),
            'new_item' => __('กิจกรรมใหม่', 'project-core'),
            'edit_item' => __('แก้ไขกิจกรรม', 'project-core'),
            'view_item' => __('ดูกิจกรรม', 'project-core'),
            'all_items' => __('กิจกรรมทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหากิจกรรม', 'project-core'),
            'not_found' => __('ไม่พบกิจกรรมในปฏิทิน', 'project-core'),
            'not_found_in_trash' => __('ไม่พบกิจกรรมในถังขยะ', 'project-core'),
        );

        $event_args = array(
            'labels' => $event_labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 21,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
            'show_in_rest' => true,
            'rest_base' => 'events',
        );

        register_post_type('college_event', $event_args);

        // Curriculum CPT (หลักสูตรที่เปิดสอน)
        $curriculum_labels = array(
            'name' => __('หลักสูตรการศึกษา', 'project-core'),
            'singular_name' => __('หลักสูตร', 'project-core'),
            'menu_name' => __('หลักสูตร / สาขาวิชา', 'project-core'),
            'name_admin_bar' => __('หลักสูตร', 'project-core'),
            'add_new' => __('เพิ่มหลักสูตรใหม่', 'project-core'),
            'add_new_item' => __('เพิ่มหลักสูตรใหม่', 'project-core'),
            'new_item' => __('หลักสูตรใหม่', 'project-core'),
            'edit_item' => __('แก้ไขหลักสูตร', 'project-core'),
            'view_item' => __('ดูหลักสูตร', 'project-core'),
            'all_items' => __('หลักสูตรทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหาหลักสูตร', 'project-core'),
            'not_found' => __('ไม่พบหลักสูตรที่ค้นหา', 'project-core'),
            'not_found_in_trash' => __('ไม่พบหลักสูตรในถังขยะ', 'project-core'),
        );

        $curriculum_args = array(
            'labels' => $curriculum_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'curriculum', 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 22,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'slug'),
            'show_in_rest' => true,
            'rest_base' => 'curriculums',
        );

        register_post_type('curriculum', $curriculum_args);

        // Personnel CPT (บุคลากรวิทยาลัย)
        $personnel_labels = array(
            'name' => __('บุคลากร', 'project-core'),
            'singular_name' => __('บุคลากร', 'project-core'),
            'menu_name' => __('บุคลากร', 'project-core'),
            'name_admin_bar' => __('บุคลากร', 'project-core'),
            'add_new' => __('เพิ่มบุคลากรใหม่', 'project-core'),
            'add_new_item' => __('เพิ่มบุคลากรใหม่', 'project-core'),
            'new_item' => __('บุคลากรใหม่', 'project-core'),
            'edit_item' => __('แก้ไขข้อมูลบุคลากร', 'project-core'),
            'view_item' => __('ดูข้อมูลบุคลากร', 'project-core'),
            'all_items' => __('บุคลากรทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหาบุคลากร', 'project-core'),
            'not_found' => __('ไม่พบข้อมูลบุคลากร', 'project-core'),
            'not_found_in_trash' => __('ไม่พบข้อมูลบุคลากรในถังขยะ', 'project-core'),
        );

        $personnel_args = array(
            'labels' => $personnel_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'personnel', 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 23,
            'menu_icon' => 'dashicons-groups',
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest' => true,
            'rest_base' => 'personnel',
        );

        register_post_type('personnel', $personnel_args);

        // SAR Report CPT (รายงานผลการประเมินตนเอง)
        $sar_labels = array(
            'name' => __('รายงาน SAR', 'project-core'),
            'singular_name' => __('รายงาน SAR', 'project-core'),
            'menu_name' => __('รายงาน SAR', 'project-core'),
            'name_admin_bar' => __('รายงาน SAR', 'project-core'),
            'add_new' => __('เพิ่มรายงาน SAR', 'project-core'),
            'add_new_item' => __('เพิ่มรายงาน SAR ใหม่', 'project-core'),
            'new_item' => __('รายงาน SAR ใหม่', 'project-core'),
            'edit_item' => __('แก้ไขรายงาน SAR', 'project-core'),
            'view_item' => __('ดูรายงาน SAR', 'project-core'),
            'all_items' => __('รายงาน SAR ทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหารายงาน SAR', 'project-core'),
            'not_found' => __('ไม่พบรายงาน SAR', 'project-core'),
            'not_found_in_trash' => __('ไม่พบรายงาน SAR ในถังขยะ', 'project-core'),
        );

        $sar_args = array(
            'labels' => $sar_labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 24,
            'menu_icon' => 'dashicons-media-document',
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest' => true,
            'rest_base' => 'sar-reports',
        );

        register_post_type('sar_report', $sar_args);

        // Educational MIS CPT
        $mis_labels = array(
            'name' => __('สารสนเทศทางการศึกษา / Educational MIS', 'project-core'),
            'singular_name' => __('สารสนเทศทางการศึกษา', 'project-core'),
            'menu_name' => __('สารสนเทศทางการศึกษา', 'project-core'),
            'name_admin_bar' => __('สารสนเทศทางการศึกษา', 'project-core'),
            'add_new' => __('เพิ่มข้อมูลสารสนเทศ', 'project-core'),
            'add_new_item' => __('เพิ่มข้อมูลสารสนเทศประจำปี', 'project-core'),
            'new_item' => __('ข้อมูลใหม่', 'project-core'),
            'edit_item' => __('แก้ไขข้อมูลสารสนเทศ', 'project-core'),
            'view_item' => __('ดูข้อมูลสารสนเทศ', 'project-core'),
            'all_items' => __('ข้อมูลสารสนเทศทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหาข้อมูลสารสนเทศ', 'project-core'),
            'not_found' => __('ไม่พบข้อมูลสารสนเทศ', 'project-core'),
            'not_found_in_trash' => __('ไม่พบในถังขยะ', 'project-core'),
        );

        $mis_args = array(
            'labels' => $mis_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'education-mis'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-chart-pie',
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest' => true,
            'rest_base' => 'education-mis-posts',
        );

        register_post_type('education_mis', $mis_args);

        // Download File CPT
        $download_labels = array(
            'name' => __('เอกสารดาวน์โหลด / Downloads', 'project-core'),
            'singular_name' => __('เอกสารดาวน์โหลด', 'project-core'),
            'menu_name' => __('ดาวน์โหลด', 'project-core'),
            'name_admin_bar' => __('เอกสารดาวน์โหลด', 'project-core'),
            'add_new' => __('เพิ่มไฟล์ดาวน์โหลด', 'project-core'),
            'add_new_item' => __('เพิ่มไฟล์เอกสารใหม่', 'project-core'),
            'new_item' => __('เอกสารใหม่', 'project-core'),
            'edit_item' => __('แก้ไขเอกสาร', 'project-core'),
            'view_item' => __('ดูเอกสาร', 'project-core'),
            'all_items' => __('เอกสารทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหาเอกสาร', 'project-core'),
            'not_found' => __('ไม่พบเอกสารดาวน์โหลด', 'project-core'),
            'not_found_in_trash' => __('ไม่พบในถังขยะ', 'project-core'),
        );

        $download_args = array(
            'labels' => $download_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'downloads'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-download',
            'supports' => array('title', 'page-attributes'),
            'show_in_rest' => true,
            'rest_base' => 'downloads-posts',
        );

        register_post_type('download_file', $download_args);

        // Student Handbook CPT (คู่มือและระเบียบการนักศึกษา)
        $handbook_labels = array(
            'name' => __('คู่มือและระเบียบการ', 'project-core'),
            'singular_name' => __('คู่มือ/ระเบียบการ', 'project-core'),
            'menu_name' => __('คู่มือ & ระเบียบการ', 'project-core'),
            'name_admin_bar' => __('คู่มือ/ระเบียบการ', 'project-core'),
            'add_new' => __('เพิ่มระเบียบ/คู่มือใหม่', 'project-core'),
            'add_new_item' => __('เพิ่มเอกสารคู่มือ/ระเบียบการใหม่', 'project-core'),
            'new_item' => __('คู่มือ/ระเบียบการใหม่', 'project-core'),
            'edit_item' => __('แก้ไขคู่มือ/ระเบียบการ', 'project-core'),
            'view_item' => __('ดูคู่มือ/ระเบียบการ', 'project-core'),
            'all_items' => __('เอกสารทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหาคู่มือ/ระเบียบการ', 'project-core'),
            'not_found' => __('ไม่พบคู่มือหรือระเบียบการ', 'project-core'),
            'not_found_in_trash' => __('ไม่พบในถังขยะ', 'project-core'),
        );

        $handbook_args = array(
            'labels' => $handbook_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'student-handbook'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 27,
            'menu_icon' => 'dashicons-book-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
            'show_in_rest' => true,
            'rest_base' => 'student-handbooks-posts',
        );

        register_post_type('student_handbook', $handbook_args);

        // Open House Activities CPT
        $openhouse_labels = array(
            'name' => __('กิจกรรมวันวิชาการ (Open House)', 'project-core'),
            'singular_name' => __('กิจกรรม/การแข่งขัน', 'project-core'),
            'menu_name' => __('วันวิชาการ (Open House)', 'project-core'),
            'name_admin_bar' => __('กิจกรรม Open House', 'project-core'),
            'add_new' => __('เพิ่มกิจกรรม/การแข่งขัน', 'project-core'),
            'add_new_item' => __('เพิ่มกิจกรรมหรือการแข่งขันใหม่', 'project-core'),
            'new_item' => __('กิจกรรมใหม่', 'project-core'),
            'edit_item' => __('แก้ไขกิจกรรม/การแข่งขัน', 'project-core'),
            'view_item' => __('ดูกิจกรรม', 'project-core'),
            'all_items' => __('กิจกรรมและการแข่งขันทั้งหมด', 'project-core'),
            'search_items' => __('ค้นหากิจกรรม/การแข่งขัน', 'project-core'),
            'not_found' => __('ไม่พบกิจกรรม', 'project-core'),
            'not_found_in_trash' => __('ไม่พบในถังขยะ', 'project-core'),
        );

        $openhouse_args = array(
            'labels' => $openhouse_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'openhouse-activity'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 28,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
            'show_in_rest' => true,
            'rest_base' => 'openhouse-activities-posts',
        );

        register_post_type('openhouse_activity', $openhouse_args);
    }

    /**
     * Register Taxonomies
     */
    public function register_taxonomies()
    {
        // Banner Position
        $labels = array(
            'name' => __('Banner Positions / ตำแหน่งแบนเนอร์', 'project-core'),
            'singular_name' => __('Banner Position', 'project-core'),
            'search_items' => __('Search Positions', 'project-core'),
            'all_items' => __('All Positions', 'project-core'),
            'edit_item' => __('Edit Position', 'project-core'),
            'update_item' => __('Update Position', 'project-core'),
            'add_new_item' => __('Add New Position', 'project-core'),
            'new_item_name' => __('New Position Name', 'project-core'),
            'menu_name' => __('Positions / ตำแหน่ง', 'project-core'),
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => false,
            'rewrite' => false,
            'show_in_rest' => true,
        );

        register_taxonomy('banner_position', array('banner'), $args);

        // Event Category (หมวดหมู่ปฏิทินกิจกรรม)
        $event_cat_labels = array(
            'name' => __('หมวดหมู่กิจกรรม', 'project-core'),
            'singular_name' => __('หมวดหมู่กิจกรรม', 'project-core'),
            'search_items' => __('ค้นหาหมวดหมู่', 'project-core'),
            'all_items' => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขหมวดหมู่', 'project-core'),
            'update_item' => __('อัปเดตหมวดหมู่', 'project-core'),
            'add_new_item' => __('เพิ่มหมวดหมู่ใหม่', 'project-core'),
            'new_item_name' => __('ชื่อหมวดหมู่ใหม่', 'project-core'),
            'menu_name' => __('หมวดหมู่กิจกรรม', 'project-core'),
        );

        $event_cat_args = array(
            'hierarchical' => true,
            'labels' => $event_cat_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'event-categories',
        );

        register_taxonomy('event_category', array('college_event'), $event_cat_args);

        // Academic Year Taxonomy (ภาคเรียน / ปีการศึกษา - เช่น 1/2569, 2/2569)
        $academic_year_labels = array(
            'name' => __('ภาคเรียน / ปีการศึกษา', 'project-core'),
            'singular_name' => __('ภาคเรียน / ปีการศึกษา', 'project-core'),
            'search_items' => __('ค้นหาภาคเรียน/ปีการศึกษา', 'project-core'),
            'all_items' => __('ภาคเรียน/ปีการศึกษาทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขภาคเรียน/ปีการศึกษา', 'project-core'),
            'update_item' => __('อัปเดตภาคเรียน/ปีการศึกษา', 'project-core'),
            'add_new_item' => __('เพิ่มภาคเรียน/ปีการศึกษาใหม่', 'project-core'),
            'new_item_name' => __('ชื่อภาคเรียน/ปีการศึกษาใหม่ (เช่น 1/2569 หรือ 2/2569)', 'project-core'),
            'menu_name' => __('ภาคเรียน / ปีการศึกษา', 'project-core'),
            'back_to_items' => __('← กลับไปหน้ารายการภาคเรียน/ปีการศึกษา', 'project-core'),
        );

        $academic_year_args = array(
            'hierarchical' => true,
            'labels' => $academic_year_labels,
            'show_ui' => true,
            'show_admin_column' => false, // Custom column rendered manually
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'academic-years',
        );

        register_taxonomy('academic_year', array('college_event'), $academic_year_args);

        // Curriculum Level Taxonomy (ระดับการศึกษา เช่น ปวช., ปวส.)
        $level_labels = array(
            'name' => __('ระดับการศึกษา (ปวช./ปวส.)', 'project-core'),
            'singular_name' => __('ระดับการศึกษา', 'project-core'),
            'search_items' => __('ค้นหาระดับการศึกษา', 'project-core'),
            'all_items' => __('ระดับการศึกษาทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขระดับการศึกษา', 'project-core'),
            'update_item' => __('อัปเดตระดับการศึกษา', 'project-core'),
            'add_new_item' => __('เพิ่มระดับการศึกษาใหม่', 'project-core'),
            'new_item_name' => __('ชื่อระดับการศึกษาใหม่', 'project-core'),
            'menu_name' => __('ระดับการศึกษา', 'project-core'),
        );

        $level_args = array(
            'hierarchical' => true,
            'labels' => $level_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'curriculum-levels',
        );

        register_taxonomy('curriculum_level', array('curriculum'), $level_args);

        // Curriculum Department Taxonomy (กลุ่มสาขาวิชา / ประเภทวิชา เช่น ช่างอุตสาหกรรม, บริหารธุรกิจ, เทคโนโลยีสารสนเทศ)
        $dept_labels = array(
            'name' => __('กลุ่มสาขาวิชา / ประเภทวิชา', 'project-core'),
            'singular_name' => __('กลุ่มสาขาวิชา', 'project-core'),
            'search_items' => __('ค้นหากลุ่มสาขาวิชา', 'project-core'),
            'all_items' => __('กลุ่มสาขาวิชาทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขกลุ่มสาขาวิชา', 'project-core'),
            'update_item' => __('อัปเดตกลุ่มสาขาวิชา', 'project-core'),
            'add_new_item' => __('เพิ่มกลุ่มสาขาวิชาใหม่', 'project-core'),
            'new_item_name' => __('ชื่อกลุ่มสาขาวิชาใหม่', 'project-core'),
            'menu_name' => __('กลุ่มสาขาวิชา', 'project-core'),
        );

        $dept_args = array(
            'hierarchical' => true,
            'labels' => $dept_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'curriculum-departments',
        );

        register_taxonomy('curriculum_department', array('curriculum'), $dept_args);

        // Personnel Category Taxonomy (กลุ่มบุคลากร เช่น ผู้บริหาร, คณาจารย์, ฝ่ายสนับสนุน)
        $personnel_cat_labels = array(
            'name' => __('กลุ่มบุคลากร', 'project-core'),
            'singular_name' => __('กลุ่มบุคลากร', 'project-core'),
            'search_items' => __('ค้นหากลุ่มบุคลากร', 'project-core'),
            'all_items' => __('กลุ่มบุคลากรทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขกลุ่มบุคลากร', 'project-core'),
            'update_item' => __('อัปเดตกลุ่มบุคลากร', 'project-core'),
            'add_new_item' => __('เพิ่มกลุ่มบุคลากรใหม่', 'project-core'),
            'new_item_name' => __('ชื่อกลุ่มบุคลากรใหม่', 'project-core'),
            'menu_name' => __('กลุ่มบุคลากร', 'project-core'),
        );

        $personnel_cat_args = array(
            'hierarchical' => true,
            'labels' => $personnel_cat_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'personnel-categories',
        );

        register_taxonomy('personnel_category', array('personnel'), $personnel_cat_args);

        // SAR Year Taxonomy (ปีการศึกษา)
        $sar_year_labels = array(
            'name' => __('ปีการศึกษา SAR', 'project-core'),
            'singular_name' => __('ปีการศึกษา SAR', 'project-core'),
            'search_items' => __('ค้นหาปีการศึกษา', 'project-core'),
            'all_items' => __('ปีการศึกษาทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขปีการศึกษา', 'project-core'),
            'update_item' => __('อัปเดตปีการศึกษา', 'project-core'),
            'add_new_item' => __('เพิ่มปีการศึกษาใหม่', 'project-core'),
            'new_item_name' => __('ชื่อปีการศึกษาใหม่ (เช่น 2568)', 'project-core'),
            'menu_name' => __('ปีการศึกษา', 'project-core'),
        );

        $sar_year_args = array(
            'hierarchical' => true,
            'labels' => $sar_year_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'sar-years',
        );

        register_taxonomy('sar_year', array('sar_report'), $sar_year_args);

        // Academic Year Taxonomy for Educational MIS
        $mis_year_labels = array(
            'name' => __('ปีการศึกษา / Academic Years', 'project-core'),
            'singular_name' => __('ปีการศึกษา', 'project-core'),
            'search_items' => __('ค้นหาปีการศึกษา', 'project-core'),
            'all_items' => __('ปีการศึกษาทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขปีการศึกษา', 'project-core'),
            'update_item' => __('อัปเดตปีการศึกษา', 'project-core'),
            'add_new_item' => __('เพิ่มปีการศึกษาใหม่', 'project-core'),
            'new_item_name' => __('ชื่อปีการศึกษาใหม่', 'project-core'),
            'menu_name' => __('ปีการศึกษา', 'project-core'),
        );

        $mis_year_args = array(
            'hierarchical' => true,
            'labels' => $mis_year_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'mis-academic-years',
        );

        register_taxonomy('mis_academic_year', array('education_mis'), $mis_year_args);

        // Download Category Taxonomy (หมวดหมู่เอกสารดาวน์โหลด)
        $download_cat_labels = array(
            'name' => __('หมวดหมู่ดาวน์โหลด', 'project-core'),
            'singular_name' => __('หมวดหมู่ดาวน์โหลด', 'project-core'),
            'search_items' => __('ค้นหาหมวดหมู่', 'project-core'),
            'all_items' => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขหมวดหมู่', 'project-core'),
            'update_item' => __('อัปเดตหมวดหมู่', 'project-core'),
            'add_new_item' => __('เพิ่มหมวดหมู่ใหม่', 'project-core'),
            'new_item_name' => __('ชื่อหมวดหมู่ใหม่', 'project-core'),
            'menu_name' => __('หมวดหมู่ดาวน์โหลด', 'project-core'),
        );

        $download_cat_args = array(
            'hierarchical' => true,
            'labels' => $download_cat_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'download-category'),
            'show_in_rest' => true,
            'rest_base' => 'download-categories',
        );

        register_taxonomy('download_category', array('download_file'), $download_cat_args);

        // Handbook Category Taxonomy (หมวดหมู่คู่มือและระเบียบการ)
        $handbook_cat_labels = array(
            'name' => __('หมวดหมู่คู่มือ & ระเบียบการ', 'project-core'),
            'singular_name' => __('หมวดหมู่คู่มือ', 'project-core'),
            'search_items' => __('ค้นหาหมวดหมู่', 'project-core'),
            'all_items' => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขหมวดหมู่', 'project-core'),
            'update_item' => __('อัปเดตหมวดหมู่', 'project-core'),
            'add_new_item' => __('เพิ่มหมวดหมู่ใหม่', 'project-core'),
            'new_item_name' => __('ชื่อหมวดหมู่ใหม่', 'project-core'),
            'menu_name' => __('หมวดหมู่คู่มือ', 'project-core'),
        );

        $handbook_cat_args = array(
            'hierarchical' => true,
            'labels' => $handbook_cat_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'handbook-category'),
            'show_in_rest' => true,
            'rest_base' => 'handbook-categories',
        );

        register_taxonomy('handbook_category', array('student_handbook'), $handbook_cat_args);

        // Open House Category Taxonomy
        $openhouse_cat_labels = array(
            'name' => __('หมวดหมู่กิจกรรม / การแข่งขัน', 'project-core'),
            'singular_name' => __('หมวดหมู่กิจกรรม', 'project-core'),
            'search_items' => __('ค้นหาหมวดหมู่', 'project-core'),
            'all_items' => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'edit_item' => __('แก้ไขหมวดหมู่', 'project-core'),
            'update_item' => __('อัปเดตหมวดหมู่', 'project-core'),
            'add_new_item' => __('เพิ่มหมวดหมู่ใหม่', 'project-core'),
            'new_item_name' => __('ชื่อหมวดหมู่ใหม่', 'project-core'),
            'menu_name' => __('หมวดหมู่กิจกรรม', 'project-core'),
        );

        $openhouse_cat_args = array(
            'hierarchical' => true,
            'labels' => $openhouse_cat_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'openhouse-category'),
            'show_in_rest' => true,
            'rest_base' => 'openhouse-categories',
        );

        register_taxonomy('openhouse_category', array('openhouse_activity'), $openhouse_cat_args);

        // Seed default terms if empty
        $this->seed_default_curriculum_terms();
        $this->seed_default_personnel_data();
        $this->seed_default_sar_data();
        $this->seed_default_education_mis_data();
        $this->seed_default_openhouse_terms();
    }

    /**
     * Define custom columns in Banners admin list
     */
    /**
     * Define custom columns in Banners admin list
     */
    public function set_banner_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['banner_handle'] = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเพื่อสลับลำดับ', 'project-core') . '"></span>';
        $new_columns['banner_order'] = __('ลำดับ', 'project-core');
        $new_columns['banner_thumbnail'] = __('รูปภาพแบนเนอร์ (1709x650)', 'project-core');
        $new_columns['title'] = __('ชื่อแบนเนอร์ / หัวข้อ', 'project-core');
        $new_columns['banner_target'] = __('ลิงก์ปลายทาง (Target URL)', 'project-core');
        $new_columns['banner_status'] = __('สถานะแสดงผล', 'project-core');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents
     */
    public function render_banner_columns($column, $post_id)
    {
        switch ($column) {
            case 'banner_handle':
                echo '<span class="dashicons dashicons-move banner-drag-handle" style="cursor: grab; color: #50575e; font-size: 20px; line-height: 28px;" title="คลิกค้างแล้วลากเพื่อสลับลำดับ"></span>';
                break;

            case 'banner_order':
                $order = (int) get_post_field('menu_order', $post_id);
                echo '<span class="banner-order-badge" style="display: inline-block; min-width: 32px; text-align: center; padding: 4px 8px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; font-weight: 700; color: #1d2327; font-size: 13px;">#' . esc_html($order) . '</span>';
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
     * Define custom columns in College Events admin list
     */
    public function set_event_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('ชื่องาน / กิจกรรม', 'project-core');
        $new_columns['academic_year'] = __('ภาคเรียน / ปีการศึกษา', 'project-core');
        $new_columns['event_date'] = __('วันที่จัดกิจกรรม', 'project-core');
        $new_columns['event_time'] = __('เวลา', 'project-core');
        $new_columns['event_location'] = __('สถานที่', 'project-core');
        $new_columns['taxonomy-event_category'] = __('หมวดหมู่', 'project-core');
        $new_columns['is_featured'] = __('ไฮไลท์', 'project-core');
        $new_columns['date'] = __('วันที่สร้าง', 'project-core');
        return $new_columns;
    }

    /**
     * Render custom columns in College Events admin list
     */
    public function render_event_columns($column, $post_id)
    {
        switch ($column) {
            case 'academic_year':
                $ay_terms = get_the_terms($post_id, 'academic_year');
                if ($ay_terms && !is_wp_error($ay_terms) && !empty($ay_terms)) {
                    $ay = $ay_terms[0]->name;
                } else {
                    $ay = get_post_meta($post_id, 'academic_year', true);
                    if (empty($ay)) {
                        $start = get_post_meta($post_id, 'event_start_date', true);
                        if (!empty($start)) {
                            $parts = explode('-', $start);
                            $y = (int) $parts[0];
                            $m = isset($parts[1]) ? (int) $parts[1] : 6;
                            $ay_calc = ($m < 5) ? ($y - 1) : $y;
                            $th_year = $ay_calc + 543;
                            $term_num = ($m >= 5 && $m <= 10) ? '1' : '2';
                            $ay = "{$term_num}/{$th_year}";
                        } else {
                            $ay = '1/2569';
                        }
                    }
                }
                $lbl = strpos($ay, '/') !== false ? 'ภาคเรียน ' . $ay : 'ปี ' . $ay;
                echo '<span style="display:inline-block;padding:2px 8px;border-radius:4px;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:12px;">' . esc_html($lbl) . '</span>';
                break;

            case 'event_date':
                $start = get_post_meta($post_id, 'event_start_date', true);
                $end = get_post_meta($post_id, 'event_end_date', true);
                if (!empty($start)) {
                    $str = esc_html($start);
                    if (!empty($end) && $end !== $start) {
                        $str .= ' ถึง ' . esc_html($end);
                    }
                    echo '<strong style="color: #0047BA;">' . $str . '</strong>';
                } else {
                    echo '<span style="color: #a7aaad;">—</span>';
                }
                break;

            case 'event_time':
                $time = get_post_meta($post_id, 'event_time', true);
                echo !empty($time) ? esc_html($time) : '<span style="color: #a7aaad;">—</span>';
                break;

            case 'event_location':
                $loc = get_post_meta($post_id, 'event_location', true);
                echo !empty($loc) ? esc_html($loc) : '<span style="color: #a7aaad;">—</span>';
                break;

            case 'is_featured':
                $feat = get_post_meta($post_id, 'is_featured', true);
                if ($feat === '1' || $feat === 1 || $feat === true) {
                    echo '<span style="display:inline-block;padding:2px 8px;border-radius:4px;background:#fef3c7;color:#b45309;font-weight:700;font-size:12px;">★ ไฮไลท์</span>';
                } else {
                    echo '<span style="color: #a7aaad;">—</span>';
                }
                break;
        }
    }

    /**
     * Default sort order by menu_order ASC in WordPress Admin Banners table
     */
    public function admin_banner_sort_order($query)
    {
        if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'banner') {
            if (!isset($_GET['orderby'])) {
                $query->set('orderby', 'menu_order');
                $query->set('order', 'ASC');
            }
        }
    }

    /**
     * Enqueue jQuery UI Sortable in Banners list view
     */
    public function enqueue_banner_list_scripts($hook)
    {
        if ($hook === 'edit.php') {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'banner') {
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-sortable');
            }
        }
    }

    /**
     * Output inline styles and Drag & Drop sortable script in admin footer
     */
    public function render_banner_reorder_script()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edit-banner') {
            return;
        }
        $nonce = wp_create_nonce('project_core_reorder_nonce');
        ?>
        <style>
            .wp-list-table tbody#the-list tr {
                cursor: grab;
                transition: background 0.15s ease;
            }

            .wp-list-table tbody#the-list tr:hover {
                background-color: #f0f6fc !important;
            }

            .wp-list-table tbody#the-list tr.ui-sortable-helper {
                background-color: #ffffff !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22) !important;
                display: table !important;
                cursor: grabbing !important;
                border: 2px solid #0047BA !important;
            }

            .wp-list-table tbody#the-list tr.ui-sortable-placeholder {
                background-color: #e7f3ff !important;
                visibility: visible !important;
                outline: 2px dashed #0047BA !important;
                height: 55px !important;
            }

            .banner-drag-handle {
                cursor: grab;
                color: #50575e;
                font-size: 20px;
                display: inline-block;
                vertical-align: middle;
            }

            .banner-drag-handle:active,
            .wp-list-table tbody#the-list tr:active {
                cursor: grabbing !important;
            }

            .column-banner_handle {
                width: 32px !important;
                text-align: center;
                padding-left: 2px !important;
                padding-right: 2px !important;
            }

            .column-banner_order {
                width: 70px !important;
                text-align: center;
            }

            .column-banner_thumbnail {
                width: 155px !important;
            }

            #banner-reorder-notice {
                display: none;
                position: fixed;
                top: 45px;
                right: 30px;
                z-index: 999999;
                padding: 12px 24px;
                background: #007017;
                color: #ffffff;
                font-weight: 600;
                border-radius: 6px;
                box-shadow: 0 6px 22px rgba(0, 0, 0, 0.25);
                font-size: 14px;
            }
        </style>
        <div id="banner-reorder-notice">✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว</div>
        <script>
            jQuery(function ($) {
                var $tbody = $('#the-list');
                if (!$tbody.length) return;

                function showNotice(msg) {
                    var $notice = $('#banner-reorder-notice');
                    $notice.text(msg || '✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                    setTimeout(function () {
                        $notice.fadeOut(400);
                    }, 2500);
                }

                function updateOrderNumbers() {
                    $tbody.find('tr').each(function (index) {
                        $(this).find('.banner-order-badge').text('#' + (index + 1));
                    });
                }

                // Initialize jQuery UI sortable on table rows
                $tbody.sortable({
                    items: 'tr',
                    handle: '.banner-drag-handle, td',
                    cancel: 'a, button, input',
                    axis: 'y',
                    cursor: 'grabbing',
                    opacity: 0.9,
                    placeholder: 'ui-sortable-placeholder',
                    helper: function (e, tr) {
                        var $originals = tr.children();
                        var $helper = tr.clone();
                        $helper.children().each(function (index) {
                            $(this).width($originals.eq(index).width());
                        });
                        return $helper;
                    },
                    update: function () {
                        updateOrderNumbers();
                        var postIds = [];
                        $tbody.find('tr').each(function () {
                            var rowId = $(this).attr('id');
                            if (rowId && rowId.indexOf('post-') === 0) {
                                var pid = rowId.replace('post-', '');
                                postIds.push(pid);
                            }
                        });

                        if (!postIds.length) return;

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'project_core_reorder_banners',
                                nonce: '<?php echo esc_js($nonce); ?>',
                                post_ids: postIds
                            },
                            success: function (res) {
                                if (res.success) {
                                    showNotice('✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว (' + postIds.length + ' แบนเนอร์)');
                                }
                            }
                        });
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * AJAX endpoint: reorder multiple banners from drag and drop
     */
    public function ajax_reorder_banners()
    {
        check_ajax_referer('project_core_reorder_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;
        $post_ids = isset($_POST['post_ids']) ? (array) $_POST['post_ids'] : array();
        foreach ($post_ids as $order => $post_id) {
            $post_id = absint($post_id);
            if ($post_id > 0) {
                $wpdb->update(
                    $wpdb->posts,
                    array('menu_order' => $order + 1),
                    array('ID' => $post_id),
                    array('%d'),
                    array('%d')
                );
                clean_post_cache($post_id);
            }
        }

        wp_send_json_success(array('message' => 'Reordered successfully'));
    }

    /**
     * Add category and academic year dropdown filter above College Events admin list
     */
    public function filter_events_by_category($post_type, $which)
    {
        if ($post_type !== 'college_event' || $which !== 'top') {
            return;
        }

        // 1. Academic Year filter (from academic_year taxonomy)
        $selected_year = isset($_GET['filter_academic_year']) ? sanitize_text_field($_GET['filter_academic_year']) : '';
        $year_terms = get_terms(array(
            'taxonomy' => 'academic_year',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'DESC',
        ));

        echo '<select name="filter_academic_year" id="filter_academic_year" style="margin-right: 6px;">';
        echo '<option value="">' . esc_html__('— ทุกภาคเรียน / ปีการศึกษา —', 'project-core') . '</option>';
        if (!is_wp_error($year_terms) && !empty($year_terms)) {
            foreach ($year_terms as $yt) {
                $lbl = strpos($yt->name, '/') !== false ? 'ภาคเรียนที่ ' . $yt->name : 'ปีการศึกษา ' . $yt->name;
                echo '<option value="' . esc_attr($yt->slug) . '" ' . selected($selected_year, $yt->slug, false) . '>' . esc_html($lbl) . ' (' . $yt->count . ')</option>';
            }
        }
        echo '</select>';

        // 2. Event Category filter
        $selected = isset($_GET['event_category']) ? sanitize_text_field($_GET['event_category']) : '';

        wp_dropdown_categories(array(
            'show_option_all' => __('— หมวดหมู่กิจกรรมทั้งหมด —', 'project-core'),
            'taxonomy' => 'event_category',
            'name' => 'event_category',
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => true,
            'depth' => 3,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));
    }

    /**
     * Filter College Events by taxonomy and academic year in admin query
     */
    public function admin_event_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'college_event') {
            $tax_query = $query->get('tax_query') ?: array();

            // Category filter
            if (isset($_GET['event_category']) && !empty($_GET['event_category']) && $_GET['event_category'] !== '0') {
                $slug = sanitize_text_field($_GET['event_category']);
                $tax_query[] = array(
                    'taxonomy' => 'event_category',
                    'field' => 'slug',
                    'terms' => $slug,
                );
            }

            // Academic Year taxonomy filter
            if (isset($_GET['filter_academic_year']) && !empty($_GET['filter_academic_year'])) {
                $ay_slug = sanitize_text_field($_GET['filter_academic_year']);
                $tax_query[] = array(
                    'taxonomy' => 'academic_year',
                    'field' => 'slug',
                    'terms' => $ay_slug,
                );
            }

            if (!empty($tax_query)) {
                $query->set('tax_query', $tax_query);
            }
        }
    }

    /**
     * Seed initial default levels and departments if not exist
     */
    public function seed_default_curriculum_terms()
    {
        if (!taxonomy_exists('curriculum_level') || !taxonomy_exists('curriculum_department')) {
            return;
        }

        // Default levels: ปวช. and ปวส.
        $levels = array(
            'vocational' => 'ระดับประกาศนียบัตรวิชาชีพ (ปวช.)',
            'high-vocational' => 'ระดับประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.)',
        );

        foreach ($levels as $slug => $name) {
            if (!term_exists($slug, 'curriculum_level')) {
                wp_insert_term($name, 'curriculum_level', array('slug' => $slug));
            }
        }

        // Default departments
        $departments = array(
            'industrial' => 'ช่างอุตสาหกรรม',
            'business' => 'บริหารธุรกิจและพาณิชยกรรม',
            'information-technology' => 'เทคโนโลยีสารสนเทศและการสื่อสาร',
        );

        foreach ($departments as $slug => $name) {
            if (!term_exists($slug, 'curriculum_department')) {
                wp_insert_term($name, 'curriculum_department', array('slug' => $slug));
            }
        }
    }

    /**
     * Custom columns for Curriculum admin list
     */
    public function set_curriculum_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['curriculum_thumb'] = __('รูปภาพ', 'project-core');
        $new_columns['title'] = __('ชื่อหลักสูตร / สาขาวิชา', 'project-core');
        $new_columns['curriculum_level'] = __('ระดับการศึกษา', 'project-core');
        $new_columns['curriculum_department'] = __('กลุ่มสาขาวิชา', 'project-core');
        $new_columns['curriculum_order'] = __('ลำดับ', 'project-core');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for Curriculum
     */
    public function render_curriculum_columns($column, $post_id)
    {
        switch ($column) {
            case 'curriculum_thumb':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50), array('style' => 'width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #ccd0d4;'));
                } else {
                    echo '<div style="width: 50px; height: 50px; background: #f0f0f1; border-radius: 6px; border: 1px dashed #ccd0d4; display: flex; align-items: center; justify-content: center; color: #a7aaad;"><span class="dashicons dashicons-welcome-learn-more" style="font-size: 20px; line-height: 20px;"></span></div>';
                }
                break;

            case 'curriculum_level':
                $terms = get_the_terms($post_id, 'curriculum_level');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $out = array();
                    foreach ($terms as $t) {
                        $badge_color = ($t->slug === 'high-vocational') ? '#ea580c' : '#0047ba';
                        $out[] = '<span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; color: #fff; background: ' . esc_attr($badge_color) . ';">' . esc_html($t->name) . '</span>';
                    }
                    echo implode(' ', $out);
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'curriculum_department':
                $terms = get_the_terms($post_id, 'curriculum_department');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $out = array();
                    foreach ($terms as $t) {
                        $out[] = esc_html($t->name);
                    }
                    echo implode(', ', $out);
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'curriculum_order':
                $post = get_post($post_id);
                echo esc_html($post->menu_order);
                break;
        }
    }

    /**
     * Admin filters for Curriculum list
     */
    public function filter_curriculums($post_type, $which)
    {
        if ($post_type !== 'curriculum') {
            return;
        }

        // 1. Level Filter
        $selected_level = isset($_GET['curriculum_level']) ? sanitize_text_field($_GET['curriculum_level']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => __('— เลือกระดับการศึกษา (ทั้งหมด) —', 'project-core'),
            'taxonomy' => 'curriculum_level',
            'name' => 'curriculum_level',
            'orderby' => 'name',
            'selected' => $selected_level,
            'hierarchical' => true,
            'depth' => 2,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));

        // 2. Department Filter
        $selected_dept = isset($_GET['curriculum_department']) ? sanitize_text_field($_GET['curriculum_department']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => __('— เลือกกลุ่มสาขาวิชา (ทั้งหมด) —', 'project-core'),
            'taxonomy' => 'curriculum_department',
            'name' => 'curriculum_department',
            'orderby' => 'name',
            'selected' => $selected_dept,
            'hierarchical' => true,
            'depth' => 2,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));
    }

    /**
     * Filter Curriculum admin query by level and department
     */
    public function admin_curriculum_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'curriculum') {
            $tax_query = $query->get('tax_query') ?: array();

            if (isset($_GET['curriculum_level']) && !empty($_GET['curriculum_level']) && $_GET['curriculum_level'] !== '0') {
                $slug = sanitize_text_field($_GET['curriculum_level']);
                $tax_query[] = array(
                    'taxonomy' => 'curriculum_level',
                    'field' => 'slug',
                    'terms' => $slug,
                );
            }

            if (isset($_GET['curriculum_department']) && !empty($_GET['curriculum_department']) && $_GET['curriculum_department'] !== '0') {
                $slug = sanitize_text_field($_GET['curriculum_department']);
                $tax_query[] = array(
                    'taxonomy' => 'curriculum_department',
                    'field' => 'slug',
                    'terms' => $slug,
                );
            }

            if (!empty($tax_query)) {
                $query->set('tax_query', $tax_query);
            }
        }
    }

    /**
     * Seed default personnel terms and sample data
     */
    public function seed_default_personnel_data()
    {
        if (!taxonomy_exists('personnel_category')) {
            return;
        }

        // Only run initial seeding once
        if (get_option('project_core_personnel_initial_seed_done')) {
            return;
        }

        // 11 College Categories matching organizational structure
        $categories = array(
            'advisory' => array('name' => 'ที่ปรึกษาสถานศึกษา', 'desc' => 'คณะที่ปรึกษาและผู้รับใบอนุญาตวิทยาลัย', 'order' => 1),
            'executive' => array('name' => 'ผู้บริหารสถานศึกษา', 'desc' => 'คณะผู้บริหารและผู้อำนวยการวิทยาลัย', 'order' => 2),
            'general' => array('name' => 'สาขาวิชาสามัญ', 'desc' => 'คณาจารย์หมวดวิชาสามัญสัมพันธ์และภาษา', 'order' => 3),
            'accounting' => array('name' => 'สาขาวิชาการบัญชี', 'desc' => 'คณาจารย์สาขาวิชาการบัญชี', 'order' => 4),
            'digital-business' => array('name' => 'สาขาเทคโนโลยีธุรกิจดิจิทัล', 'desc' => 'คณาจารย์สาขาเทคโนโลยีธุรกิจดิจิทัลและคอมพิวเตอร์', 'order' => 5),
            'marketing' => array('name' => 'สาขาวิชาการตลาด', 'desc' => 'คณาจารย์สาขาวิชาการตลาดดิจิทัล', 'order' => 6),
            'logistics' => array('name' => 'สาขาวิชาโลจิสติกส์และซัพพลายเชน', 'desc' => 'คณาจารย์สาขาวิชาการจัดการโลจิสติกส์', 'order' => 7),
            'electrical' => array('name' => 'สาขาอุตสาหกรรมไฟฟ้า', 'desc' => 'คณาจารย์สาขาวิชาช่างไฟฟ้ากำลัง', 'order' => 8),
            'mechanical' => array('name' => 'สาขาอุตสาหกรรมเทคนิคเครื่องกล', 'desc' => 'คณาจารย์สาขาวิชาช่างยนต์และเทคนิคเครื่องกล', 'order' => 9),
            'mechatronics' => array('name' => 'สาขาเมคาทรอนิกส์และหุ่นยนต์', 'desc' => 'คณาจารย์สาขาวิชาเมคาทรอนิกส์และระบบอัตโนมัติ', 'order' => 10),
            'support' => array('name' => 'บุคลากรสายสนับสนุน', 'desc' => 'บุคลากรฝ่ายสนับสนุน เจ้าหน้าที่ธุรการ ทะเบียน และการเงิน', 'order' => 11),
        );

        foreach ($categories as $slug => $data) {
            $term = get_term_by('slug', $slug, 'personnel_category');
            if (!$term) {
                $inserted = wp_insert_term($data['name'], 'personnel_category', array(
                    'slug' => $slug,
                    'description' => $data['desc'],
                ));
                if (!is_wp_error($inserted) && isset($inserted['term_id'])) {
                    update_term_meta($inserted['term_id'], 'category_order', $data['order']);
                }
            } else {
                update_term_meta($term->term_id, 'category_order', $data['order']);
            }
        }

        // Seed sample staff ONLY if personnel table is completely empty
        $count = wp_count_posts('personnel');
        $total_posts = isset($count->publish) ? (int) $count->publish : 0;
        if ($total_posts === 0) {
            $sample_data = array(
                array(
                    'title' => 'นางหนึ่งฤทัย อินทเศียร',
                    'nickname' => 'อาจารย์หนึ่ง',
                    'category' => 'advisory',
                    'order' => 1,
                    'position' => 'ผู้รับใบอนุญาตฯ',
                    'department' => 'ที่ปรึกษาสถานศึกษา',
                    'education' => 'ปริญญาโท บริหารการศึกษา',
                    'email' => 'nuengruthai@technosriracha.ac.th',
                    'phone' => '038-352440 ต่อ 100',
                ),
                array(
                    'title' => 'นางปฤษณา บุญจิตสิทธิ์ศักดิ์',
                    'nickname' => 'อาจารย์แม่',
                    'category' => 'advisory',
                    'order' => 2,
                    'position' => 'ที่ปรึกษาวิทยาลัยฯ',
                    'department' => 'ที่ปรึกษาสถานศึกษา',
                    'education' => 'ปริญญาโท',
                    'email' => 'advisor@technosriracha.ac.th',
                    'phone' => '038-352440',
                ),
                array(
                    'title' => 'นางบุญนภัส รินทร์คำ',
                    'nickname' => '',
                    'category' => 'executive',
                    'order' => 3,
                    'position' => 'ผู้อำนวยการวิทยาลัยเทคโนโลยีศรีราชา',
                    'department' => 'ผู้บริหารสถานศึกษา',
                    'education' => 'ปริญญาโท บริหารการศึกษา',
                    'email' => 'director@technosriracha.ac.th',
                    'phone' => '038-352440',
                ),
                array(
                    'title' => 'นายเขมวิทย์ บุญจิตสิทธิ์ศักดิ์',
                    'nickname' => '',
                    'category' => 'executive',
                    'order' => 4,
                    'position' => 'ผู้จัดการวิทยาลัยเทคโนโลยีศรีราชา',
                    'department' => 'ผู้บริหารสถานศึกษา',
                    'education' => 'ปริญญาโท บริหารธุรกิจ',
                    'email' => 'manager@technosriracha.ac.th',
                    'phone' => '038-352440 ต่อ 101',
                ),
                array(
                    'title' => 'นายอานนท์ สุขสวัสดิ์',
                    'nickname' => '',
                    'category' => 'executive',
                    'order' => 5,
                    'position' => 'รองผู้อำนวยการฝ่ายวิชาการ',
                    'department' => 'ผู้บริหารสถานศึกษา',
                    'education' => 'ปริญญาโท ครุศาสตร์อุตสาหกรรมมหาบัณฑิต',
                    'email' => 'academic.vp@technosriracha.ac.th',
                    'phone' => '038-352440 ต่อ 103',
                ),
            );

            foreach ($sample_data as $data) {
                $existing_posts = get_posts(array(
                    'post_type' => 'personnel',
                    'title' => $data['title'],
                    'post_status' => 'all',
                    'posts_per_page' => 1,
                    'no_found_rows' => true,
                    'ignore_sticky_posts' => true,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                ));
                $existing = !empty($existing_posts) ? $existing_posts[0] : null;
                // Never overwrite existing data
                if ($existing) {
                    continue;
                }

                $pid = wp_insert_post(array(
                    'post_title' => $data['title'],
                    'post_type' => 'personnel',
                    'post_status' => 'publish',
                    'menu_order' => $data['order'],
                ));
                if ($pid && !is_wp_error($pid)) {
                    wp_set_object_terms($pid, $data['category'], 'personnel_category');
                    update_post_meta($pid, 'personnel_position', $data['position']);
                    update_post_meta($pid, 'personnel_nickname', $data['nickname']);
                    update_post_meta($pid, 'personnel_department', $data['department']);
                    update_post_meta($pid, 'personnel_education', $data['education']);
                    update_post_meta($pid, 'personnel_email', $data['email']);
                    update_post_meta($pid, 'personnel_phone', $data['phone']);
                }
            }
        }

        update_option('project_core_personnel_initial_seed_done', 1);
    }

    /**
     * Columns for Personnel admin list
     */
    public function set_personnel_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['personnel_handle'] = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเพื่อสลับลำดับ', 'project-core') . '"></span>';
        $new_columns['personnel_order'] = __('ลำดับ', 'project-core');
        $new_columns['personnel_photo'] = __('รูปถ่าย', 'project-core');
        $new_columns['title'] = __('ชื่อ - สกุล', 'project-core');
        $new_columns['personnel_pos'] = __('ตำแหน่ง', 'project-core');
        $new_columns['personnel_dept'] = __('ฝ่าย / แผนกวิชา', 'project-core');
        $new_columns['taxonomy-personnel_category'] = __('กลุ่มบุคลากร', 'project-core');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for Personnel
     */
    public function render_personnel_columns($column, $post_id)
    {
        switch ($column) {
            case 'personnel_handle':
                echo '<span class="dashicons dashicons-move personnel-drag-handle" style="cursor: grab; color: #50575e; font-size: 20px; line-height: 28px;" title="คลิกค้างแล้วลากเพื่อสลับลำดับ"></span>';
                break;

            case 'personnel_order':
                $order = (int) get_post_field('menu_order', $post_id);
                echo '<span class="personnel-order-badge" style="display: inline-block; min-width: 32px; text-align: center; padding: 4px 8px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; font-weight: 700; color: #1d2327; font-size: 13px;">#' . esc_html($order) . '</span>';
                break;

            case 'personnel_photo':
                $img = get_the_post_thumbnail_url($post_id, 'thumbnail');
                if (!empty($img)) {
                    echo '<div style="width: 44px; height: 54px; overflow: hidden; border-radius: 6px; border: 1px solid #c3c4c7; background: #f0f0f1;">';
                    echo '<img src="' . esc_url($img) . '" style="width: 100%; height: 100%; object-fit: cover;" alt="" />';
                    echo '</div>';
                } else {
                    echo '<div style="width: 44px; height: 54px; border-radius: 6px; border: 1px dashed #c3c4c7; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #94a3b8;"><span class="dashicons dashicons-admin-users" style="font-size: 24px; width: 24px; height: 24px;"></span></div>';
                }
                break;

            case 'personnel_pos':
                $pos = get_post_meta($post_id, 'personnel_position', true);
                if (!empty($pos)) {
                    echo '<strong style="color: #0047BA;">' . esc_html($pos) . '</strong>';
                } else {
                    echo '<span style="color: #a7aaad;">—</span>';
                }
                break;

            case 'personnel_dept':
                $dept = get_post_meta($post_id, 'personnel_department', true);
                echo !empty($dept) ? esc_html($dept) : '<span style="color: #a7aaad;">—</span>';
                break;
        }
    }

    /**
     * Default sort order by menu_order ASC in Personnel table
     */
    public function admin_personnel_sort_order($query)
    {
        if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'personnel') {
            if (!isset($_GET['orderby'])) {
                $query->set('orderby', 'menu_order');
                $query->set('order', 'ASC');
            }
        }
    }

    /**
     * Default sort order by category_order (NUMERIC) ASC in personnel_category admin table
     */
    public function admin_personnel_category_sort_order($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit-tags.php') {
            $taxonomies = isset($query->query_vars['taxonomy']) ? (array) $query->query_vars['taxonomy'] : array();
            if (in_array('personnel_category', $taxonomies, true)) {
                if (empty($_GET['orderby'])) {
                    $query->query_vars['meta_key'] = 'category_order';
                    $query->query_vars['orderby'] = 'meta_value_num';
                    $query->query_vars['order'] = 'ASC';
                }
            }
        }
    }

    /**
     * Filter Personnel by category in admin table
     */
    public function filter_personnel_by_category($post_type, $which)
    {
        if ($post_type !== 'personnel') {
            return;
        }

        $selected = isset($_GET['personnel_category']) ? sanitize_text_field($_GET['personnel_category']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => '— กลุ่มบุคลากรทั้งหมด —',
            'taxonomy' => 'personnel_category',
            'name' => 'personnel_category',
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => true,
            'depth' => 2,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));
    }

    /**
     * Filter Personnel query by category in admin
     */
    public function admin_personnel_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'personnel') {
            if (isset($_GET['personnel_category']) && !empty($_GET['personnel_category']) && $_GET['personnel_category'] !== '0') {
                $tax_query = $query->get('tax_query') ?: array();
                $tax_query[] = array(
                    'taxonomy' => 'personnel_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_GET['personnel_category']),
                );
                $query->set('tax_query', $tax_query);
            }
        }
    }

    /**
     * Enqueue jQuery UI Sortable in Personnel list and Personnel Category taxonomy view
     */
    public function enqueue_personnel_list_scripts($hook)
    {
        if ($hook === 'edit.php') {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === 'personnel') {
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-sortable');
            }
        } elseif ($hook === 'edit-tags.php') {
            $screen = get_current_screen();
            if ($screen && $screen->taxonomy === 'personnel_category') {
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-sortable');
            }
        } elseif (isset($_GET['page']) && $_GET['page'] === 'personnel-reorder') {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    /**
     * Render inline styles and drag & drop reorder script for Personnel & Categories
     */
    public function render_personnel_reorder_script()
    {
        $screen = get_current_screen();
        if (!$screen)
            return;

        // 1. Reordering Personnel posts
        if ($screen->id === 'edit-personnel') {
            $nonce = wp_create_nonce('project_core_personnel_reorder_nonce');
            ?>
            <style>
                .wp-list-table tbody#the-list tr {
                    cursor: grab;
                    transition: background 0.15s ease;
                }

                .wp-list-table tbody#the-list tr:hover {
                    background-color: #f0f6fc !important;
                }

                .wp-list-table tbody#the-list tr.ui-sortable-helper {
                    background-color: #ffffff !important;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22) !important;
                    display: table !important;
                    cursor: grabbing !important;
                    border: 2px solid #0047BA !important;
                }

                .wp-list-table tbody#the-list tr.ui-sortable-placeholder {
                    background-color: #e7f3ff !important;
                    visibility: visible !important;
                    outline: 2px dashed #0047BA !important;
                    height: 55px !important;
                }

                .personnel-drag-handle {
                    cursor: grab;
                    color: #50575e;
                    font-size: 20px;
                    display: inline-block;
                    vertical-align: middle;
                }

                .column-personnel_handle {
                    width: 32px !important;
                    text-align: center;
                }

                .column-personnel_order {
                    width: 70px !important;
                    text-align: center;
                }

                .column-personnel_photo {
                    width: 60px !important;
                    text-align: center;
                }

                #personnel-reorder-notice {
                    display: none;
                    position: fixed;
                    top: 45px;
                    right: 30px;
                    z-index: 999999;
                    padding: 12px 24px;
                    background: #007017;
                    color: #ffffff;
                    font-weight: 600;
                    border-radius: 6px;
                    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.25);
                    font-size: 14px;
                }

                #btn-personnel-dept-reorder .dashicons {
                    font-size: 14px;
                    width: 14px;
                    height: 14px;
                    line-height: 1;
                    vertical-align: -1px;
                    margin-right: 3px;
                }
            </style>
            <div id="personnel-reorder-notice">✓ บันทึกลำดับบุคลากรเรียบร้อยแล้ว</div>
            <script>
                jQuery(function ($) {
                    // Add quick link to "จัดลำดับตามฝ่าย" next to "เพิ่มบุคลากรใหม่"
                    var $addNew = $('a.page-title-action');
                    if ($addNew.length && !$('#btn-personnel-dept-reorder').length) {
                        $addNew.after('<a id="btn-personnel-dept-reorder" href="<?php echo esc_url(admin_url('edit.php?post_type=personnel&page=personnel-reorder')); ?>" class="page-title-action"><span class="dashicons dashicons-sort"></span><?php esc_html_e('จัดลำดับตามฝ่าย', 'project-core'); ?></a>');
                    }

                    var $tbody = $('#the-list');
                    if (!$tbody.length) return;
                    function showNotice(msg) {
                        $('#personnel-reorder-notice').text(msg || '✓ บันทึกลำดับบุคลากรเรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                        setTimeout(function () { $('#personnel-reorder-notice').fadeOut(400); }, 2500);
                    }
                    function updateOrderNumbers() {
                        $tbody.find('tr').each(function (index) {
                            $(this).find('.personnel-order-badge').text('#' + (index + 1));
                        });
                    }
                    $tbody.sortable({
                        items: 'tr',
                        handle: '.personnel-drag-handle, td',
                        cancel: 'a, button, input',
                        axis: 'y',
                        cursor: 'grabbing',
                        opacity: 0.9,
                        placeholder: 'ui-sortable-placeholder',
                        helper: function (e, tr) {
                            var $originals = tr.children();
                            var $helper = tr.clone();
                            $helper.children().each(function (index) { $(this).width($originals.eq(index).width()); });
                            return $helper;
                        },
                        update: function () {
                            updateOrderNumbers();
                            var postIds = [];
                            $tbody.find('tr').each(function () {
                                var rowId = $(this).attr('id');
                                if (rowId && rowId.indexOf('post-') === 0) postIds.push(rowId.replace('post-', ''));
                            });
                            if (!postIds.length) return;
                            var catSlug = new URLSearchParams(window.location.search).get('personnel_category') || '';
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'project_core_reorder_personnel',
                                    nonce: '<?php echo esc_js($nonce); ?>',
                                    post_ids: postIds,
                                    cat_slug: catSlug
                                },
                                success: function (res) {
                                    if (res.success) showNotice('✓ บันทึกลำดับบุคลากรเรียบร้อยแล้ว (' + postIds.length + ' ท่าน)');
                                }
                            });
                        }
                    });
                });
            </script>
            <?php
        }

        // 2. Reordering Personnel Categories
        if ($screen->taxonomy === 'personnel_category') {
            $cat_nonce = wp_create_nonce('project_core_personnel_cat_nonce');
            ?>
            <style>
                .wp-list-table tbody#the-list tr {
                    cursor: grab;
                    transition: background 0.15s ease;
                }

                .wp-list-table tbody#the-list tr:hover {
                    background-color: #f0f6fc !important;
                }

                .wp-list-table tbody#the-list tr.ui-sortable-helper {
                    background-color: #ffffff !important;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22) !important;
                    display: table !important;
                    cursor: grabbing !important;
                    border: 2px solid #0284c7 !important;
                }

                .wp-list-table tbody#the-list tr.ui-sortable-placeholder {
                    background-color: #e0f2fe !important;
                    visibility: visible !important;
                    outline: 2px dashed #0284c7 !important;
                    height: 45px !important;
                }

                .category-drag-handle {
                    cursor: grab;
                    color: #50575e;
                    font-size: 20px;
                    display: inline-block;
                    vertical-align: middle;
                }

                .column-category_handle {
                    width: 32px !important;
                    text-align: center;
                }

                .column-category_order {
                    width: 65px !important;
                    text-align: center;
                }

                #personnel-cat-reorder-notice {
                    display: none;
                    position: fixed;
                    top: 45px;
                    right: 30px;
                    z-index: 999999;
                    padding: 12px 24px;
                    background: #0284c7;
                    color: #ffffff;
                    font-weight: 600;
                    border-radius: 6px;
                    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.25);
                    font-size: 14px;
                }
            </style>
            <div id="personnel-cat-reorder-notice">✓ บันทึกลำดับกลุ่มบุคลากรเรียบร้อยแล้ว</div>
            <script>
                jQuery(function ($) {
                    var $tbody = $('#the-list');
                    if (!$tbody.length) return;
                    function showNotice(msg) {
                        $('#personnel-cat-reorder-notice').text(msg || '✓ บันทึกลำดับกลุ่มบุคลากรเรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                        setTimeout(function () { $('#personnel-cat-reorder-notice').fadeOut(400); }, 2500);
                    }
                    function updateCatOrderNumbers() {
                        $tbody.find('tr').each(function (index) {
                            $(this).find('.category-order-badge').text('#' + (index + 1));
                        });
                    }
                    $tbody.sortable({
                        items: 'tr',
                        handle: '.category-drag-handle, td',
                        cancel: 'a, button, input',
                        axis: 'y',
                        cursor: 'grabbing',
                        opacity: 0.9,
                        placeholder: 'ui-sortable-placeholder',
                        helper: function (e, tr) {
                            var $originals = tr.children();
                            var $helper = tr.clone();
                            $helper.children().each(function (index) { $(this).width($originals.eq(index).width()); });
                            return $helper;
                        },
                        update: function () {
                            updateCatOrderNumbers();
                            var termIds = [];
                            $tbody.find('tr').each(function () {
                                var rowId = $(this).attr('id');
                                if (rowId && rowId.indexOf('tag-') === 0) termIds.push(rowId.replace('tag-', ''));
                            });
                            if (!termIds.length) return;
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'project_core_reorder_personnel_categories',
                                    nonce: '<?php echo esc_js($cat_nonce); ?>',
                                    term_ids: termIds
                                },
                                success: function (res) {
                                    if (res.success) showNotice('✓ บันทึกลำดับกลุ่มเรียบร้อยแล้ว (' + termIds.length + ' กลุ่ม)');
                                }
                            });
                        }
                    });
                });
            </script>
            <?php
        }
    }

    /**
     * AJAX handler to save personnel menu_order
     */
    /**
     * Register Personnel submenu for Department Reordering
     */
    public function register_personnel_admin_menus()
    {
        add_submenu_page(
            'edit.php?post_type=personnel',
            __('จัดลำดับบุคลากรตามฝ่าย', 'project-core'),
            __('จัดลำดับตามฝ่าย', 'project-core'),
            'edit_others_posts',
            'personnel-reorder',
            array($this, 'render_personnel_reorder_page')
        );
    }

    /**
     * AJAX handler to save personnel menu_order (enhanced for category filter offset)
     */
    public function ajax_reorder_personnel()
    {
        check_ajax_referer('project_core_personnel_reorder_nonce', 'nonce');

        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : array();
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'No post IDs received.'));
        }

        $cat_slug = isset($_POST['cat_slug']) ? sanitize_text_field($_POST['cat_slug']) : '';
        $cat_offset = 0;
        if (!empty($cat_slug)) {
            $term = get_term_by('slug', $cat_slug, 'personnel_category');
            if ($term && !is_wp_error($term)) {
                $cat_order = (int) get_term_meta($term->term_id, 'category_order', true) ?: 1;
                $cat_offset = $cat_order * 100;
            }
        }

        global $wpdb;
        foreach ($post_ids as $order_index => $post_id) {
            $dept_order = $order_index + 1;
            update_post_meta($post_id, 'personnel_dept_order', $dept_order);
            $menu_order = $cat_offset > 0 ? ($cat_offset + $dept_order) : $dept_order;
            $wpdb->update(
                $wpdb->posts,
                array('menu_order' => $menu_order),
                array('ID' => $post_id),
                array('%d'),
                array('%d')
            );
            clean_post_cache($post_id);
        }

        wp_send_json_success(array('updated' => count($post_ids)));
    }

    /**
     * AJAX handler to save personnel order within a specific department
     */
    public function ajax_reorder_dept_personnel()
    {
        check_ajax_referer('project_core_personnel_dept_reorder_nonce', 'nonce');

        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : array();

        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'No post IDs received.'));
        }

        $cat_order = 99;
        if ($term_id > 0) {
            $cat_order = (int) get_term_meta($term_id, 'category_order', true) ?: 99;
        }

        global $wpdb;
        foreach ($post_ids as $index => $post_id) {
            $dept_order = $index + 1;
            update_post_meta($post_id, 'personnel_dept_order', $dept_order);
            $menu_order = ($cat_order * 100) + $dept_order;
            $wpdb->update(
                $wpdb->posts,
                array('menu_order' => $menu_order),
                array('ID' => $post_id),
                array('%d'),
                array('%d')
            );
            clean_post_cache($post_id);
        }

        wp_send_json_success(array(
            'updated' => count($post_ids),
            'term_id' => $term_id,
        ));
    }

    /**
     * AJAX handler to auto-index all personnel across all departments
     */
    public function ajax_auto_index_personnel()
    {
        check_ajax_referer('project_core_personnel_dept_reorder_nonce', 'nonce');

        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $categories = get_terms(array(
            'taxonomy' => 'personnel_category',
            'hide_empty' => false,
        ));

        if (!empty($categories) && !is_wp_error($categories)) {
            usort($categories, function ($a, $b) {
                $order_a = (int) (get_term_meta($a->term_id, 'category_order', true) ?: 999);
                $order_b = (int) (get_term_meta($b->term_id, 'category_order', true) ?: 999);
                if ($order_a === $order_b) {
                    return $a->term_id - $b->term_id;
                }
                return $order_a - $order_b;
            });
        }

        global $wpdb;
        $total_updated = 0;

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $cat_order = (int) get_term_meta($cat->term_id, 'category_order', true) ?: 99;
                $posts = get_posts(array(
                    'post_type' => 'personnel',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'draft'),
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'personnel_category',
                            'field' => 'term_id',
                            'terms' => $cat->term_id,
                        ),
                    ),
                ));

                usort($posts, function ($a, $b) {
                    $order_a = get_post_meta($a->ID, 'personnel_dept_order', true);
                    $order_b = get_post_meta($b->ID, 'personnel_dept_order', true);
                    $val_a = ($order_a !== '' && $order_a !== false) ? (int) $order_a : (int) $a->menu_order;
                    $val_b = ($order_b !== '' && $order_b !== false) ? (int) $order_b : (int) $b->menu_order;
                    if ($val_a === $val_b)
                        return $a->ID - $b->ID;
                    return $val_a - $val_b;
                });

                foreach ($posts as $idx => $p) {
                    $dept_order = $idx + 1;
                    update_post_meta($p->ID, 'personnel_dept_order', $dept_order);
                    $menu_order = ($cat_order * 100) + $dept_order;
                    $wpdb->update(
                        $wpdb->posts,
                        array('menu_order' => $menu_order),
                        array('ID' => $p->ID),
                        array('%d'),
                        array('%d')
                    );
                    clean_post_cache($p->ID);
                    $total_updated++;
                }
            }
        }

        wp_send_json_success(array('updated' => $total_updated));
    }

    /**
     * Render the dedicated Department Personnel Ordering page
     */
    public function render_personnel_reorder_page()
    {
        if (!current_user_can('edit_others_posts')) {
            wp_die(__('คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'project-core'));
        }

        $nonce = wp_create_nonce('project_core_personnel_dept_reorder_nonce');

        // Fetch categories ordered by category_order (NUMERIC) ASC
        $categories = get_terms(array(
            'taxonomy' => 'personnel_category',
            'hide_empty' => false,
        ));

        if (!empty($categories) && !is_wp_error($categories)) {
            usort($categories, function ($a, $b) {
                $order_a = (int) (get_term_meta($a->term_id, 'category_order', true) ?: 999);
                $order_b = (int) (get_term_meta($b->term_id, 'category_order', true) ?: 999);
                if ($order_a === $order_b) {
                    return $a->term_id - $b->term_id;
                }
                return $order_a - $order_b;
            });
        }

        // Group staff by category
        $cat_staff_map = array();
        $total_personnel_count = 0;

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $posts = get_posts(array(
                    'post_type' => 'personnel',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'draft'),
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'personnel_category',
                            'field' => 'term_id',
                            'terms' => $cat->term_id,
                        ),
                    ),
                ));

                // Sort inside category by personnel_dept_order if present, else menu_order
                usort($posts, function ($a, $b) {
                    $order_a = get_post_meta($a->ID, 'personnel_dept_order', true);
                    $order_b = get_post_meta($b->ID, 'personnel_dept_order', true);
                    $val_a = ($order_a !== '' && $order_a !== false) ? (int) $order_a : (int) $a->menu_order;
                    $val_b = ($order_b !== '' && $order_b !== false) ? (int) $order_b : (int) $b->menu_order;
                    if ($val_a === $val_b)
                        return $a->ID - $b->ID;
                    return $val_a - $val_b;
                });

                $cat_staff_map[$cat->term_id] = $posts;
                $total_personnel_count += count($posts);
            }
        }

        // Check unassigned personnel
        $unassigned_posts = get_posts(array(
            'post_type' => 'personnel',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft'),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'personnel_category',
                    'operator' => 'NOT EXISTS',
                ),
            ),
        ));
        $total_personnel_count += count($unassigned_posts);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('จัดลำดับบุคลากรตามฝ่าย / แผนก', 'project-core'); ?></h1>
            <a href="<?php echo admin_url('edit.php?post_type=personnel'); ?>"
                class="page-title-action"><?php _e('บุคลากรทั้งหมด', 'project-core'); ?></a>
            <a href="<?php echo admin_url('post-new.php?post_type=personnel'); ?>"
                class="page-title-action"><?php _e('เพิ่มบุคลากรใหม่', 'project-core'); ?></a>
            <a href="<?php echo admin_url('edit-tags.php?taxonomy=personnel_category&post_type=personnel'); ?>"
                class="page-title-action"><?php _e('จัดลำดับกลุ่ม/ฝ่าย', 'project-core'); ?></a>
            <button type="button" id="btn-auto-index-all" class="page-title-action"
                style="cursor: pointer;"><?php _e('⚡ จัดระเบียบทุกฝ่ายอัตโนมัติ', 'project-core'); ?></button>
            <hr class="wp-header-end">

            <!-- Toast Save Notice -->
            <div id="personnel-dept-toast" class="notice notice-success is-dismissible" style="display:none; margin: 15px 0;">
                <p id="personnel-toast-text">✓ บันทึกการจัดลำดับเรียบร้อยแล้ว</p>
            </div>

            <!-- Help Notice -->
            <div class="notice notice-info inline" style="margin: 15px 0 20px 0;">
                <p>
                    <strong>💡 <?php _e('วิธีจัดลำดับ:', 'project-core'); ?></strong>
                    <?php _e('สามารถคลิกค้างแล้วลากแถวเพื่อสลับตำแหน่ง (Drag & Drop) หรือกดปุ่ม <strong>⬆️ ขึ้น / ⬇️ ลง / 🔝 บนสุด / 🔻 ล่างสุด</strong> ระบบจะบันทึกลำดับให้อัตโนมัติทันที และนำไปแสดงผลเรียงตามนี้บนหน้าเว็บไซต์', 'project-core'); ?>
                </p>
            </div>

            <!-- Native WordPress Nav Tabs -->
            <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
                <a href="#all" class="nav-tab nav-tab-active dept-tab" data-target="all">
                    <?php _e('แสดงทุกฝ่าย', 'project-core'); ?>
                    <span class="count" style="color:#646970;">(<?php echo (int) $total_personnel_count; ?>)</span>
                </a>
                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                    <?php foreach ($categories as $cat):
                        $c_count = isset($cat_staff_map[$cat->term_id]) ? count($cat_staff_map[$cat->term_id]) : 0;
                        ?>
                        <a href="#dept-<?php echo (int) $cat->term_id; ?>" class="nav-tab dept-tab"
                            data-target="dept-<?php echo (int) $cat->term_id; ?>">
                            <?php echo esc_html($cat->name); ?>
                            <span class="count" style="color:#646970;">(<?php echo (int) $c_count; ?>)</span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($unassigned_posts)): ?>
                    <a href="#dept-unassigned" class="nav-tab dept-tab" data-target="dept-unassigned" style="color:#b32d2e;">
                        <?php _e('⚠️ ยังไม่ระบุฝ่าย', 'project-core'); ?>
                        <span class="count">(<?php echo count($unassigned_posts); ?>)</span>
                    </a>
                <?php endif; ?>
            </nav>

            <style>
                .personnel-dept-table tbody tr {
                    cursor: grab;
                    transition: background-color 0.15s ease;
                }

                .personnel-dept-table tbody tr:hover {
                    background-color: #f0f6fc !important;
                }

                .personnel-dept-table tbody tr.ui-sortable-helper {
                    background-color: #ffffff !important;
                    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18) !important;
                    display: table !important;
                    cursor: grabbing !important;
                    border: 1px solid #2271b1 !important;
                }

                .personnel-dept-table tbody tr.ui-sortable-placeholder {
                    background-color: #f0f6fc !important;
                    visibility: visible !important;
                    outline: 2px dashed #2271b1 !important;
                    height: 54px !important;
                }

                tr.row-just-moved {
                    background-color: #edfaef !important;
                    transition: background-color 0.8s ease;
                }

                .dept-btn-group {
                    display: inline-flex;
                    align-items: center;
                    gap: 3px;
                }
            </style>

            <!-- Cards Container: Postboxes with Native WP Tables -->
            <div class="dept-cards-container">
                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                    <?php foreach ($categories as $cat):
                        $staff_list = isset($cat_staff_map[$cat->term_id]) ? $cat_staff_map[$cat->term_id] : array();
                        $cat_order = (int) get_term_meta($cat->term_id, 'category_order', true) ?: 99;
                        ?>
                        <div class="postbox dept-section-card" id="dept-<?php echo (int) $cat->term_id; ?>"
                            data-term-id="<?php echo (int) $cat->term_id; ?>" style="margin-bottom: 22px;">
                            <div class="postbox-header"
                                style="display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; border-bottom: 1px solid #dcdcde; background: #f6f7f7;">
                                <h2 class="hndle"
                                    style="margin: 0; padding: 0; font-size: 14px; font-weight: 600; cursor: default; display: flex; align-items: center; gap: 8px;">
                                    <span class="dashicons dashicons-category" style="color: #2271b1;"></span>
                                    <span><?php echo esc_html($cat->name); ?></span>
                                    <span style="font-weight: normal; font-size: 12px; color: #646970;">
                                        (<?php printf(__('ลำดับกลุ่มที่ #%d | บุคลากร %d ท่าน', 'project-core'), (int) $cat_order, count($staff_list)); ?>)
                                    </span>
                                </h2>
                                <div class="handle-actions">
                                    <?php if (!empty($staff_list)): ?>
                                        <button type="button" class="button button-secondary btn-manual-save"
                                            data-term-id="<?php echo (int) $cat->term_id; ?>"
                                            style="display: inline-flex; align-items: center; gap: 4px;">
                                            <span class="dashicons dashicons-saved"
                                                style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
                                            <?php _e('บันทึกลำดับฝ่ายนี้', 'project-core'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="inside" style="margin: 0; padding: 0;">
                                <?php if (empty($staff_list)): ?>
                                    <div style="padding: 24px; text-align: center; color: #646970;">
                                        <p style="margin: 0 0 10px 0; font-size: 13px;">
                                            <?php _e('ยังไม่มีบุคลากรในฝ่ายนี้', 'project-core'); ?></p>
                                        <a href="<?php echo admin_url('post-new.php?post_type=personnel'); ?>" class="button button-small">
                                            + <?php _e('เพิ่มบุคลากรเข้าฝ่ายนี้', 'project-core'); ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <table class="wp-list-table widefat fixed striped table-view-list personnel-dept-table"
                                        data-term-id="<?php echo (int) $cat->term_id; ?>"
                                        data-term-name="<?php echo esc_attr($cat->name); ?>" style="border: none; box-shadow: none;">
                                        <thead>
                                            <tr>
                                                <th scope="col" style="width: 36px; text-align: center;"></th>
                                                <th scope="col" style="width: 60px; text-align: center;">
                                                    <?php _e('ลำดับ', 'project-core'); ?></th>
                                                <th scope="col" style="width: 58px; text-align: center;">
                                                    <?php _e('รูปถ่าย', 'project-core'); ?></th>
                                                <th scope="col" style="width: 28%;"><?php _e('ชื่อ - นามสกุล', 'project-core'); ?></th>
                                                <th scope="col"><?php _e('ตำแหน่ง / หน้าที่รับผิดชอบ', 'project-core'); ?></th>
                                                <th scope="col" style="width: 240px; text-align: right;">
                                                    <?php _e('การจัดการลำดับ', 'project-core'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody class="personnel-dept-list ui-sortable" data-term-id="<?php echo (int) $cat->term_id; ?>"
                                            data-term-name="<?php echo esc_attr($cat->name); ?>">
                                            <?php foreach ($staff_list as $idx => $p):
                                                $thumb_url = get_the_post_thumbnail_url($p->ID, 'thumbnail');
                                                $nickname = get_post_meta($p->ID, 'personnel_nickname', true);
                                                $position = get_post_meta($p->ID, 'personnel_position', true);
                                                ?>
                                                <tr class="personnel-dept-row" data-post-id="<?php echo (int) $p->ID; ?>">
                                                    <td class="col-drag" style="text-align: center; vertical-align: middle; cursor: grab;"
                                                        title="<?php esc_attr_e('คลิกค้างแล้วลากเพื่อสลับตำแหน่ง', 'project-core'); ?>">
                                                        <span class="dashicons dashicons-menu" style="color: #8c8f94; font-size: 18px;"></span>
                                                    </td>
                                                    <td class="col-order" style="text-align: center; vertical-align: middle;">
                                                        <span class="order-badge"
                                                            style="display: inline-block; min-width: 28px; text-align: center; padding: 2px 7px; background: #2271b1; color: #ffffff; border-radius: 4px; font-weight: 700; font-size: 12px;">#<?php echo (int) ($idx + 1); ?></span>
                                                    </td>
                                                    <td class="col-avatar" style="text-align: center; vertical-align: middle;">
                                                        <?php if ($thumb_url): ?>
                                                            <img src="<?php echo esc_url($thumb_url); ?>"
                                                                alt="<?php echo esc_attr($p->post_title); ?>"
                                                                style="width: 36px; height: 46px; object-fit: cover; border-radius: 3px; border: 1px solid #c3c4c7; display: inline-block; vertical-align: middle;" />
                                                        <?php else: ?>
                                                            <span class="dashicons dashicons-admin-users"
                                                                style="font-size: 32px; width: 32px; height: 32px; color: #c3c4c7; vertical-align: middle;"></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="col-info" style="vertical-align: middle;">
                                                        <strong><a href="<?php echo get_edit_post_link($p->ID); ?>" class="row-title"
                                                                style="font-size: 13px;"><?php echo esc_html($p->post_title); ?></a></strong>
                                                        <?php if (!empty($nickname)): ?>
                                                            <span
                                                                style="color: #646970; font-size: 12px; margin-left: 4px;">(<?php echo esc_html($nickname); ?>)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="col-position" style="vertical-align: middle;">
                                                        <span
                                                            style="display: inline-block; padding: 2px 8px; background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 3px; font-size: 12px; color: #2c3338;">
                                                            <?php echo esc_html($position ?: 'บุคลากร'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="col-actions"
                                                        style="text-align: right; vertical-align: middle; white-space: nowrap;">
                                                        <div class="dept-btn-group">
                                                            <button type="button" class="button button-small btn-ctrl btn-move-top"
                                                                title="<?php esc_attr_e('ขึ้นบนสุด', 'project-core'); ?>">🔝 บนสุด</button>
                                                            <button type="button" class="button button-small btn-ctrl btn-move-up"
                                                                title="<?php esc_attr_e('เลื่อนขึ้น 1 ตำแหน่ง', 'project-core'); ?>">⬆️
                                                                ขึ้น</button>
                                                            <button type="button" class="button button-small btn-ctrl btn-move-down"
                                                                title="<?php esc_attr_e('เลื่อนลง 1 ตำแหน่ง', 'project-core'); ?>">⬇️
                                                                ลง</button>
                                                            <button type="button" class="button button-small btn-ctrl btn-move-bottom"
                                                                title="<?php esc_attr_e('ลงล่างสุด', 'project-core'); ?>">🔻 ล่างสุด</button>
                                                            <a href="<?php echo get_edit_post_link($p->ID); ?>" target="_blank"
                                                                class="button button-small"
                                                                title="<?php esc_attr_e('แก้ไขข้อมูลบุคลากร', 'project-core'); ?>">✏️</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($unassigned_posts)): ?>
                    <div class="postbox dept-section-card" id="dept-unassigned" data-term-id="0"
                        style="margin-bottom: 22px; border-color: #d63638;">
                        <div class="postbox-header"
                            style="display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; border-bottom: 1px solid #d63638; background: #fcf0f1;">
                            <h2 class="hndle"
                                style="margin: 0; padding: 0; font-size: 14px; font-weight: 600; color: #b32d2e; display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                <span><?php _e('บุคลากรที่ยังไม่ได้จัดกลุ่มฝ่าย', 'project-core'); ?></span>
                                <span
                                    style="font-weight: normal; font-size: 12px; color: #646970;">(<?php echo count($unassigned_posts); ?>
                                    ท่าน)</span>
                            </h2>
                        </div>
                        <div class="inside" style="margin: 0; padding: 0;">
                            <table class="wp-list-table widefat fixed striped table-view-list"
                                style="border: none; box-shadow: none;">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 36px; text-align: center;"></th>
                                        <th scope="col" style="width: 60px; text-align: center;">
                                            <?php _e('ลำดับ', 'project-core'); ?></th>
                                        <th scope="col" style="width: 58px; text-align: center;">
                                            <?php _e('รูปถ่าย', 'project-core'); ?></th>
                                        <th scope="col" style="width: 35%;"><?php _e('ชื่อ - นามสกุล', 'project-core'); ?></th>
                                        <th scope="col"><?php _e('ตำแหน่ง', 'project-core'); ?></th>
                                        <th scope="col" style="width: 140px; text-align: right;">
                                            <?php _e('การจัดการ', 'project-core'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unassigned_posts as $idx => $p):
                                        $thumb_url = get_the_post_thumbnail_url($p->ID, 'thumbnail');
                                        $nickname = get_post_meta($p->ID, 'personnel_nickname', true);
                                        $position = get_post_meta($p->ID, 'personnel_position', true);
                                        ?>
                                        <tr class="personnel-dept-row" data-post-id="<?php echo (int) $p->ID; ?>">
                                            <td style="text-align: center; vertical-align: middle;">
                                                <span class="dashicons dashicons-menu" style="color: #c3c4c7;"></span>
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <span
                                                    style="display: inline-block; min-width: 28px; text-align: center; padding: 2px 7px; background: #646970; color: #ffffff; border-radius: 4px; font-weight: 700; font-size: 12px;">#<?php echo (int) ($idx + 1); ?></span>
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <?php if ($thumb_url): ?>
                                                    <img src="<?php echo esc_url($thumb_url); ?>"
                                                        alt="<?php echo esc_attr($p->post_title); ?>"
                                                        style="width: 36px; height: 46px; object-fit: cover; border-radius: 3px; border: 1px solid #c3c4c7; display: inline-block; vertical-align: middle;" />
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-admin-users"
                                                        style="font-size: 32px; width: 32px; height: 32px; color: #c3c4c7; vertical-align: middle;"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <strong><a href="<?php echo get_edit_post_link($p->ID); ?>" class="row-title"
                                                        style="font-size: 13px;"><?php echo esc_html($p->post_title); ?></a></strong>
                                                <?php if (!empty($nickname)): ?>
                                                    <span
                                                        style="color: #646970; font-size: 12px; margin-left: 4px;">(<?php echo esc_html($nickname); ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span
                                                    style="display: inline-block; padding: 2px 8px; background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 3px; font-size: 12px; color: #2c3338;">
                                                    <?php echo esc_html($position ?: 'บุคลากร'); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right; vertical-align: middle;">
                                                <a href="<?php echo get_edit_post_link($p->ID); ?>" target="_blank"
                                                    class="button button-small">
                                                    <span class="dashicons dashicons-edit"
                                                        style="vertical-align: middle; font-size: 14px; width: 14px; height: 14px; line-height: 14px;"></span>
                                                    <?php _e('กำหนดฝ่าย', 'project-core'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <script>
                jQuery(function ($) {
                    var nonce = '<?php echo esc_js($nonce); ?>';

                    function showToast(msg) {
                        $('#personnel-toast-text').text(msg || '✓ บันทึกลำดับเรียบร้อยแล้ว');
                        $('#personnel-dept-toast').stop(true, true).slideDown(150);
                        setTimeout(function () {
                            $('#personnel-dept-toast').slideUp(300);
                        }, 3000);
                    }

                    // 1. Department Tabs Filter
                    $('.nav-tab.dept-tab').on('click', function (e) {
                        e.preventDefault();
                        $('.nav-tab.dept-tab').removeClass('nav-tab-active');
                        $(this).addClass('nav-tab-active');

                        var target = $(this).data('target');
                        if (target === 'all') {
                            $('.dept-section-card').show();
                        } else {
                            $('.dept-section-card').hide();
                            $('#' + target).show();
                        }
                    });

                    // 2. Update numbers and trigger save for a list
                    function saveListOrder($tbody, $highlightRow) {
                        var termId = $tbody.data('term-id');
                        var termName = $tbody.data('term-name') || 'ฝ่ายนี้';
                        var postIds = [];

                        $tbody.find('tr.personnel-dept-row').each(function (index) {
                            $(this).find('.order-badge').text('#' + (index + 1));
                            postIds.push($(this).data('post-id'));
                        });

                        if ($highlightRow && $highlightRow.length) {
                            $highlightRow.addClass('row-just-moved');
                            setTimeout(function () {
                                $highlightRow.removeClass('row-just-moved');
                            }, 1000);
                        }

                        if (!postIds.length) return;

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'project_core_reorder_dept_personnel',
                                nonce: nonce,
                                term_id: termId,
                                post_ids: postIds
                            },
                            success: function (res) {
                                if (res.success) {
                                    showToast('✓ บันทึกลำดับใน ' + termName + ' เรียบร้อยแล้ว (' + postIds.length + ' ท่าน)');
                                }
                            }
                        });
                    }

                    // 3. Make each department list Sortable
                    $('.personnel-dept-list').each(function () {
                        var $tbody = $(this);
                        $tbody.sortable({
                            items: 'tr.personnel-dept-row',
                            handle: '.col-drag, .col-order, .col-avatar',
                            cancel: 'button, a, input',
                            axis: 'y',
                            cursor: 'grabbing',
                            opacity: 0.88,
                            placeholder: 'ui-sortable-placeholder',
                            helper: function (e, tr) {
                                var $originals = tr.children();
                                var $helper = tr.clone();
                                $helper.children().each(function (index) {
                                    $(this).width($originals.eq(index).width());
                                });
                                return $helper;
                            },
                            update: function (event, ui) {
                                saveListOrder($tbody, ui.item);
                            }
                        });
                    });

                    // 4. Quick Action Buttons
                    $(document).on('click', '.btn-move-up', function () {
                        var $row = $(this).closest('tr.personnel-dept-row');
                        var $prev = $row.prev('tr.personnel-dept-row');
                        if ($prev.length) {
                            $row.insertBefore($prev);
                            saveListOrder($row.closest('.personnel-dept-list'), $row);
                        }
                    });

                    $(document).on('click', '.btn-move-down', function () {
                        var $row = $(this).closest('tr.personnel-dept-row');
                        var $next = $row.next('tr.personnel-dept-row');
                        if ($next.length) {
                            $row.insertAfter($next);
                            saveListOrder($row.closest('.personnel-dept-list'), $row);
                        }
                    });

                    $(document).on('click', '.btn-move-top', function () {
                        var $row = $(this).closest('tr.personnel-dept-row');
                        var $tbody = $row.closest('.personnel-dept-list');
                        $tbody.prepend($row);
                        saveListOrder($tbody, $row);
                    });

                    $(document).on('click', '.btn-move-bottom', function () {
                        var $row = $(this).closest('tr.personnel-dept-row');
                        var $tbody = $row.closest('.personnel-dept-list');
                        $tbody.append($row);
                        saveListOrder($tbody, $row);
                    });

                    // Manual Save button
                    $('.btn-manual-save').on('click', function () {
                        var termId = $(this).data('term-id');
                        var $card = $('#dept-' + termId);
                        var $tbody = $card.find('.personnel-dept-list');
                        if ($tbody.length) {
                            saveListOrder($tbody);
                        }
                    });

                    // 5. Auto Index All Departments button
                    $('#btn-auto-index-all').on('click', function () {
                        if (!confirm('ต้องการจัดระเบียบลำดับเริ่มต้นของบุคลากรทุกฝ่ายตามลำดับปัจจุบันใช่หรือไม่?')) {
                            return;
                        }
                        var $btn = $(this);
                        $btn.prop('disabled', true).text('กำลังจัดระเบียบ...');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'project_core_auto_index_personnel',
                                nonce: nonce
                            },
                            success: function (res) {
                                $btn.prop('disabled', false).text('⚡ จัดระเบียบทุกฝ่ายอัตโนมัติ');
                                if (res.success) {
                                    showToast('✓ จัดระเบียบลำดับทุกฝ่ายเรียบร้อยแล้ว (' + res.data.updated + ' รายการ)');
                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 1200);
                                }
                            },
                            error: function () {
                                $btn.prop('disabled', false).text('⚡ จัดระเบียบทุกฝ่ายอัตโนมัติ');
                                alert('เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่อีกครั้ง');
                            }
                        });
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX handler to save personnel category order
     */
    public function ajax_reorder_personnel_categories()
    {
        check_ajax_referer('project_core_personnel_cat_nonce', 'nonce');

        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $term_ids = isset($_POST['term_ids']) ? array_map('intval', (array) $_POST['term_ids']) : array();
        if (empty($term_ids)) {
            wp_send_json_error(array('message' => 'No term IDs received.'));
        }

        foreach ($term_ids as $order_index => $term_id) {
            $order = $order_index + 1;
            update_term_meta($term_id, 'category_order', $order);
            clean_term_cache($term_id, 'personnel_category');
        }

        wp_send_json_success(array('updated' => count($term_ids)));
    }

    /**
     * Define custom columns for SAR reports admin list
     */
    public function set_sar_report_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['sar_cover'] = __('หน้าปก / ภาพตัวอย่าง', 'project-core');
        $new_columns['title'] = __('ชื่อรายงาน SAR', 'project-core');
        $new_columns['taxonomy-sar_year'] = __('ปีการศึกษา', 'project-core');
        $new_columns['sar_file'] = __('ไฟล์แนบ (PDF)', 'project-core');
        $new_columns['sar_order'] = __('ลำดับ', 'project-core');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for SAR reports
     */
    public function render_sar_report_columns($column, $post_id)
    {
        switch ($column) {
            case 'sar_cover':
                $img = get_the_post_thumbnail_url($post_id, 'thumbnail');
                if ($img) {
                    echo '<img src="' . esc_url($img) . '" style="width: 50px; height: 65px; object-fit: cover; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); border: 1px solid #dcdcde;" />';
                } else {
                    echo '<div style="width: 50px; height: 65px; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 4px; display: flex; align-items: center; justify-content: center;"><span class="dashicons dashicons-media-document" style="color: #0284c7; font-size: 26px; width: 26px; height: 26px;"></span></div>';
                }
                break;

            case 'sar_file':
                $file_url = get_post_meta($post_id, 'sar_file_url', true);
                if (!empty($file_url)) {
                    $ext = strtoupper(pathinfo(parse_url($file_url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'PDF';
                    echo '<a href="' . esc_url($file_url) . '" target="_blank" class="button button-small" style="display: inline-flex; align-items: center; gap: 4px; color: #0047BA; font-weight: 600;">';
                    echo '<span class="dashicons dashicons-pdf" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span> ' . esc_html($ext);
                    echo '</a>';
                } else {
                    echo '<span style="color: #94a3b8; font-style: italic;">ไม่มีไฟล์แนบ</span>';
                }
                break;

            case 'sar_order':
                $order = (int) get_post_field('menu_order', $post_id);
                echo '<span style="display: inline-block; min-width: 24px; text-align: center; padding: 2px 6px; background: #f1f5f9; border-radius: 4px; font-weight: 600; font-size: 12px;">' . esc_html($order) . '</span>';
                break;
        }
    }

    /**
     * Filter SAR reports by year dropdown in admin
     */
    public function filter_sar_reports_by_year($post_type, $which)
    {
        if ($post_type === 'sar_report') {
            $selected = isset($_GET['sar_year']) ? sanitize_text_field($_GET['sar_year']) : '';
            wp_dropdown_categories(array(
                'show_option_all' => __('— ทุกปีการศึกษา —', 'project-core'),
                'taxonomy' => 'sar_year',
                'name' => 'sar_year',
                'orderby' => 'name',
                'order' => 'DESC',
                'selected' => $selected,
                'hierarchical' => true,
                'value_field' => 'slug',
                'hide_empty' => false,
            ));
        }
    }

    /**
     * Admin query filter for SAR reports
     */
    public function admin_sar_report_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'sar_report') {
            if (!empty($_GET['sar_year'])) {
                $query->query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => 'sar_year',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['sar_year']),
                    ),
                );
            }
            if (!isset($_GET['orderby'])) {
                $query->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
            }
        }
    }

    /**
     * Seed default SAR years and sample reports (Disabled to respect admin deletions)
     */
    public function seed_default_sar_data()
    {
        // Do not auto-seed sample SAR reports when deleted by admin
        return;
    }

    /**
     * Set columns for Educational MIS admin table
     */
    public function set_education_mis_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('ปีการศึกษา / รายการสารสนเทศ', 'project-core');
        $new_columns['mis_year'] = __('ปีการศึกษา', 'project-core');
        $new_columns['students_count'] = __('จำนวนนักศึกษา', 'project-core');
        $new_columns['personnel_count'] = __('จำนวนบุคลากร', 'project-core');
        $new_columns['employment_rate'] = __('อัตรามีงานทำ/ศึกษาต่อ', 'project-core');
        $new_columns['pdf_file'] = __('ไฟล์รายงาน PDF', 'project-core');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render Educational MIS admin columns
     */
    public function render_education_mis_columns($column, $post_id)
    {
        switch ($column) {
            case 'mis_year':
                $terms = get_the_terms($post_id, 'mis_academic_year');
                if ($terms && !is_wp_error($terms)) {
                    $links = array();
                    foreach ($terms as $t) {
                        $links[] = sprintf(
                            '<a href="%s"><strong style="color: #0047BA;">%s</strong></a>',
                            esc_url(add_query_arg(array('post_type' => 'education_mis', 'mis_academic_year' => $t->slug), 'edit.php')),
                            esc_html($t->name)
                        );
                    }
                    echo implode(', ', $links);
                } else {
                    $year_text = get_post_meta($post_id, 'mis_academic_year_text', true);
                    echo !empty($year_text) ? 'ปีการศึกษา ' . esc_html($year_text) : '—';
                }
                break;

            case 'students_count':
                $total = get_post_meta($post_id, 'mis_students_total', true);
                if (!empty($total)) {
                    echo '<strong>' . number_format((int) $total) . '</strong> <span style="color:#64748b;">คน</span>';
                } else {
                    echo '—';
                }
                break;

            case 'personnel_count':
                $total = get_post_meta($post_id, 'mis_personnel_total', true);
                if (!empty($total)) {
                    echo '<strong>' . number_format((int) $total) . '</strong> <span style="color:#64748b;">คน</span>';
                } else {
                    echo '—';
                }
                break;

            case 'employment_rate':
                $rate = get_post_meta($post_id, 'mis_employment_total_rate', true);
                if (!empty($rate)) {
                    echo '<span style="display:inline-block; padding: 2px 8px; border-radius: 9999px; background: #ecfdf5; color: #047857; font-weight: 600; font-size: 12px;">' . esc_html($rate) . '%</span>';
                } else {
                    echo '—';
                }
                break;

            case 'pdf_file':
                $url = get_post_meta($post_id, 'mis_report_pdf_url', true);
                if (!empty($url)) {
                    printf(
                        '<a href="%s" target="_blank" class="button button-small" style="display:inline-flex; align-items:center; gap:3px;"><span class="dashicons dashicons-pdf" style="font-size:14px; width:14px; height:14px; line-height:14px;"></span> %s</a>',
                        esc_url($url),
                        __('ดาวน์โหลด PDF', 'project-core')
                    );
                } else {
                    echo '<span style="color: #94a3b8;">— ไม่มีไฟล์ —</span>';
                }
                break;
        }
    }

    /**
     * Filter Educational MIS by academic year dropdown in admin
     */
    public function filter_education_mis_by_year($post_type, $which)
    {
        if ($post_type === 'education_mis') {
            $selected = isset($_GET['mis_academic_year']) ? sanitize_text_field($_GET['mis_academic_year']) : '';
            $terms = get_terms(array(
                'taxonomy' => 'mis_academic_year',
                'hide_empty' => false,
            ));

            if (!empty($terms) && !is_wp_error($terms)) {
                echo '<select name="mis_academic_year" id="filter-by-mis-year">';
                echo '<option value="">' . __('— ทุกปีการศึกษา —', 'project-core') . '</option>';
                foreach ($terms as $t) {
                    printf(
                        '<option value="%s" %s>%s (%d)</option>',
                        esc_attr($t->slug),
                        selected($selected, $t->slug, false),
                        esc_html($t->name),
                        $t->count
                    );
                }
                echo '</select>';
            }
        }
    }

    /**
     * Apply filter query for Educational MIS in admin
     */
    public function admin_education_mis_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'education_mis') {
            if (!empty($_GET['mis_academic_year'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'mis_academic_year',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['mis_academic_year']),
                    ),
                ));
            }
            if (!isset($_GET['orderby'])) {
                $query->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
            }
        }
    }

    /**
     * Seed default academic years and comprehensive MIS data
     */
    public function seed_default_education_mis_data()
    {
        if (!taxonomy_exists('mis_academic_year') || !post_type_exists('education_mis')) {
            return;
        }

        // Only run initial setup once so admin deletions persist
        if (get_option('project_core_education_mis_seeded')) {
            return;
        }
        update_option('project_core_education_mis_seeded', 1);

        // Default academic years
        $years = array(
            '2568' => 'ปีการศึกษา 2568',
            '2567' => 'ปีการศึกษา 2567',
            '2566' => 'ปีการศึกษา 2566',
        );

        foreach ($years as $slug => $name) {
            if (!term_exists($slug, 'mis_academic_year')) {
                wp_insert_term($name, 'mis_academic_year', array('slug' => $slug));
            }
        }

        // Check if sample exists
        $existing = get_posts(array(
            'post_type' => 'education_mis',
            'post_status' => 'any',
            'posts_per_page' => 1,
        ));

        if (empty($existing)) {
            // Seed Year 2568 (Latest & complete)
            $students_by_dept_2568 = array(
                array('level' => 'ปวช.', 'department' => 'ช่างยนต์', 'male' => 210, 'female' => 12, 'total' => 222),
                array('level' => 'ปวช.', 'department' => 'ช่างไฟฟ้ากำลัง', 'male' => 185, 'female' => 15, 'total' => 200),
                array('level' => 'ปวช.', 'department' => 'ช่างอิเล็กทรอนิกส์', 'male' => 140, 'female' => 18, 'total' => 158),
                array('level' => 'ปวช.', 'department' => 'เทคโนโลยีสารสนเทศ', 'male' => 110, 'female' => 65, 'total' => 175),
                array('level' => 'ปวช.', 'department' => 'การบัญชี', 'male' => 25, 'female' => 165, 'total' => 190),
                array('level' => 'ปวช.', 'department' => 'การตลาด', 'male' => 35, 'female' => 140, 'total' => 175),
                array('level' => 'ปวส.', 'department' => 'เทคนิคเครื่องกล (ช่างยนต์)', 'male' => 145, 'female' => 5, 'total' => 150),
                array('level' => 'ปวส.', 'department' => 'ไฟฟ้า', 'male' => 130, 'female' => 10, 'total' => 140),
                array('level' => 'ปวส.', 'department' => 'เทคโนโลยีสารสนเทศ', 'male' => 85, 'female' => 55, 'total' => 140),
                array('level' => 'ปวส.', 'department' => 'การบัญชี', 'male' => 20, 'female' => 135, 'total' => 155),
                array('level' => 'ปวส.', 'department' => 'การจัดการโลจิสติกส์', 'male' => 45, 'female' => 100, 'total' => 145),
            );

            $personnel_breakdown_2568 = array(
                array('category' => 'ฝ่ายบริหาร', 'position' => 'ผู้อำนวยการ และรองผู้อำนวยการ', 'count' => 5, 'notes' => 'ปริญญาเอก 2, ปริญญาโท 3'),
                array('category' => 'แผนกวิชาช่างยนต์', 'position' => 'ครูผู้สอนประจำแผนกวิชา', 'count' => 12, 'notes' => 'ผู้เชี่ยวชาญยานยนต์ไฟฟ้า (EV) 4 คน'),
                array('category' => 'แผนกวิชาช่างไฟฟ้ากำลัง', 'position' => 'ครูผู้สอนประจำแผนกวิชา', 'count' => 10, 'notes' => 'ผู้ทดสอบมาตรฐานฝีมือแรงงาน 5 คน'),
                array('category' => 'แผนกวิชาช่างอิเล็กทรอนิกส์', 'position' => 'ครูผู้สอนประจำแผนกวิชา', 'count' => 8, 'notes' => 'IoT และหุ่นยนต์อุตสาหกรรม'),
                array('category' => 'แผนกวิชาเทคโนโลยีสารสนเทศ', 'position' => 'ครูผู้สอนประจำแผนกวิชา', 'count' => 9, 'notes' => 'Cloud & Software Development'),
                array('category' => 'แผนกวิชาการบัญชี', 'position' => 'ครูผู้สอนประจำแผนกวิชา', 'count' => 8, 'notes' => 'ERP & Digital Accounting'),
                array('category' => 'แผนกวิชาการตลาดและโลจิสติกส์', 'position' => 'ครูผู้สอนประจำแผนกวิชา', 'count' => 7, 'notes' => 'E-Commerce & Supply Chain'),
                array('category' => 'แผนกวิชาสามัญสัมพันธ์', 'position' => 'ครูผู้สอนหมวดวิชาสามัญ (ภาษา/วิทย์/คณิต)', 'count' => 4, 'notes' => 'ครูต่างชาติ 2 คน'),
                array('category' => 'บุคลากรทางการศึกษา', 'position' => 'เจ้าหน้าที่สนับสนุนการปฏิบัติงาน/ธุรการ', 'count' => 22, 'notes' => 'ฝ่ายทะเบียน, การเงิน, พัสดุ, อาคารสถานที่'),
            );

            $curriculum_list_2568 = array(
                array('level' => 'ปวช.', 'department' => 'ช่างยนต์', 'specialties' => 'สาขางานยานยนต์, สาขางานยานยนต์ไฟฟ้า (EV)', 'study_mode' => 'ปกติ และ ทวิภาคี'),
                array('level' => 'ปวช.', 'department' => 'ช่างไฟฟ้ากำลัง', 'specialties' => 'สาขางานไฟฟ้ากำลัง, สาขางานระบบควบคุมอัตโนมัติ', 'study_mode' => 'ปกติ และ ทวิภาคี'),
                array('level' => 'ปวช.', 'department' => 'ช่างอิเล็กทรอนิกส์', 'specialties' => 'สาขางานอิเล็กทรอนิกส์, สาขางานอุปกรณ์การสื่อสาร', 'study_mode' => 'ปกติ'),
                array('level' => 'ปวช.', 'department' => 'เทคโนโลยีสารสนเทศ', 'specialties' => 'สาขางานนักพัฒนาซอฟต์แวร์, สาขางานคอมพิวเตอร์กราฟิก', 'study_mode' => 'ปกติ และ ทวิภาคี'),
                array('level' => 'ปวช.', 'department' => 'การบัญชี', 'specialties' => 'สาขางานการบัญชี', 'study_mode' => 'ปกติ'),
                array('level' => 'ปวช.', 'department' => 'การตลาด', 'specialties' => 'สาขางานการตลาดดิจิทัล', 'study_mode' => 'ปกติ และ ทวิภาคี'),
                array('level' => 'ปวส.', 'department' => 'เทคนิคเครื่องกล', 'specialties' => 'สาขางานเทคนิคยานยนต์, สาขางานยานยนต์ไฟฟ้าอัจฉริยะ', 'study_mode' => 'ทวิภาคี 100%'),
                array('level' => 'ปวส.', 'department' => 'ไฟฟ้า', 'specialties' => 'สาขางานระบบควบคุมและระบบอัตโนมัติอุตสาหกรรม', 'study_mode' => 'ปกติ และ ทวิภาคี'),
                array('level' => 'ปวส.', 'department' => 'เทคโนโลยีสารสนเทศ', 'specialties' => 'สาขางานเทคโนโลยีสารสนเทศและการสื่อสาร, Cloud Platform', 'study_mode' => 'ปกติ และ ทวิภาคี'),
                array('level' => 'ปวส.', 'department' => 'การบัญชี', 'specialties' => 'สาขางานการบัญชีและระบบสารสนเทศทางการเงิน', 'study_mode' => 'ปกติ'),
                array('level' => 'ปวส.', 'department' => 'การจัดการโลจิสติกส์และซัพพลายเชน', 'specialties' => 'สาขางานการจัดการคลังสินค้าและการขนส่งต่อเนื่อง', 'study_mode' => 'ปกติ และ ทวิภาคี'),
            );

            $admissions_graduates_2568 = array(
                array('year' => '2568', 'intake_pvch' => 480, 'intake_pvs' => 390, 'intake_total' => 870, 'grad_pvch' => 395, 'grad_pvs' => 340, 'grad_total' => 735, 'grad_rate' => '95.8%'),
                array('year' => '2567', 'intake_pvch' => 465, 'intake_pvs' => 380, 'intake_total' => 845, 'grad_pvch' => 380, 'grad_pvs' => 330, 'grad_total' => 710, 'grad_rate' => '94.2%'),
                array('year' => '2566', 'intake_pvch' => 450, 'intake_pvs' => 360, 'intake_total' => 810, 'grad_pvch' => 370, 'grad_pvs' => 315, 'grad_total' => 685, 'grad_rate' => '93.6%'),
            );

            $achievements_list_2568 = array(
                array('department' => 'ช่างยนต์', 'gpax' => '3.15', 'vnet_score' => '72.4%', 'standard_pass_rate' => '98.5%'),
                array('department' => 'ช่างไฟฟ้ากำลัง', 'gpax' => '3.20', 'vnet_score' => '74.2%', 'standard_pass_rate' => '97.2%'),
                array('department' => 'ช่างอิเล็กทรอนิกส์', 'gpax' => '3.18', 'vnet_score' => '71.8%', 'standard_pass_rate' => '95.0%'),
                array('department' => 'เทคโนโลยีสารสนเทศ', 'gpax' => '3.38', 'vnet_score' => '82.5%', 'standard_pass_rate' => '96.8%'),
                array('department' => 'การบัญชี', 'gpax' => '3.42', 'vnet_score' => '84.1%', 'standard_pass_rate' => '99.0%'),
                array('department' => 'การตลาดและโลจิสติกส์', 'gpax' => '3.25', 'vnet_score' => '76.3%', 'standard_pass_rate' => '94.5%'),
            );

            $employment_data_2568 = array(
                array('category' => 'มีงานทำตรงสาขาวิชาที่สำเร็จการศึกษา', 'count' => 459, 'percent' => '62.5%', 'avg_salary' => '16,500 - 24,000'),
                array('category' => 'มีงานทำในสาขาวิชาอื่น / ภาคอุตสาหกรรม', 'count' => 105, 'percent' => '14.3%', 'avg_salary' => '15,000 - 18,500'),
                array('category' => 'ศึกษาต่อในระดับปริญญาตรี / สถาบันอุดมศึกษา', 'count' => 110, 'percent' => '15.0%', 'avg_salary' => '—'),
                array('category' => 'ประกอบอาชีพอิสระ / ผู้ประกอบการใหม่ (Startup)', 'count' => 37, 'percent' => '5.0%', 'avg_salary' => '18,000 - 35,000'),
                array('category' => 'อยู่ระหว่างรอการเกณฑ์ทหาร / เตรียมตัวศึกษาต่อ', 'count' => 24, 'percent' => '3.2%', 'avg_salary' => '—'),
            );

            $partner_enterprises_2568 = array(
                array(
                    'logo_url' => '/images/partners/toyota.svg',
                    'name' => 'บริษัท โตโยต้า มอเตอร์ ประเทศไทย จำกัด',
                    'industry' => 'ยานยนต์และชิ้นส่วน',
                    'cooperation_type' => 'ทวิภาคี (DVE) & มอบทุนการศึกษา',
                    'departments' => 'ช่างยนต์, ช่างไฟฟ้า'
                ),
                array(
                    'logo_url' => '/images/partners/honda.svg',
                    'name' => 'บริษัท ฮอนด้า ออโตโมบิล (ประเทศไทย) จำกัด',
                    'industry' => 'ยานยนต์อัจฉริยะ',
                    'cooperation_type' => 'ทวิภาคี & รับเข้าทำงานทันที',
                    'departments' => 'ช่างยนต์, ช่างอิเล็กทรอนิกส์'
                ),
                array(
                    'logo_url' => '/images/partners/delta.svg',
                    'name' => 'บริษัท เดลต้า อีเลคโทรนิคส์ (ประเทศไทย) จำกัด (มหาชน)',
                    'industry' => 'อิเล็กทรอนิกส์และพลังงานหมุนเวียน',
                    'cooperation_type' => 'ศูนย์เรียนรู้ & ฝึกอาชีพทวิภาคี',
                    'departments' => 'ช่างอิเล็กทรอนิกส์, ช่างไฟฟ้ากำลัง'
                ),
                array(
                    'logo_url' => '/images/partners/mitsubishi.svg',
                    'name' => 'บริษัท มิตซูบิชิ มอเตอร์ส (ประเทศไทย) จำกัด',
                    'industry' => 'ยานยนต์และระบบอัตโนมัติ',
                    'cooperation_type' => 'ทวิภาคีพรีเมียม (EEC Model)',
                    'departments' => 'เทคนิคเครื่องกล, แมคคาทรอนิกส์'
                ),
                array(
                    'logo_url' => '/images/partners/cpall.svg',
                    'name' => 'บมจ. ซีพี ออลล์ (CP ALL PCL)',
                    'industry' => 'ค้าปลีกและเทคโนโลยีสารสนเทศ',
                    'cooperation_type' => 'ทวิภาคี & ทุนเรียนฟรีมีรายได้',
                    'departments' => 'การตลาด, เทคโนโลยีสารสนเทศ'
                ),
                array(
                    'logo_url' => '/images/partners/kerry.svg',
                    'name' => 'บริษัท เคอรี่ เอ็กซ์เพรส (ประเทศไทย) จำกัด (มหาชน)',
                    'industry' => 'การจัดการคลังสินค้าและโลจิสติกส์',
                    'cooperation_type' => 'ฝึกงานและระบบบริหารจัดส่ง',
                    'departments' => 'การจัดการโลจิสติกส์'
                ),
                array(
                    'logo_url' => '/images/partners/wha.svg',
                    'name' => 'บริษัท ดับบลิวเอชเอ อินดัสเตรียล ดีเวลลอปเมนท์ จำกัด (มหาชน)',
                    'industry' => 'นิคมอุตสาหกรรมและสาธารณูปโภค',
                    'cooperation_type' => 'MOU พัฒนากำลังคนเขต EEC',
                    'departments' => 'ทุกสาขาวิชาช่างและบริหาร'
                ),
                array(
                    'logo_url' => '/images/partners/bgrimm.svg',
                    'name' => 'บริษัท บี.กริม เพาเวอร์ จำกัด (มหาชน)',
                    'industry' => 'พลังงานสะอาดและวิศวกรรมไฟฟ้า',
                    'cooperation_type' => 'ฝึกอาชีพช่างเทคนิคโรงไฟฟ้า',
                    'departments' => 'ช่างไฟฟ้ากำลัง, ช่างยนต์'
                ),
            );

            $post_id = wp_insert_post(array(
                'post_title' => 'ข้อมูลสารสนเทศทางการศึกษา ประจำปีการศึกษา 2568',
                'post_name' => 'education-mis-2568',
                'post_status' => 'publish',
                'post_type' => 'education_mis',
                'post_content' => 'สรุปสารสนเทศทางการศึกษา วิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2568 รวบรวมข้อมูลสถิตินักเรียนนักศึกษา บุคลากร หลักสูตร ผลสัมฤทธิ์ทางการเรียน ผลการทดสอบ V-NET การมีงานทำ และความร่วมมือกับสถานประกอบการตามมาตรฐานการอาชีวศึกษา',
                'menu_order' => 1,
            ));

            if ($post_id && !is_wp_error($post_id)) {
                wp_set_object_terms($post_id, '2568', 'mis_academic_year');

                // General
                update_post_meta($post_id, 'mis_academic_year_text', '2568');
                update_post_meta($post_id, 'mis_report_pdf_url', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');

                // 1. Students
                update_post_meta($post_id, 'mis_students_total', 1850);
                update_post_meta($post_id, 'mis_students_pvch_total', 1120);
                update_post_meta($post_id, 'mis_students_pvs_total', 730);
                update_post_meta($post_id, 'mis_students_by_dept', wp_json_encode($students_by_dept_2568, JSON_UNESCAPED_UNICODE));

                // 2. Personnel
                update_post_meta($post_id, 'mis_personnel_total', 85);
                update_post_meta($post_id, 'mis_personnel_executives', 5);
                update_post_meta($post_id, 'mis_personnel_teachers', 58);
                update_post_meta($post_id, 'mis_personnel_support', 22);
                update_post_meta($post_id, 'mis_personnel_breakdown', wp_json_encode($personnel_breakdown_2568, JSON_UNESCAPED_UNICODE));

                // 3. Curriculum
                update_post_meta($post_id, 'mis_curriculum_levels_count', 2);
                update_post_meta($post_id, 'mis_departments_count', 8);
                update_post_meta($post_id, 'mis_programs_count', 14);
                update_post_meta($post_id, 'mis_curriculum_list', wp_json_encode($curriculum_list_2568, JSON_UNESCAPED_UNICODE));

                // 4. Admissions & Graduates
                update_post_meta($post_id, 'mis_admissions_graduates', wp_json_encode($admissions_graduates_2568, JSON_UNESCAPED_UNICODE));

                // 5. Achievements
                update_post_meta($post_id, 'mis_gpax_avg', '3.28');
                update_post_meta($post_id, 'mis_gpax_pvch', '3.21');
                update_post_meta($post_id, 'mis_gpax_pvs', '3.36');
                update_post_meta($post_id, 'mis_vnet_pass_rate', '89.4');
                update_post_meta($post_id, 'mis_vocational_standard_pass_rate', '96.2');
                update_post_meta($post_id, 'mis_achievements_list', wp_json_encode($achievements_list_2568, JSON_UNESCAPED_UNICODE));

                // 6. Employment & Higher Education
                update_post_meta($post_id, 'mis_employment_total_rate', '96.8');
                update_post_meta($post_id, 'mis_employed_field_rate', '62.5');
                update_post_meta($post_id, 'mis_employed_other_rate', '14.3');
                update_post_meta($post_id, 'mis_higher_education_rate', '15.0');
                update_post_meta($post_id, 'mis_freelance_business_rate', '5.0');
                update_post_meta($post_id, 'mis_unemployed_or_other_rate', '3.2');
                update_post_meta($post_id, 'mis_employment_data', wp_json_encode($employment_data_2568, JSON_UNESCAPED_UNICODE));

                // 7. Partner Enterprises
                update_post_meta($post_id, 'mis_mou_partners_count', 54);
                update_post_meta($post_id, 'mis_dual_vocational_count', 42);
                update_post_meta($post_id, 'mis_intern_students_count', 640);
                update_post_meta($post_id, 'mis_intern_allowance_avg', 10500);
                update_post_meta($post_id, 'mis_partner_enterprises', wp_json_encode($partner_enterprises_2568, JSON_UNESCAPED_UNICODE));
            }

            // Seed Year 2567 (Previous year for historical comparison)
            $post_id_2567 = wp_insert_post(array(
                'post_title' => 'ข้อมูลสารสนเทศทางการศึกษา ประจำปีการศึกษา 2567',
                'post_name' => 'education-mis-2567',
                'post_status' => 'publish',
                'post_type' => 'education_mis',
                'post_content' => 'สารสนเทศทางการศึกษา วิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2567',
                'menu_order' => 2,
            ));

            if ($post_id_2567 && !is_wp_error($post_id_2567)) {
                wp_set_object_terms($post_id_2567, '2567', 'mis_academic_year');
                update_post_meta($post_id_2567, 'mis_academic_year_text', '2567');
                update_post_meta($post_id_2567, 'mis_report_pdf_url', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
                update_post_meta($post_id_2567, 'mis_students_total', 1780);
                update_post_meta($post_id_2567, 'mis_students_pvch_total', 1080);
                update_post_meta($post_id_2567, 'mis_students_pvs_total', 700);
                update_post_meta($post_id_2567, 'mis_students_by_dept', wp_json_encode($students_by_dept_2568, JSON_UNESCAPED_UNICODE));
                update_post_meta($post_id_2567, 'mis_personnel_total', 82);
                update_post_meta($post_id_2567, 'mis_personnel_executives', 5);
                update_post_meta($post_id_2567, 'mis_personnel_teachers', 56);
                update_post_meta($post_id_2567, 'mis_personnel_support', 21);
                update_post_meta($post_id_2567, 'mis_personnel_breakdown', wp_json_encode($personnel_breakdown_2568, JSON_UNESCAPED_UNICODE));
                update_post_meta($post_id_2567, 'mis_curriculum_levels_count', 2);
                update_post_meta($post_id_2567, 'mis_departments_count', 8);
                update_post_meta($post_id_2567, 'mis_programs_count', 13);
                update_post_meta($post_id_2567, 'mis_curriculum_list', wp_json_encode($curriculum_list_2568, JSON_UNESCAPED_UNICODE));
                update_post_meta($post_id_2567, 'mis_admissions_graduates', wp_json_encode($admissions_graduates_2568, JSON_UNESCAPED_UNICODE));
                update_post_meta($post_id_2567, 'mis_gpax_avg', '3.22');
                update_post_meta($post_id_2567, 'mis_gpax_pvch', '3.16');
                update_post_meta($post_id_2567, 'mis_gpax_pvs', '3.30');
                update_post_meta($post_id_2567, 'mis_vnet_pass_rate', '87.2');
                update_post_meta($post_id_2567, 'mis_vocational_standard_pass_rate', '95.1');
                update_post_meta($post_id_2567, 'mis_achievements_list', wp_json_encode($achievements_list_2568, JSON_UNESCAPED_UNICODE));
                update_post_meta($post_id_2567, 'mis_employment_total_rate', '95.4');
                update_post_meta($post_id_2567, 'mis_employed_field_rate', '60.2');
                update_post_meta($post_id_2567, 'mis_employed_other_rate', '15.2');
                update_post_meta($post_id_2567, 'mis_higher_education_rate', '15.5');
                update_post_meta($post_id_2567, 'mis_freelance_business_rate', '4.5');
                update_post_meta($post_id_2567, 'mis_unemployed_or_other_rate', '4.6');
                update_post_meta($post_id_2567, 'mis_employment_data', wp_json_encode($employment_data_2568, JSON_UNESCAPED_UNICODE));
                update_post_meta($post_id_2567, 'mis_mou_partners_count', 48);
                update_post_meta($post_id_2567, 'mis_dual_vocational_count', 36);
                update_post_meta($post_id_2567, 'mis_intern_students_count', 590);
                update_post_meta($post_id_2567, 'mis_intern_allowance_avg', 9800);
                update_post_meta($post_id_2567, 'mis_partner_enterprises', wp_json_encode($partner_enterprises_2568, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * Define custom columns for Downloads admin list
     */
    public function set_download_file_columns($columns)
    {
        $new_columns = array();
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        $new_columns['download_handle'] = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเลื่อนจัดลำดับ', 'project-core') . '"></span>';
        $new_columns['download_order'] = __('ลำดับ', 'project-core');
        $new_columns['title'] = __('ชื่อเอกสาร / แบบฟอร์ม', 'project-core');
        $new_columns['taxonomy-download_category'] = __('หมวดหมู่', 'project-core');
        $new_columns['download_type'] = __('ประเภทไฟล์', 'project-core');
        $new_columns['download_file_info'] = __('ไฟล์ / ลิงก์', 'project-core');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for Downloads
     */
    public function render_download_file_columns($column, $post_id)
    {
        switch ($column) {
            case 'download_handle':
                echo '<span class="dashicons dashicons-move download-drag-handle" style="cursor:grab; color:#64748b; font-size:18px; display:inline-block; vertical-align:middle;" title="' . esc_attr__('คลิกลากเพื่อจัดลำดับ', 'project-core') . '"></span>';
                break;

            case 'download_order':
                $post = get_post($post_id);
                $order = $post ? $post->menu_order : 0;
                echo '<span class="download-order-badge" style="display:inline-block; font-weight:700; font-size:12px; color:#0047BA; background:#e0f2fe; padding:2px 8px; border-radius:999px;">#' . esc_html($order) . '</span>';
                break;

            case 'download_type':
                $type = get_post_meta($post_id, 'download_file_type', true) ?: 'pdf';
                $color = '#64748b';
                $bg = '#f1f5f9';
                if ($type === 'pdf') {
                    $color = '#dc2626';
                    $bg = '#fee2e2';
                } elseif (in_array($type, array('doc', 'docx'))) {
                    $color = '#2563eb';
                    $bg = '#dbeafe';
                } elseif (in_array($type, array('xls', 'xlsx'))) {
                    $color = '#16a34a';
                    $bg = '#dcfce7';
                } elseif (in_array($type, array('ppt', 'pptx'))) {
                    $color = '#ea580c';
                    $bg = '#ffedd5';
                } elseif (in_array($type, array('zip', 'rar'))) {
                    $color = '#9333ea';
                    $bg = '#f3e8ff';
                } elseif ($type === 'link') {
                    $color = '#0284c7';
                    $bg = '#e0f2fe';
                }

                echo '<span style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; text-transform:uppercase; background:' . esc_attr($bg) . '; color:' . esc_attr($color) . ';">' . esc_html($type) . '</span>';
                break;

            case 'download_file_info':
                $file_url = get_post_meta($post_id, 'download_file_url', true);
                $ext_url = get_post_meta($post_id, 'download_external_url', true);
                $size = get_post_meta($post_id, 'download_file_size', true);

                if (!empty($file_url)) {
                    echo '<a href="' . esc_url($file_url) . '" target="_blank" style="text-decoration:none; color:#0047BA; font-weight:500;">';
                    echo '<span class="dashicons dashicons-media-default" style="font-size:16px; vertical-align:middle;"></span> ' . esc_html(__('ดูไฟล์', 'project-core'));
                    if (!empty($size)) {
                        echo ' <small style="color:#64748b;">(' . esc_html($size) . ')</small>';
                    }
                    echo '</a>';
                } elseif (!empty($ext_url)) {
                    echo '<a href="' . esc_url($ext_url) . '" target="_blank" style="text-decoration:none; color:#0284c7; font-weight:500;">';
                    echo '<span class="dashicons dashicons-external" style="font-size:16px; vertical-align:middle;"></span> ' . esc_html(__('ลิงก์ภายนอก', 'project-core'));
                    echo '</a>';
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;
        }
    }

    /**
     * Filter Downloads by Category in Admin List
     */
    public function filter_downloads_by_category($post_type, $which)
    {
        if ($post_type !== 'download_file') {
            return;
        }

        $selected = isset($_GET['download_category']) ? sanitize_text_field($_GET['download_category']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'taxonomy' => 'download_category',
            'name' => 'download_category',
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => true,
            'depth' => 3,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));
    }

    /**
     * Apply Download Category Filter to Query & Default Order
     */
    public function admin_download_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'download_file') {
            if (!empty($_GET['download_category'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'download_category',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['download_category']),
                    ),
                ));
            }

            if (!isset($_GET['orderby'])) {
                $query->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
            }
        }
    }

    /**
     * Set default order for download categories in WP Admin
     */
    public function admin_download_category_sort_order($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit-tags.php') {
            $taxonomies = isset($query->query_vars['taxonomy']) ? (array) $query->query_vars['taxonomy'] : array();
            if (in_array('download_category', $taxonomies, true)) {
                if (empty($_GET['orderby'])) {
                    $query->query_vars['meta_key'] = 'category_order';
                    $query->query_vars['orderby'] = 'meta_value_num';
                    $query->query_vars['order'] = 'ASC';
                }
            }
        }
    }

    /**
     * Add Category Order Field in Add Term Screen
     */
    public function download_category_add_fields()
    {
        ?>
        <div class="form-field term-order-wrap">
            <label for="category_order"><?php _e('ลำดับการแสดงผล (Order)', 'project-core'); ?></label>
            <input type="number" name="category_order" id="category_order" value="0" min="0" style="width: 100px;" />
            <p><?php _e('ตัวเลขน้อยจะแสดงก่อน เช่น 1, 2, 3...', 'project-core'); ?></p>
        </div>
        <?php
    }

    /**
     * Add Category Order Field in Edit Term Screen
     */
    public function download_category_edit_fields($term)
    {
        $order = get_term_meta($term->term_id, 'category_order', true);
        if ($order === '')
            $order = 0;
        ?>
        <tr class="form-field term-order-wrap">
            <th scope="row"><label for="category_order"><?php _e('ลำดับการแสดงผล (Order)', 'project-core'); ?></label></th>
            <td>
                <input type="number" name="category_order" id="category_order" value="<?php echo esc_attr($order); ?>" min="0"
                    style="width: 100px;" />
                <p class="description"><?php _e('ตัวเลขน้อยจะแสดงก่อน เช่น 1, 2, 3...', 'project-core'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Category Order Meta
     */
    public function save_download_category_fields($term_id)
    {
        if (isset($_POST['category_order'])) {
            $order = intval($_POST['category_order']);
            update_term_meta($term_id, 'category_order', $order);
        }
    }

    /**
     * Add Order & Handle columns to download_category list table
     */
    public function set_download_category_columns($columns)
    {
        $new = array();
        if (isset($columns['cb'])) {
            $new['cb'] = $columns['cb'];
        }
        $new['cat_handle'] = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเลื่อนจัดลำดับ', 'project-core') . '"></span>';
        $new['category_order'] = __('ลำดับ', 'project-core');
        foreach ($columns as $k => $v) {
            if ($k === 'cb')
                continue;
            $new[$k] = $v;
        }
        return $new;
    }

    public function render_download_category_columns($content, $column_name, $term_id)
    {
        if ($column_name === 'cat_handle') {
            return '<span class="dashicons dashicons-move download-cat-drag-handle" style="cursor:grab; color:#64748b; font-size:18px; display:inline-block; vertical-align:middle;" title="' . esc_attr__('คลิกลากเพื่อจัดลำดับ', 'project-core') . '"></span>';
        }
        if ($column_name === 'category_order') {
            $order = get_term_meta($term_id, 'category_order', true);
            $order_num = ($order !== '') ? intval($order) : 0;
            return '<span class="cat-order-badge" style="display:inline-block; font-weight:700; font-size:12px; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:999px;">#' . esc_html($order_num) . '</span>';
        }
        return $content;
    }

    public function set_download_category_sortable_columns($columns)
    {
        $columns['category_order'] = 'category_order';
        return $columns;
    }

    /**
     * Enqueue jQuery UI Sortable for Downloads & Download Categories
     */
    public function enqueue_download_list_scripts($hook)
    {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        if (
            ($hook === 'edit.php' && $screen->post_type === 'download_file') ||
            ($hook === 'edit-tags.php' && $screen->taxonomy === 'download_category')
        ) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    /**
     * Drag & Drop sorting script and styles for Downloads and Download Categories
     */
    public function render_download_reorder_script()
    {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $is_download_list = ($screen->id === 'edit-download_file');
        $is_category_list = ($screen->id === 'edit-download_category');

        if (!$is_download_list && !$is_category_list) {
            return;
        }

        $nonce = wp_create_nonce('project_core_download_reorder_nonce');
        ?>
        <style>
            .wp-list-table tbody#the-list tr {
                cursor: grab;
                transition: background 0.15s ease;
            }

            .wp-list-table tbody#the-list tr:hover {
                background-color: #f0f6fc !important;
            }

            .wp-list-table tbody#the-list tr.ui-sortable-helper {
                background-color: #ffffff !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22) !important;
                display: table !important;
                cursor: grabbing !important;
                border: 2px solid #0047BA !important;
            }

            .wp-list-table tbody#the-list tr.ui-sortable-placeholder {
                background-color: #e7f3ff !important;
                visibility: visible !important;
                outline: 2px dashed #0047BA !important;
                height: 50px !important;
            }

            .download-drag-handle,
            .download-cat-drag-handle {
                cursor: grab;
                color: #64748b;
                display: inline-block;
                transition: transform 0.15s, color 0.15s;
            }

            .download-drag-handle:hover,
            .download-cat-drag-handle:hover {
                color: #0047BA;
                transform: scale(1.15);
            }

            .column-download_handle,
            .column-cat_handle {
                width: 38px !important;
                text-align: center;
                padding-left: 4px !important;
                padding-right: 4px !important;
            }

            .column-download_order,
            .column-category_order {
                width: 75px !important;
                text-align: center;
            }

            #download-reorder-toast {
                display: none;
                position: fixed;
                top: 45px;
                right: 30px;
                z-index: 999999;
                padding: 12px 24px;
                background: #007017;
                color: #ffffff;
                font-weight: 600;
                border-radius: 6px;
                box-shadow: 0 6px 22px rgba(0, 0, 0, 0.25);
                font-size: 14px;
            }
        </style>
        <div id="download-reorder-toast">✓ บันทึกลำดับเรียบร้อยแล้ว</div>
        <script>
            jQuery(function ($) {
                var $tbody = $('#the-list');
                if (!$tbody.length) return;

                function showToast(msg) {
                    var $toast = $('#download-reorder-toast');
                    $toast.text(msg || '✓ บันทึกลำดับเรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                    setTimeout(function () {
                        $toast.fadeOut(400);
                    }, 2500);
                }

                <?php if ($is_download_list): ?>
                    function updateDownloadBadges() {
                        $tbody.find('tr').each(function (index) {
                            $(this).find('.download-order-badge').text('#' + (index + 1));
                        });
                    }

                    $tbody.sortable({
                        items: 'tr',
                        handle: '.download-drag-handle, td',
                        cancel: 'a, button, input, select, .check-column',
                        axis: 'y',
                        cursor: 'grabbing',
                        opacity: 0.9,
                        placeholder: 'ui-sortable-placeholder',
                        helper: function (e, tr) {
                            var $originals = tr.children();
                            var $helper = tr.clone();
                            $helper.children().each(function (index) {
                                $(this).width($originals.eq(index).width());
                            });
                            return $helper;
                        },
                        update: function () {
                            updateDownloadBadges();
                            var postIds = [];
                            $tbody.find('tr').each(function () {
                                var rowId = $(this).attr('id');
                                if (rowId && rowId.indexOf('post-') === 0) {
                                    var pid = rowId.replace('post-', '');
                                    postIds.push(pid);
                                }
                            });

                            if (!postIds.length) return;

                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'project_core_reorder_downloads',
                                    nonce: '<?php echo esc_js($nonce); ?>',
                                    post_ids: postIds
                                },
                                success: function (res) {
                                    if (res.success) {
                                        showToast('✓ บันทึกลำดับเอกสารเรียบร้อยแล้ว (' + postIds.length + ' รายการ)');
                                    }
                                }
                            });
                        }
                    });
                <?php endif; ?>

                <?php if ($is_category_list): ?>
                    function updateCategoryBadges() {
                        $tbody.find('tr').each(function (index) {
                            $(this).find('.cat-order-badge').text('#' + (index + 1));
                        });
                    }

                    $tbody.sortable({
                        items: 'tr',
                        handle: '.download-cat-drag-handle, td',
                        cancel: 'a, button, input, select, .check-column',
                        axis: 'y',
                        cursor: 'grabbing',
                        opacity: 0.9,
                        placeholder: 'ui-sortable-placeholder',
                        helper: function (e, tr) {
                            var $originals = tr.children();
                            var $helper = tr.clone();
                            $helper.children().each(function (index) {
                                $(this).width($originals.eq(index).width());
                            });
                            return $helper;
                        },
                        update: function () {
                            updateCategoryBadges();
                            var termIds = [];
                            $tbody.find('tr').each(function () {
                                var rowId = $(this).attr('id');
                                if (rowId && rowId.indexOf('tag-') === 0) {
                                    var tid = rowId.replace('tag-', '');
                                    termIds.push(tid);
                                }
                            });

                            if (!termIds.length) return;

                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'project_core_reorder_download_categories',
                                    nonce: '<?php echo esc_js($nonce); ?>',
                                    term_ids: termIds
                                },
                                success: function (res) {
                                    if (res.success) {
                                        showToast('✓ บันทึกลำดับหมวดหมู่เรียบร้อยแล้ว (' + termIds.length + ' หมวดหมู่)');
                                    }
                                }
                            });
                        }
                    });
                <?php endif; ?>
            });
        </script>
        <?php
    }

    /**
     * AJAX endpoint: reorder download files
     */
    public function ajax_reorder_downloads()
    {
        check_ajax_referer('project_core_download_reorder_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : array();
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'No post IDs received.'));
        }

        global $wpdb;
        foreach ($post_ids as $order_index => $post_id) {
            $order = $order_index + 1;
            $wpdb->update(
                $wpdb->posts,
                array('menu_order' => $order),
                array('ID' => $post_id),
                array('%d'),
                array('%d')
            );
            update_post_meta($post_id, 'download_order', $order);
            clean_post_cache($post_id);
        }

        wp_send_json_success(array('updated' => count($post_ids)));
    }

    /**
     * AJAX endpoint: reorder download categories
     */
    public function ajax_reorder_download_categories()
    {
        check_ajax_referer('project_core_download_reorder_nonce', 'nonce');
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $term_ids = isset($_POST['term_ids']) ? array_map('intval', (array) $_POST['term_ids']) : array();
        if (empty($term_ids)) {
            wp_send_json_error(array('message' => 'No term IDs received.'));
        }

        foreach ($term_ids as $order_index => $term_id) {
            $order = $order_index + 1;
            update_term_meta($term_id, 'category_order', $order);
            clean_term_cache($term_id, 'download_category');
        }

        wp_send_json_success(array('updated' => count($term_ids)));
    }

    /**
     * Seed default categories & realistic sample files for Downloads
     */
    public function seed_default_download_data()
    {
        return; // Disabled so user-deleted categories are not recreated automatically

        // Default categories with explicit ordering
        $categories = array(
            'forms' => array(
                'name' => 'แบบฟอร์มคำร้องทั่วไป',
                'order' => 1,
                'desc' => 'แบบฟอร์มคำร้องสำหรับนักเรียน นักศึกษา และผู้ปกครอง',
            ),
            'regulations' => array(
                'name' => 'ระเบียบปฏิบัติและข้อบังคับ',
                'order' => 2,
                'desc' => 'ระเบียบวิทยาลัย ข้อบังคับ และประกาศสำคัญ',
            ),
            'handbook' => array(
                'name' => 'คู่มือนักศึกษาและหลักสูตร',
                'order' => 3,
                'desc' => 'คู่มือการเรียน แผนการเรียน และแนวปฏิบัติการศึกษา',
            ),
            'staff' => array(
                'name' => 'เอกสารสำหรับครูและบุคลากร',
                'order' => 4,
                'desc' => 'แบบฟอร์มฝ่ายบุคคล แบบฟอร์มขออนุมัติ และเทมเพลตงานวิทยาลัย',
            ),
        );

        foreach ($categories as $slug => $data) {
            $term = get_term_by('slug', $slug, 'download_category');
            if (!$term) {
                $inserted = wp_insert_term($data['name'], 'download_category', array(
                    'slug' => $slug,
                    'description' => $data['desc'],
                ));
                if (!is_wp_error($inserted)) {
                    update_term_meta($inserted['term_id'], 'category_order', $data['order']);
                }
            } else {
                update_term_meta($term->term_id, 'category_order', $data['order']);
            }
        }

        // Seed sample documents if none exist
        $existing = get_posts(array(
            'post_type' => 'download_file',
            'post_status' => 'any',
            'posts_per_page' => 1,
        ));

        if (empty($existing)) {
            $samples = array(
                array(
                    'title' => 'แบบฟอร์มคำร้องทั่วไป (วทศ.01)',
                    'slug' => 'form-general-request-01',
                    'category' => 'forms',
                    'version' => 'แบบฟอร์ม วทศ.01',
                    'description' => 'แบบฟอร์มสำหรับนักเรียนนักศึกษาใช้ในการยื่นคำร้องขอเอกสารทางการศึกษา ขอหนังสือรับรอง หรือคำร้องทั่วไป',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'pdf',
                    'file_size' => '245 KB',
                    'menu_order' => 1,
                ),
                array(
                    'title' => 'แบบฟอร์มขอผ่อนผันค่าธรรมเนียมการศึกษา',
                    'slug' => 'form-tuition-deferral',
                    'category' => 'forms',
                    'version' => 'แบบฟอร์ม วทศ.02',
                    'description' => 'แบบฟอร์มสำหรับผู้ปกครองและนักศึกษาที่มีความจำเป็นต้องขอผ่อนผันการชำระเงินค่าเล่าเรียน',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'pdf',
                    'file_size' => '198 KB',
                    'menu_order' => 2,
                ),
                array(
                    'title' => 'แบบคำร้องขอลงทะเบียนเรียนล่าช้า / เพิ่ม-ถอนรายวิชา',
                    'slug' => 'form-course-add-drop',
                    'category' => 'forms',
                    'version' => 'แบบฟอร์ม วทศ.03',
                    'description' => 'แบบฟอร์มขอเพิ่มหรือถอนรายวิชา และคำร้องขอลงทะเบียนเรียนเกินกำหนดเวลาที่แผนกทะเบียนกำหนด',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'docx',
                    'file_size' => '142 KB',
                    'menu_order' => 3,
                ),
                array(
                    'title' => 'ระเบียบวิทยาลัยว่าด้วยเครื่องแบบ วินัย และความประพฤตินักศึกษา',
                    'slug' => 'regulations-uniform-discipline',
                    'category' => 'regulations',
                    'version' => 'ฉบับปรับปรุง 2568',
                    'description' => 'ข้อบังคับและแนวปฏิบัติว่าด้วยการแต่งกาย ระเบียบวินัย และการตัดคะแนนความประพฤติของนักเรียนนักศึกษา',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'pdf',
                    'file_size' => '1.2 MB',
                    'menu_order' => 1,
                ),
                array(
                    'title' => 'แนวปฏิบัติและระเบียบการฝึกประสบการณ์ทักษะวิชาชีพในสถานประกอบการ',
                    'slug' => 'regulations-internship-guidelines',
                    'category' => 'regulations',
                    'version' => 'ฉบับปี 2568',
                    'description' => 'คู่มือและข้อปฏิบัติตนสำหรับนักศึกษาระดับ ปวช. และ ปวส. ในระหว่างการฝึกงานในโรงงานและสถานประกอบการ',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'pdf',
                    'file_size' => '890 KB',
                    'menu_order' => 2,
                ),
                array(
                    'title' => 'คู่มือนักศึกษาใหม่และผู้ปกครอง ประจำปีการศึกษา',
                    'slug' => 'handbook-new-students',
                    'category' => 'handbook',
                    'version' => 'Student Handbook',
                    'description' => 'ข้อมูลสำคัญสำหรับนักศึกษาเข้าใหม่ แนะนำอาคารสถานที่ อาจารย์ที่ปรึกษา และระบบบริการการศึกษา',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'pdf',
                    'file_size' => '4.5 MB',
                    'menu_order' => 1,
                ),
                array(
                    'title' => 'ตารางและแผนการเรียนโครงสร้างหลักสูตร ปวช. และ ปวส.',
                    'slug' => 'curriculum-plan-sheet',
                    'category' => 'handbook',
                    'version' => 'ไฟล์ Excel สำหรับวางแผน',
                    'description' => 'ตารางสรุปโครงสร้างหน่วยกิต รายวิชาบังคับ และวิชาเลือก เพื่อการวางแผนการศึกษาตลอดหลักสูตร',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'xlsx',
                    'file_size' => '320 KB',
                    'menu_order' => 2,
                ),
                array(
                    'title' => 'แบบฟอร์มขออนุมัติไปราชการและอบรมสัมมนาบุคลากร',
                    'slug' => 'staff-travel-training-form',
                    'category' => 'staff',
                    'version' => 'แบบฟอร์ม ทส.05',
                    'description' => 'แบบฟอร์มสำหรับคณาจารย์และบุคลากรใช้ขออนุมัติเข้ารับการอบรม สัมมนาทางวิชาการ หรือปฏิบัติราชการภายนอก',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'docx',
                    'file_size' => '115 KB',
                    'menu_order' => 1,
                ),
                array(
                    'title' => 'เทมเพลตสไลด์นำเสนองานทางการของวิทยาลัย (PowerPoint)',
                    'slug' => 'staff-presentation-slide-template',
                    'category' => 'staff',
                    'version' => 'S-TECH Official Template 16:9',
                    'description' => 'ไฟล์เทมเพลตสไลด์บรรยาย Presentation ธีมสีฟ้า-ขาว ตราสัญลักษณ์ทางการ สำหรับใช้ในงานวิชาการและพิธีการ',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'pptx',
                    'file_size' => '3.8 MB',
                    'menu_order' => 2,
                ),
                array(
                    'title' => 'ชุดตราสัญลักษณ์และโลโก้วิทยาลัยความละเอียดสูง (Press & Media Kit)',
                    'slug' => 'staff-college-logo-press-kit',
                    'category' => 'staff',
                    'version' => 'Logo Pack (PNG, SVG, AI)',
                    'description' => 'รวมไฟล์ตราสัญลักษณ์วิทยาลัยเทคโนโลยีศรีราชาครบทุกเฉดสี ทั้งแบบสีทอง สีเงิน และสีขาว สำหรับสื่อสิ่งพิมพ์และกราฟิก',
                    'file_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url' => '',
                    'file_type' => 'zip',
                    'file_size' => '8.2 MB',
                    'menu_order' => 3,
                ),
            );

            foreach ($samples as $sample) {
                $post_id = wp_insert_post(array(
                    'post_title' => $sample['title'],
                    'post_name' => $sample['slug'],
                    'post_status' => 'publish',
                    'post_type' => 'download_file',
                    'post_content' => $sample['description'],
                    'menu_order' => $sample['menu_order'],
                ));

                if ($post_id && !is_wp_error($post_id)) {
                    wp_set_object_terms($post_id, $sample['category'], 'download_category');
                    update_post_meta($post_id, 'download_file_url', $sample['file_url']);
                    update_post_meta($post_id, 'download_external_url', $sample['external_url']);
                    update_post_meta($post_id, 'download_file_type', $sample['file_type']);
                    update_post_meta($post_id, 'download_file_size', $sample['file_size']);
                    update_post_meta($post_id, 'download_version', $sample['version']);
                    update_post_meta($post_id, 'download_description', $sample['description']);
                    update_post_meta($post_id, 'download_order', $sample['menu_order']);
                }
            }
        }
    }

    /**
     * Define custom columns for Student Handbook list table
     */
    public function set_student_handbook_columns($columns)
    {
        $new = array();
        $new['cb'] = $columns['cb'];
        $new['title'] = __('ชื่อคู่มือ / ระเบียบการ', 'project-core');
        $new['handbook_category'] = __('หมวดหมู่', 'project-core');
        $new['handbook_pdf'] = __('ไฟล์แนบ PDF', 'project-core');
        $new['handbook_order'] = __('ลำดับ', 'project-core');
        $new['date'] = $columns['date'];
        return $new;
    }

    /**
     * Render custom column contents for Student Handbook list table
     */
    public function render_student_handbook_columns($column, $post_id)
    {
        switch ($column) {
            case 'handbook_category':
                $terms = get_the_terms($post_id, 'handbook_category');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $links = array();
                    foreach ($terms as $t) {
                        $url = add_query_arg(array('post_type' => 'student_handbook', 'handbook_category' => $t->slug), admin_url('edit.php'));
                        $links[] = '<a href="' . esc_url($url) . '" style="font-weight:600; color:#0047BA;">' . esc_html($t->name) . '</a>';
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;

            case 'handbook_pdf':
                $pdf_url = get_post_meta($post_id, 'handbook_pdf_url', true);
                if (!empty($pdf_url)) {
                    echo '<a href="' . esc_url($pdf_url) . '" target="_blank" style="text-decoration:none; color:#dc2626; font-weight:600; font-size:12px;">';
                    echo '<span class="dashicons dashicons-pdf" style="font-size:16px; vertical-align:middle;"></span> ' . esc_html(__('ดู PDF', 'project-core'));
                    echo '</a>';
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;

            case 'handbook_order':
                $post = get_post($post_id);
                $order = $post ? $post->menu_order : 0;
                echo '<span style="display:inline-block; font-weight:700; font-size:12px; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:999px;">#' . esc_html($order) . '</span>';
                break;
        }
    }

    /**
     * Filter Handbooks by Category in Admin List
     */
    public function filter_handbooks_by_category($post_type, $which)
    {
        if ($post_type !== 'student_handbook') {
            return;
        }

        $selected = isset($_GET['handbook_category']) ? sanitize_text_field($_GET['handbook_category']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'taxonomy' => 'handbook_category',
            'name' => 'handbook_category',
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => true,
            'depth' => 3,
            'show_count' => true,
            'hide_empty' => false,
            'value_field' => 'slug',
        ));
    }

    /**
     * Apply Handbook Category Filter to Query & Default Order
     */
    public function admin_handbook_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'student_handbook') {
            if (!empty($_GET['handbook_category'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'handbook_category',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['handbook_category']),
                    ),
                ));
            }

            if (!isset($_GET['orderby'])) {
                $query->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
            }
        }
    }

    /**
     * Set default order for handbook categories in WP Admin
     */
    public function admin_handbook_category_sort_order($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit-tags.php') {
            $taxonomies = isset($query->query_vars['taxonomy']) ? (array) $query->query_vars['taxonomy'] : array();
            if (in_array('handbook_category', $taxonomies, true)) {
                if (empty($_GET['orderby'])) {
                    $query->query_vars['meta_key'] = 'category_order';
                    $query->query_vars['orderby'] = 'meta_value_num';
                    $query->query_vars['order'] = 'ASC';
                }
            }
        }
    }

    /**
     * Add Category Order Field in Add Term Screen
     */
    public function handbook_category_add_fields()
    {
        ?>
        <div class="form-field term-order-wrap">
            <label for="category_order"><?php _e('ลำดับการแสดงผล (Order)', 'project-core'); ?></label>
            <input type="number" name="category_order" id="category_order" value="0" min="0" style="width: 100px;" />
            <p><?php _e('ตัวเลขน้อยจะแสดงก่อน เช่น 1, 2, 3...', 'project-core'); ?></p>
        </div>
        <?php
    }

    /**
     * Add Category Order Field in Edit Term Screen
     */
    public function handbook_category_edit_fields($term)
    {
        $order = get_term_meta($term->term_id, 'category_order', true);
        if ($order === '')
            $order = 0;
        ?>
        <tr class="form-field term-order-wrap">
            <th scope="row"><label for="category_order"><?php _e('ลำดับการแสดงผล (Order)', 'project-core'); ?></label></th>
            <td>
                <input type="number" name="category_order" id="category_order" value="<?php echo esc_attr($order); ?>" min="0"
                    style="width: 100px;" />
                <p class="description"><?php _e('ตัวเลขน้อยจะแสดงก่อน เช่น 1, 2, 3...', 'project-core'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Handbook Category Order Meta
     */
    public function save_handbook_category_fields($term_id)
    {
        if (isset($_POST['category_order'])) {
            $order = intval($_POST['category_order']);
            update_term_meta($term_id, 'category_order', $order);
        }
    }

    /**
     * Add Order column to handbook_category list table
     */
    public function set_handbook_category_columns($columns)
    {
        $new = array();
        if (isset($columns['cb'])) {
            $new['cb'] = $columns['cb'];
        }
        $new['category_order'] = __('ลำดับ', 'project-core');
        foreach ($columns as $k => $v) {
            if ($k === 'cb')
                continue;
            $new[$k] = $v;
        }
        return $new;
    }

    public function render_handbook_category_columns($content, $column_name, $term_id)
    {
        if ($column_name === 'category_order') {
            $order = get_term_meta($term_id, 'category_order', true);
            $order_num = ($order !== '') ? intval($order) : 0;
            return '<span class="cat-order-badge" style="display:inline-block; font-weight:700; font-size:12px; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:999px;">#' . esc_html($order_num) . '</span>';
        }
        return $content;
    }

    public function set_handbook_category_sortable_columns($columns)
    {
        $columns['category_order'] = 'category_order';
        return $columns;
    }

    /**
     * Seed default Open House categories if none exist
     */
    public function seed_default_openhouse_terms()
    {
        if (!taxonomy_exists('openhouse_category')) {
            return;
        }

        $existing = get_terms(array(
            'taxonomy' => 'openhouse_category',
            'hide_empty' => false,
        ));

        if (!empty($existing) && !is_wp_error($existing)) {
            return;
        }

        $defaults = array(
            array('name' => 'การแข่งขันทักษะวิชาการ', 'slug' => 'academic-competitions'),
            array('name' => 'การแข่งขันทักษะวิชาชีพ', 'slug' => 'vocational-skills'),
            array('name' => 'นิทรรศการผลงานและนวัตกรรม', 'slug' => 'exhibitions-innovations'),
            array('name' => 'กิจกรรมเวิร์กช็อปและเปิดบ้านสาขาวิชา', 'slug' => 'workshops-openhouse'),
        );

        foreach ($defaults as $item) {
            wp_insert_term($item['name'], 'openhouse_category', array('slug' => $item['slug']));
        }
    }

    /**
     * Register Submenu for Open House Settings
     */
    public function register_openhouse_admin_menus()
    {
        add_submenu_page(
            'edit.php?post_type=openhouse_activity',
            __('ข้อมูลงานวันวิชาการ & ตั้งค่า', 'project-core'),
            __('ข้อมูลงาน & ตั้งค่า', 'project-core'),
            'manage_options',
            'openhouse-settings',
            array($this, 'render_openhouse_settings_page')
        );
    }

    /**
     * Render Open House Settings Admin Page
     */
    public function render_openhouse_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'project-core'));
        }

        // Handle Save
        $saved_notice = false;
        if (isset($_POST['project_core_openhouse_settings_nonce']) && wp_verify_nonce($_POST['project_core_openhouse_settings_nonce'], 'project_core_save_openhouse_settings')) {
            $settings = array(
                'event_title' => sanitize_text_field(wp_unslash($_POST['event_title'] ?? '')),
                'event_tagline' => sanitize_text_field(wp_unslash($_POST['event_tagline'] ?? '')),
                'event_logo_url' => esc_url_raw(wp_unslash($_POST['event_logo_url'] ?? '')),
                'event_logo_id' => absint($_POST['event_logo_id'] ?? 0),
                'event_banner_url' => esc_url_raw(wp_unslash($_POST['event_banner_url'] ?? '')),
                'event_banner_id' => absint($_POST['event_banner_id'] ?? 0),
                'event_dates' => sanitize_text_field(wp_unslash($_POST['event_dates'] ?? '')),
                'event_venue' => sanitize_text_field(wp_unslash($_POST['event_venue'] ?? '')),
                'event_description' => wp_kses_post(wp_unslash($_POST['event_description'] ?? '')),
                'event_contact_name' => sanitize_text_field(wp_unslash($_POST['event_contact_name'] ?? '')),
                'event_contact_phone' => sanitize_text_field(wp_unslash($_POST['event_contact_phone'] ?? '')),
                'event_contact_line' => sanitize_text_field(wp_unslash($_POST['event_contact_line'] ?? '')),
                'event_reg_url' => esc_url_raw(wp_unslash($_POST['event_reg_url'] ?? '')),
                'event_livestream_url' => esc_url_raw(wp_unslash($_POST['event_livestream_url'] ?? '')),
            );

            update_option('project_core_openhouse_settings', $settings);
            $saved_notice = true;
        }

        $settings = get_option('project_core_openhouse_settings', array());
        $title = $settings['event_title'] ?? 'งานสัปดาห์วันวิชาการและเปิดบ้าน Open House';
        $tagline = $settings['event_tagline'] ?? 'จุดประกายความคิด สร้างสรรค์นวัตกรรมสู่อาชีพแห่งอนาคต วิทยาลัยเทคโนโลยีศรีราชา';
        $logo_url = $settings['event_logo_url'] ?? '';
        $logo_id = $settings['event_logo_id'] ?? '';
        $banner_url = $settings['event_banner_url'] ?? '';
        $banner_id = $settings['event_banner_id'] ?? '';
        $dates = $settings['event_dates'] ?? '15 - 16 มกราคม 2569 เวลา 08:30 - 16:30 น.';
        $venue = $settings['event_venue'] ?? 'วิทยาลัยเทคโนโลยีศรีราชา อ.ศรีราชา จ.ชลบุรี';
        $description = $settings['event_description'] ?? 'ขอเชิญนักเรียน นักศึกษา ครู อาจารย์ และผู้สนใจ เข้าร่วมงานวันวิชาการและการแข่งขันทักษะวิชาการ-วิชาชีพ ประจำปีการศึกษา 2568 ชมนิทรรศการผลงานสิ่งประดิษฐ์และนวัตกรรม พร้อมร่วมกิจกรรมเวิร์กช็อปจากทุกสาขาวิชา';
        $contact_name = $settings['event_contact_name'] ?? 'ฝ่ายวิชาการและงานประชาสัมพันธ์';
        $contact_phone = $settings['event_contact_phone'] ?? '038-352440';
        $contact_line = $settings['event_contact_line'] ?? '@sriracha';
        $reg_url = $settings['event_reg_url'] ?? '';
        $livestream = $settings['event_livestream_url'] ?? '';

        $frontend_url = 'http://localhost:4321/openhouse';
        ?>
        <div class="wrap" style="max-width: 1000px;">
            <h1 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                🎉 <?php _e('ข้อมูลงานวันวิชาการ (Open House Settings)', 'project-core'); ?>
            </h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 0; margin-bottom: 20px;">
                <?php _e('จัดการข้อมูลภาพรวม โลโก้ และแบนเนอร์ของงานวันวิชาการ เพื่อนำไปแสดงบนหน้าประชาสัมพันธ์ (/openhouse)', 'project-core'); ?>
            </p>

            <?php if ($saved_notice): ?>
                <div class="notice notice-success is-dismissible" style="border-left-color: #0047BA;">
                    <p><strong>✓ <?php _e('บันทึกข้อมูลงานวันวิชาการเรียบร้อยแล้ว!', 'project-core'); ?></strong></p>
                </div>
            <?php endif; ?>

            <!-- Public URL Copy Box Card -->
            <div
                style="background: linear-gradient(135deg, #0047BA 0%, #0369a1 100%); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; color: #ffffff; box-shadow: 0 4px 14px rgba(0,71,186,0.18);">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
                    <div>
                        <span
                            style="display: inline-block; background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                            🚀 ลิงก์หน้าประชาสัมพันธ์งาน (Public Link)
                        </span>
                        <div style="font-size: 17px; font-weight: 700; word-break: break-all; font-family: monospace;">
                            <span id="openhouse-url-text"><?php echo esc_url($frontend_url); ?></span>
                        </div>
                        <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">
                            หน้านี้เป็นหน้าแยกสำหรับประชาสัมพันธ์งานโดยเฉพาะ ไม่ปรากฏในแถบเมนูหลักของเว็บ
                            สามารถคัดลอกลิงก์ไปแชร์ได้ทันที
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; shrink-0;">
                        <button type="button" class="button" id="btn-copy-openhouse-url"
                            style="background: #ffffff; color: #0047BA; border: none; font-weight: 700; padding: 6px 16px; border-radius: 8px; font-size: 13px; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                            📋 คัดลอกลิงก์
                        </button>
                        <a href="<?php echo esc_url($frontend_url); ?>" target="_blank" class="button"
                            style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.4); font-weight: 600; padding: 6px 14px; border-radius: 8px; font-size: 13px; text-decoration: none;">
                            เปิดดูหน้าเว็บจริง ↗
                        </a>
                    </div>
                </div>
            </div>

            <form method="post" action=""
                style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <?php wp_nonce_field('project_core_save_openhouse_settings', 'project_core_openhouse_settings_nonce'); ?>

                <h2
                    style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 0; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    📌 ข้อมูลหลักของงาน (Event Basics)
                </h2>

                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <th scope="row"><label for="event_title">ชื่องานวันวิชาการ <span style="color:#ef4444;">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="event_title" name="event_title" value="<?php echo esc_attr($title); ?>"
                                class="large-text" required
                                placeholder="เช่น งานสัปดาห์วันวิชาการและเปิดบ้าน Open House 2026" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_tagline">สโลแกน / คำขวัญของงาน</label></th>
                        <td>
                            <input type="text" id="event_tagline" name="event_tagline" value="<?php echo esc_attr($tagline); ?>"
                                class="large-text" placeholder="เช่น จุดประกายความคิด สร้างสรรค์นวัตกรรมสู่อาชีพแห่งอนาคต" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_dates">วันที่และเวลาจัดงาน <span
                                    style="color:#ef4444;">*</span></label></th>
                        <td>
                            <input type="text" id="event_dates" name="event_dates" value="<?php echo esc_attr($dates); ?>"
                                class="regular-text" required placeholder="เช่น 15 - 16 มกราคม 2569 เวลา 08:30 - 16:30 น." />
                            <p class="description">ระบุช่วงเวลาจัดงานที่จะแสดงบนแถบหัวข้อและแบนเนอร์</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_venue">สถานที่จัดงานหลัก</label></th>
                        <td>
                            <input type="text" id="event_venue" name="event_venue" value="<?php echo esc_attr($venue); ?>"
                                class="regular-text" placeholder="เช่น วิทยาลัยเทคโนโลยีศรีราชา อ.ศรีราชา จ.ชลบุรี" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_description">คำอธิบายภาพรวม / วัตถุประสงค์งาน</label></th>
                        <td>
                            <textarea id="event_description" name="event_description" rows="4" class="large-text"
                                placeholder="รายละเอียดงาน กิจกรรมไฮไลท์ หรือคำเชิญชวน"><?php echo esc_textarea($description); ?></textarea>
                        </td>
                    </tr>
                </table>

                <h2
                    style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 32px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    🎨 โลโก้และภาพแบนเนอร์ (Visual Assets)
                </h2>

                <table class="form-table" style="margin-top: 0;">
                    <!-- Event Logo -->
                    <tr>
                        <th scope="row"><label for="event_logo_url">โลโก้งาน (Event Logo)</label></th>
                        <td>
                            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 10px;">
                                <div id="openhouse-logo-preview"
                                    style="width: 80px; height: 80px; border: 2px dashed #cbd5e1; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f8fafc;">
                                    <?php if (!empty($logo_url)): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>"
                                            style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                                    <?php else: ?>
                                        <span class="dashicons dashicons-format-image"
                                            style="color: #94a3b8; font-size: 32px; width: 32px; height: 32px;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input type="text" id="event_logo_url" name="event_logo_url"
                                        value="<?php echo esc_attr($logo_url); ?>" class="regular-text"
                                        placeholder="URL รูปโลโก้งาน หรือกดเลือกไฟล์" />
                                    <input type="hidden" id="event_logo_id" name="event_logo_id"
                                        value="<?php echo esc_attr($logo_id); ?>" />
                                    <div style="margin-top: 6px;">
                                        <button type="button" class="button"
                                            id="btn-upload-openhouse-logo">เลือกรูปโลโก้</button>
                                        <button type="button" class="button" id="btn-clear-openhouse-logo"
                                            style="<?php echo empty($logo_url) ? 'display:none;' : ''; ?>">ลบรูป</button>
                                    </div>
                                    <p class="description">แนะนำรูปไฟล์ PNG โปร่งใส หรือ SVG ขนาดประมาณ 300x300 พิกเซล
                                        (ถ้าเว้นว่างจะใช้ตราสัญลักษณ์วิทยาลัย)</p>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Event Banner / Poster -->
                    <tr>
                        <th scope="row"><label for="event_banner_url">ภาพแบนเนอร์ / โปสเตอร์งาน</label></th>
                        <td>
                            <div style="max-width: 600px; margin-bottom: 10px;">
                                <div id="openhouse-banner-preview"
                                    style="width: 100%; aspect-ratio: 16/9; max-height: 220px; border: 2px dashed #cbd5e1; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f8fafc;">
                                    <?php if (!empty($banner_url)): ?>
                                        <img src="<?php echo esc_url($banner_url); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;" />
                                    <?php else: ?>
                                        <div style="text-align: center; color: #94a3b8;">
                                            <span class="dashicons dashicons-format-image"
                                                style="font-size: 36px; width: 36px; height: 36px; margin: 0 auto 4px;"></span>
                                            <div style="font-size: 12px;">ยังไม่ได้เลือกภาพแบนเนอร์/โปสเตอร์</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 8px; display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="event_banner_url" name="event_banner_url"
                                        value="<?php echo esc_attr($banner_url); ?>" class="large-text"
                                        placeholder="URL ภาพโปสเตอร์/แบนเนอร์งาน" />
                                    <input type="hidden" id="event_banner_id" name="event_banner_id"
                                        value="<?php echo esc_attr($banner_id); ?>" />
                                    <button type="button" class="button button-primary"
                                        id="btn-upload-openhouse-banner">เลือกภาพ</button>
                                    <button type="button" class="button" id="btn-clear-openhouse-banner"
                                        style="<?php echo empty($banner_url) ? 'display:none;' : ''; ?>">ลบภาพ</button>
                                </div>
                                <p class="description">ขนาดแนะนำ: 1920x1080 หรือ 1200x675 พิกเซล แนวนอน</p>
                            </div>
                        </td>
                    </tr>
                </table>

                <h2
                    style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 32px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    📞 ช่องทางติดต่อและลิงก์เพิ่มเติม (Contact & Links)
                </h2>

                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <th scope="row"><label for="event_contact_name">ผู้ประสานงาน / หน่วยงาน</label></th>
                        <td>
                            <input type="text" id="event_contact_name" name="event_contact_name"
                                value="<?php echo esc_attr($contact_name); ?>" class="regular-text"
                                placeholder="เช่น ฝ่ายวิชาการและงานวิจัย" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_contact_phone">เบอร์โทรศัพท์ติดต่อ</label></th>
                        <td>
                            <input type="text" id="event_contact_phone" name="event_contact_phone"
                                value="<?php echo esc_attr($contact_phone); ?>" class="regular-text"
                                placeholder="เช่น 038-352440 หรือ 081-xxxxxxx" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_contact_line">Line ID / Official</label></th>
                        <td>
                            <input type="text" id="event_contact_line" name="event_contact_line"
                                value="<?php echo esc_attr($contact_line); ?>" class="regular-text"
                                placeholder="เช่น @sriracha" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_reg_url">ลิงก์ลงทะเบียนเข้าร่วมงาน (รวม)</label></th>
                        <td>
                            <input type="url" id="event_reg_url" name="event_reg_url" value="<?php echo esc_attr($reg_url); ?>"
                                class="large-text" placeholder="https://forms.gle/... (เว้นว่างได้)" />
                            <p class="description">ลิงก์ Google Form หรือระบบลงทะเบียนสำหรับโรงเรียน/ผู้เข้าร่วมงานภาพรวม</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_livestream_url">ลิงก์ถ่ายทอดสด / วิดีโองาน (YouTube / FB Live)</label>
                        </th>
                        <td>
                            <input type="url" id="event_livestream_url" name="event_livestream_url"
                                value="<?php echo esc_attr($livestream); ?>" class="large-text"
                                placeholder="https://www.youtube.com/... หรือ https://facebook.com/... (เว้นว่างได้)" />
                        </td>
                    </tr>
                </table>

                <div
                    style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
                    <button type="submit" class="button button-primary button-large"
                        style="background: #0047BA; border-color: #003893; padding: 4px 28px; font-weight: 700; font-size: 14px;">
                        💾 บันทึกการตั้งค่า
                    </button>
                </div>
            </form>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Copy URL
                $('#btn-copy-openhouse-url').on('click', function () {
                    var url = $('#openhouse-url-text').text().trim();
                    navigator.clipboard.writeText(url).then(function () {
                        var btn = $('#btn-copy-openhouse-url');
                        btn.text('✓ คัดลอกสำเร็จแล้ว!').css({ background: '#22c55e', color: '#fff' });
                        setTimeout(function () {
                            btn.text('📋 คัดลอกลิงก์').css({ background: '#ffffff', color: '#0047BA' });
                        }, 2500);
                    });
                });

                // Media Uploader for Logo
                var logoFrame;
                $('#btn-upload-openhouse-logo').on('click', function (e) {
                    e.preventDefault();
                    if (logoFrame) { logoFrame.open(); return; }
                    logoFrame = wp.media({
                        title: 'เลือกรูปโลโก้งานวันวิชาการ',
                        button: { text: 'ใช้รูปนี้' },
                        multiple: false
                    });
                    logoFrame.on('select', function () {
                        var attachment = logoFrame.state().get('selection').first().toJSON();
                        $('#event_logo_url').val(attachment.url);
                        $('#event_logo_id').val(attachment.id);
                        $('#openhouse-logo-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; max-height: 100%; object-fit: contain;" />');
                        $('#btn-clear-openhouse-logo').show();
                    });
                    logoFrame.open();
                });
                $('#btn-clear-openhouse-logo').on('click', function () {
                    $('#event_logo_url').val('');
                    $('#event_logo_id').val('');
                    $('#openhouse-logo-preview').html('<span class="dashicons dashicons-format-image" style="color: #94a3b8; font-size: 32px; width: 32px; height: 32px;"></span>');
                    $(this).hide();
                });

                // Media Uploader for Banner
                var bannerFrame;
                $('#btn-upload-openhouse-banner').on('click', function (e) {
                    e.preventDefault();
                    if (bannerFrame) { bannerFrame.open(); return; }
                    bannerFrame = wp.media({
                        title: 'เลือกภาพแบนเนอร์/โปสเตอร์งานวันวิชาการ',
                        button: { text: 'ใช้ภาพนี้' },
                        multiple: false
                    });
                    bannerFrame.on('select', function () {
                        var attachment = bannerFrame.state().get('selection').first().toJSON();
                        $('#event_banner_url').val(attachment.url);
                        $('#event_banner_id').val(attachment.id);
                        $('#openhouse-banner-preview').html('<img src="' + attachment.url + '" style="width: 100%; height: 100%; object-fit: cover;" />');
                        $('#btn-clear-openhouse-banner').show();
                    });
                    bannerFrame.open();
                });
                $('#btn-clear-openhouse-banner').on('click', function () {
                    $('#event_banner_url').val('');
                    $('#event_banner_id').val('');
                    $('#openhouse-banner-preview').html('<div style="text-align: center; color: #94a3b8;"><span class="dashicons dashicons-format-image" style="font-size: 36px; width: 36px; height: 36px; margin: 0 auto 4px;"></span><div style="font-size: 12px;">ยังไม่ได้เลือกภาพแบนเนอร์/โปสเตอร์</div></div>');
                    $(this).hide();
                });
            });
        </script>
        <?php
    }

    /**
     * Define columns for Open House Activity list
     */
    public function set_openhouse_activity_columns($columns)
    {
        $new = array();
        $new['cb'] = $columns['cb'];
        $new['activity_thumbnail'] = __('รูปภาพ', 'project-core');
        $new['title'] = __('ชื่อกิจกรรม / การแข่งขัน', 'project-core');
        $new['openhouse_category'] = __('หมวดหมู่', 'project-core');
        $new['activity_venue'] = __('สถานที่', 'project-core');
        $new['activity_date_time'] = __('วันเวลาจัดกิจกรรม', 'project-core');
        $new['activity_rules'] = __('เกณฑ์การแข่งขัน (PDF)', 'project-core');
        $new['activity_photos'] = __('ภาพกิจกรรม', 'project-core');
        $new['activity_cert'] = __('เกียรติบัตร', 'project-core');
        $new['activity_order'] = __('ลำดับ', 'project-core');
        $new['date'] = $columns['date'];
        return $new;
    }

    /**
     * Render column content for Open House Activity list
     */
    public function render_openhouse_activity_columns($column, $post_id)
    {
        switch ($column) {
            case 'activity_thumbnail':
                $thumb_url = get_the_post_thumbnail_url($post_id, 'thumbnail') ?: get_post_meta($post_id, 'activity_cover_url', true);
                if (!empty($thumb_url)) {
                    echo '<img src="' . esc_url($thumb_url) . '" style="width:52px; height:36px; object-fit:cover; border-radius:6px; border:1px solid #cbd5e1;" />';
                } else {
                    echo '<div style="width:52px; height:36px; background:#f1f5f9; border-radius:6px; border:1px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; font-size:16px;">🏆</div>';
                }
                break;
            case 'openhouse_category':
                $terms = get_the_terms($post_id, 'openhouse_category');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $links = array();
                    foreach ($terms as $t) {
                        $url = add_query_arg(array('post_type' => 'openhouse_activity', 'openhouse_category' => $t->slug), admin_url('edit.php'));
                        $links[] = '<a href="' . esc_url($url) . '" style="font-weight:600; color:#0047BA;">' . esc_html($t->name) . '</a>';
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;

            case 'activity_venue':
                $venue = get_post_meta($post_id, 'activity_venue', true);
                if (!empty($venue)) {
                    echo '<span style="font-size:12px; color:#334155;">📍 ' . esc_html($venue) . '</span>';
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;

            case 'activity_date_time':
                $dt = get_post_meta($post_id, 'activity_date_time', true);
                if (!empty($dt)) {
                    echo '<span style="font-size:12px; color:#334155;">📅 ' . esc_html($dt) . '</span>';
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;

            case 'activity_rules':
                $pdf_url = get_post_meta($post_id, 'activity_rules_pdf_url', true);
                if (!empty($pdf_url)) {
                    echo '<a href="' . esc_url($pdf_url) . '" target="_blank" style="text-decoration:none; color:#dc2626; font-weight:600; font-size:12px;">';
                    echo '<span class="dashicons dashicons-pdf" style="font-size:16px; vertical-align:middle;"></span> ' . esc_html(__('มีไฟล์ PDF', 'project-core'));
                    echo '</a>';
                } else {
                    echo '<span style="color:#94a3b8; font-size:12px;">ไม่มีไฟล์แนบ</span>';
                }
                break;

            case 'activity_photos':
                $photos = get_post_meta($post_id, 'activity_photos_url', true);
                if (!empty($photos)) {
                    echo '<a href="' . esc_url($photos) . '" target="_blank" style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:#eff6ff; color:#0047BA; text-decoration:none; border:1px solid #bfdbfe;">';
                    echo '📷 มีลิงก์ภาพ ↗';
                    echo '</a>';
                } else {
                    echo '<span style="display:inline-block; font-size:11px; padding:2px 8px; border-radius:999px; background:#f1f5f9; color:#94a3b8;">(ปุ่ม Disabled)</span>';
                }
                break;

            case 'activity_cert':
                $cert = get_post_meta($post_id, 'activity_certificate_url', true);
                if (!empty($cert)) {
                    echo '<a href="' . esc_url($cert) . '" target="_blank" style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:#ecfdf5; color:#059669; text-decoration:none; border:1px solid #a7f3d0;">';
                    echo '🎓 มีเกียรติบัตร ↗';
                    echo '</a>';
                } else {
                    echo '<span style="display:inline-block; font-size:11px; padding:2px 8px; border-radius:999px; background:#f1f5f9; color:#94a3b8;">(ปุ่ม Disabled)</span>';
                }
                break;

            case 'activity_order':
                $order = get_post_meta($post_id, 'activity_order', true);
                $order_num = ($order !== '') ? intval($order) : 0;
                echo '<span style="font-weight:700; color:#475569;">' . esc_html($order_num) . '</span>';
                break;
        }
    }

    /**
     * Filter Open House Activities by Category dropdown in Admin
     */
    public function filter_openhouse_activities_by_category($post_type, $which)
    {
        if ($post_type !== 'openhouse_activity') {
            return;
        }

        $selected = isset($_GET['openhouse_category']) ? sanitize_text_field($_GET['openhouse_category']) : '';
        $terms = get_terms(array(
            'taxonomy' => 'openhouse_category',
            'hide_empty' => false,
        ));

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        echo '<select name="openhouse_category" id="filter-by-openhouse-cat">';
        echo '<option value="">' . esc_html(__('หมวดหมู่ทั้งหมด', 'project-core')) . '</option>';
        foreach ($terms as $t) {
            $is_sel = ($selected === $t->slug) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr($t->slug) . '"' . $is_sel . '>' . esc_html($t->name) . ' (' . intval($t->count) . ')</option>';
        }
        echo '</select>';
    }

    /**
     * Handle Admin filter query for Open House Activities
     */
    public function admin_openhouse_activity_filter_query($query)
    {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'openhouse_activity') {
            if (!empty($_GET['openhouse_category'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'openhouse_category',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['openhouse_category']),
                    ),
                ));
            }

            // Default order by menu_order asc
            if (!$query->get('orderby')) {
                $query->set('orderby', 'menu_order title');
                $query->set('order', 'ASC');
            }
        }
    }
}


