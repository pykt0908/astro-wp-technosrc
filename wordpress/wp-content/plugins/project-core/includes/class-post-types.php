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
        add_action('restrict_manage_posts', array($this, 'filter_personnel_by_category'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_personnel_filter_query'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_personnel_list_scripts'));
        add_action('admin_footer', array($this, 'render_personnel_reorder_script'));
        add_action('wp_ajax_project_core_reorder_personnel', array($this, 'ajax_reorder_personnel'));
        add_action('wp_ajax_project_core_reorder_personnel_categories', array($this, 'ajax_reorder_personnel_categories'));

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
    }

    /**
     * Register Custom Post Types (Banner & College Event)
     */
    public function register_post_types() {
        // Banner CPT
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

        // College Event CPT (ปฏิทินกิจกรรมวิทยาลัย)
        $event_labels = array(
            'name'                  => __('ปฏิทินกิจกรรม', 'project-core'),
            'singular_name'         => __('กิจกรรม', 'project-core'),
            'menu_name'             => __('ปฏิทินวิทยาลัย', 'project-core'),
            'name_admin_bar'        => __('กิจกรรม', 'project-core'),
            'add_new'               => __('เพิ่มกิจกรรมใหม่', 'project-core'),
            'add_new_item'          => __('เพิ่มกิจกรรมใหม่', 'project-core'),
            'new_item'              => __('กิจกรรมใหม่', 'project-core'),
            'edit_item'             => __('แก้ไขกิจกรรม', 'project-core'),
            'view_item'             => __('ดูกิจกรรม', 'project-core'),
            'all_items'             => __('กิจกรรมทั้งหมด', 'project-core'),
            'search_items'          => __('ค้นหากิจกรรม', 'project-core'),
            'not_found'             => __('ไม่พบกิจกรรมในปฏิทิน', 'project-core'),
            'not_found_in_trash'    => __('ไม่พบกิจกรรมในถังขยะ', 'project-core'),
        );

        $event_args = array(
            'labels'             => $event_labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 21,
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => array('title', 'editor', 'excerpt', 'thumbnail'),
            'show_in_rest'       => true,
            'rest_base'          => 'events',
        );

        register_post_type('college_event', $event_args);

        // Curriculum CPT (หลักสูตรที่เปิดสอน)
        $curriculum_labels = array(
            'name'                  => __('หลักสูตรการศึกษา', 'project-core'),
            'singular_name'         => __('หลักสูตร', 'project-core'),
            'menu_name'             => __('หลักสูตร / สาขาวิชา', 'project-core'),
            'name_admin_bar'        => __('หลักสูตร', 'project-core'),
            'add_new'               => __('เพิ่มหลักสูตรใหม่', 'project-core'),
            'add_new_item'          => __('เพิ่มหลักสูตรใหม่', 'project-core'),
            'new_item'              => __('หลักสูตรใหม่', 'project-core'),
            'edit_item'             => __('แก้ไขหลักสูตร', 'project-core'),
            'view_item'             => __('ดูหลักสูตร', 'project-core'),
            'all_items'             => __('หลักสูตรทั้งหมด', 'project-core'),
            'search_items'          => __('ค้นหาหลักสูตร', 'project-core'),
            'not_found'             => __('ไม่พบหลักสูตรที่ค้นหา', 'project-core'),
            'not_found_in_trash'    => __('ไม่พบหลักสูตรในถังขยะ', 'project-core'),
        );

        $curriculum_args = array(
            'labels'             => $curriculum_labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'curriculum', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 22,
            'menu_icon'          => 'dashicons-welcome-learn-more',
            'supports'           => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'slug'),
            'show_in_rest'       => true,
            'rest_base'          => 'curriculums',
        );

        register_post_type('curriculum', $curriculum_args);

        // Personnel CPT (บุคลากรวิทยาลัย)
        $personnel_labels = array(
            'name'                  => __('บุคลากร', 'project-core'),
            'singular_name'         => __('บุคลากร', 'project-core'),
            'menu_name'             => __('บุคลากร', 'project-core'),
            'name_admin_bar'        => __('บุคลากร', 'project-core'),
            'add_new'               => __('เพิ่มบุคลากรใหม่', 'project-core'),
            'add_new_item'          => __('เพิ่มบุคลากรใหม่', 'project-core'),
            'new_item'              => __('บุคลากรใหม่', 'project-core'),
            'edit_item'             => __('แก้ไขข้อมูลบุคลากร', 'project-core'),
            'view_item'             => __('ดูข้อมูลบุคลากร', 'project-core'),
            'all_items'             => __('บุคลากรทั้งหมด', 'project-core'),
            'search_items'          => __('ค้นหาบุคลากร', 'project-core'),
            'not_found'             => __('ไม่พบข้อมูลบุคลากร', 'project-core'),
            'not_found_in_trash'    => __('ไม่พบข้อมูลบุคลากรในถังขยะ', 'project-core'),
        );

        $personnel_args = array(
            'labels'             => $personnel_labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'personnel', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 23,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest'       => true,
            'rest_base'          => 'personnel',
        );

        register_post_type('personnel', $personnel_args);

        // SAR Report CPT (รายงานผลการประเมินตนเอง)
        $sar_labels = array(
            'name'                  => __('รายงาน SAR', 'project-core'),
            'singular_name'         => __('รายงาน SAR', 'project-core'),
            'menu_name'             => __('รายงาน SAR', 'project-core'),
            'name_admin_bar'        => __('รายงาน SAR', 'project-core'),
            'add_new'               => __('เพิ่มรายงาน SAR', 'project-core'),
            'add_new_item'          => __('เพิ่มรายงาน SAR ใหม่', 'project-core'),
            'new_item'              => __('รายงาน SAR ใหม่', 'project-core'),
            'edit_item'             => __('แก้ไขรายงาน SAR', 'project-core'),
            'view_item'             => __('ดูรายงาน SAR', 'project-core'),
            'all_items'             => __('รายงาน SAR ทั้งหมด', 'project-core'),
            'search_items'          => __('ค้นหารายงาน SAR', 'project-core'),
            'not_found'             => __('ไม่พบรายงาน SAR', 'project-core'),
            'not_found_in_trash'    => __('ไม่พบรายงาน SAR ในถังขยะ', 'project-core'),
        );

        $sar_args = array(
            'labels'             => $sar_labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 24,
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest'       => true,
            'rest_base'          => 'sar-reports',
        );

        register_post_type('sar_report', $sar_args);

        // Educational MIS CPT
        $mis_labels = array(
            'name'                  => __('สารสนเทศทางการศึกษา / Educational MIS', 'project-core'),
            'singular_name'         => __('สารสนเทศทางการศึกษา', 'project-core'),
            'menu_name'             => __('สารสนเทศทางการศึกษา', 'project-core'),
            'name_admin_bar'        => __('สารสนเทศทางการศึกษา', 'project-core'),
            'add_new'               => __('เพิ่มข้อมูลสารสนเทศ', 'project-core'),
            'add_new_item'          => __('เพิ่มข้อมูลสารสนเทศประจำปี', 'project-core'),
            'new_item'              => __('ข้อมูลใหม่', 'project-core'),
            'edit_item'             => __('แก้ไขข้อมูลสารสนเทศ', 'project-core'),
            'view_item'             => __('ดูข้อมูลสารสนเทศ', 'project-core'),
            'all_items'             => __('ข้อมูลสารสนเทศทั้งหมด', 'project-core'),
            'search_items'          => __('ค้นหาข้อมูลสารสนเทศ', 'project-core'),
            'not_found'             => __('ไม่พบข้อมูลสารสนเทศ', 'project-core'),
            'not_found_in_trash'    => __('ไม่พบในถังขยะ', 'project-core'),
        );

        $mis_args = array(
            'labels'             => $mis_labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'education-mis'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-chart-pie',
            'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest'       => true,
            'rest_base'          => 'education-mis-posts',
        );

        register_post_type('education_mis', $mis_args);
    }

    /**
     * Register Taxonomies
     */
    public function register_taxonomies() {
        // Banner Position
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

        // Event Category (หมวดหมู่ปฏิทินกิจกรรม)
        $event_cat_labels = array(
            'name'              => __('หมวดหมู่กิจกรรม', 'project-core'),
            'singular_name'     => __('หมวดหมู่กิจกรรม', 'project-core'),
            'search_items'      => __('ค้นหาหมวดหมู่', 'project-core'),
            'all_items'         => __('หมวดหมู่ทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขหมวดหมู่', 'project-core'),
            'update_item'       => __('อัปเดตหมวดหมู่', 'project-core'),
            'add_new_item'      => __('เพิ่มหมวดหมู่ใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อหมวดหมู่ใหม่', 'project-core'),
            'menu_name'         => __('หมวดหมู่กิจกรรม', 'project-core'),
        );

        $event_cat_args = array(
            'hierarchical'      => true,
            'labels'            => $event_cat_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'event-categories',
        );

        register_taxonomy('event_category', array('college_event'), $event_cat_args);

        // Academic Year Taxonomy (ภาคเรียน / ปีการศึกษา - เช่น 1/2569, 2/2569)
        $academic_year_labels = array(
            'name'              => __('ภาคเรียน / ปีการศึกษา', 'project-core'),
            'singular_name'     => __('ภาคเรียน / ปีการศึกษา', 'project-core'),
            'search_items'      => __('ค้นหาภาคเรียน/ปีการศึกษา', 'project-core'),
            'all_items'         => __('ภาคเรียน/ปีการศึกษาทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขภาคเรียน/ปีการศึกษา', 'project-core'),
            'update_item'       => __('อัปเดตภาคเรียน/ปีการศึกษา', 'project-core'),
            'add_new_item'      => __('เพิ่มภาคเรียน/ปีการศึกษาใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อภาคเรียน/ปีการศึกษาใหม่ (เช่น 1/2569 หรือ 2/2569)', 'project-core'),
            'menu_name'         => __('ภาคเรียน / ปีการศึกษา', 'project-core'),
            'back_to_items'     => __('← กลับไปหน้ารายการภาคเรียน/ปีการศึกษา', 'project-core'),
        );

        $academic_year_args = array(
            'hierarchical'      => true,
            'labels'            => $academic_year_labels,
            'show_ui'           => true,
            'show_admin_column' => false, // Custom column rendered manually
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'academic-years',
        );

        register_taxonomy('academic_year', array('college_event'), $academic_year_args);

        // Curriculum Level Taxonomy (ระดับการศึกษา เช่น ปวช., ปวส.)
        $level_labels = array(
            'name'              => __('ระดับการศึกษา (ปวช./ปวส.)', 'project-core'),
            'singular_name'     => __('ระดับการศึกษา', 'project-core'),
            'search_items'      => __('ค้นหาระดับการศึกษา', 'project-core'),
            'all_items'         => __('ระดับการศึกษาทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขระดับการศึกษา', 'project-core'),
            'update_item'       => __('อัปเดตระดับการศึกษา', 'project-core'),
            'add_new_item'      => __('เพิ่มระดับการศึกษาใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อระดับการศึกษาใหม่', 'project-core'),
            'menu_name'         => __('ระดับการศึกษา', 'project-core'),
        );

        $level_args = array(
            'hierarchical'      => true,
            'labels'            => $level_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'curriculum-levels',
        );

        register_taxonomy('curriculum_level', array('curriculum'), $level_args);

        // Curriculum Department Taxonomy (กลุ่มสาขาวิชา / ประเภทวิชา เช่น ช่างอุตสาหกรรม, บริหารธุรกิจ, เทคโนโลยีสารสนเทศ)
        $dept_labels = array(
            'name'              => __('กลุ่มสาขาวิชา / ประเภทวิชา', 'project-core'),
            'singular_name'     => __('กลุ่มสาขาวิชา', 'project-core'),
            'search_items'      => __('ค้นหากลุ่มสาขาวิชา', 'project-core'),
            'all_items'         => __('กลุ่มสาขาวิชาทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขกลุ่มสาขาวิชา', 'project-core'),
            'update_item'       => __('อัปเดตกลุ่มสาขาวิชา', 'project-core'),
            'add_new_item'      => __('เพิ่มกลุ่มสาขาวิชาใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อกลุ่มสาขาวิชาใหม่', 'project-core'),
            'menu_name'         => __('กลุ่มสาขาวิชา', 'project-core'),
        );

        $dept_args = array(
            'hierarchical'      => true,
            'labels'            => $dept_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'curriculum-departments',
        );

        register_taxonomy('curriculum_department', array('curriculum'), $dept_args);

        // Personnel Category Taxonomy (กลุ่มบุคลากร เช่น ผู้บริหาร, คณาจารย์, ฝ่ายสนับสนุน)
        $personnel_cat_labels = array(
            'name'              => __('กลุ่มบุคลากร', 'project-core'),
            'singular_name'     => __('กลุ่มบุคลากร', 'project-core'),
            'search_items'      => __('ค้นหากลุ่มบุคลากร', 'project-core'),
            'all_items'         => __('กลุ่มบุคลากรทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขกลุ่มบุคลากร', 'project-core'),
            'update_item'       => __('อัปเดตกลุ่มบุคลากร', 'project-core'),
            'add_new_item'      => __('เพิ่มกลุ่มบุคลากรใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อกลุ่มบุคลากรใหม่', 'project-core'),
            'menu_name'         => __('กลุ่มบุคลากร', 'project-core'),
        );

        $personnel_cat_args = array(
            'hierarchical'      => true,
            'labels'            => $personnel_cat_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'personnel-categories',
        );

        register_taxonomy('personnel_category', array('personnel'), $personnel_cat_args);

        // SAR Year Taxonomy (ปีการศึกษา)
        $sar_year_labels = array(
            'name'              => __('ปีการศึกษา SAR', 'project-core'),
            'singular_name'     => __('ปีการศึกษา SAR', 'project-core'),
            'search_items'      => __('ค้นหาปีการศึกษา', 'project-core'),
            'all_items'         => __('ปีการศึกษาทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขปีการศึกษา', 'project-core'),
            'update_item'       => __('อัปเดตปีการศึกษา', 'project-core'),
            'add_new_item'      => __('เพิ่มปีการศึกษาใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อปีการศึกษาใหม่ (เช่น 2568)', 'project-core'),
            'menu_name'         => __('ปีการศึกษา', 'project-core'),
        );

        $sar_year_args = array(
            'hierarchical'      => true,
            'labels'            => $sar_year_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'sar-years',
        );

        register_taxonomy('sar_year', array('sar_report'), $sar_year_args);

        // Academic Year Taxonomy for Educational MIS
        $mis_year_labels = array(
            'name'              => __('ปีการศึกษา / Academic Years', 'project-core'),
            'singular_name'     => __('ปีการศึกษา', 'project-core'),
            'search_items'      => __('ค้นหาปีการศึกษา', 'project-core'),
            'all_items'         => __('ปีการศึกษาทั้งหมด', 'project-core'),
            'edit_item'         => __('แก้ไขปีการศึกษา', 'project-core'),
            'update_item'       => __('อัปเดตปีการศึกษา', 'project-core'),
            'add_new_item'      => __('เพิ่มปีการศึกษาใหม่', 'project-core'),
            'new_item_name'     => __('ชื่อปีการศึกษาใหม่', 'project-core'),
            'menu_name'         => __('ปีการศึกษา', 'project-core'),
        );

        $mis_year_args = array(
            'hierarchical'      => true,
            'labels'            => $mis_year_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'mis-academic-years',
        );

        register_taxonomy('mis_academic_year', array('education_mis'), $mis_year_args);

        // Seed default terms if empty
        $this->seed_default_curriculum_terms();
        $this->seed_default_personnel_data();
        $this->seed_default_sar_data();
        $this->seed_default_education_mis_data();
    }

    /**
     * Define custom columns in Banners admin list
     */
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
    public function set_event_columns($columns) {
        $new_columns = array();
        $new_columns['cb']                      = $columns['cb'];
        $new_columns['title']                   = __('ชื่องาน / กิจกรรม', 'project-core');
        $new_columns['academic_year']           = __('ภาคเรียน / ปีการศึกษา', 'project-core');
        $new_columns['event_date']              = __('วันที่จัดกิจกรรม', 'project-core');
        $new_columns['event_time']              = __('เวลา', 'project-core');
        $new_columns['event_location']          = __('สถานที่', 'project-core');
        $new_columns['taxonomy-event_category'] = __('หมวดหมู่', 'project-core');
        $new_columns['is_featured']             = __('ไฮไลท์', 'project-core');
        $new_columns['date']                    = __('วันที่สร้าง', 'project-core');
        return $new_columns;
    }

    /**
     * Render custom columns in College Events admin list
     */
    public function render_event_columns($column, $post_id) {
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
                $end   = get_post_meta($post_id, 'event_end_date', true);
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
    public function admin_banner_sort_order($query) {
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
    public function enqueue_banner_list_scripts($hook) {
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
    public function render_banner_reorder_script() {
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
                box-shadow: 0 6px 22px rgba(0,0,0,0.25);
                font-size: 14px;
            }
        </style>
        <div id="banner-reorder-notice">✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว</div>
        <script>
        jQuery(function($) {
            var $tbody = $('#the-list');
            if (!$tbody.length) return;

            function showNotice(msg) {
                var $notice = $('#banner-reorder-notice');
                $notice.text(msg || '✓ บันทึกลำดับแบนเนอร์เรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                setTimeout(function() {
                    $notice.fadeOut(400);
                }, 2500);
            }

            function updateOrderNumbers() {
                $tbody.find('tr').each(function(index) {
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
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function() {
                    updateOrderNumbers();
                    var postIds = [];
                    $tbody.find('tr').each(function() {
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
                        success: function(res) {
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
    public function ajax_reorder_banners() {
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
    public function filter_events_by_category($post_type, $which) {
        if ($post_type !== 'college_event' || $which !== 'top') {
            return;
        }

        // 1. Academic Year filter (from academic_year taxonomy)
        $selected_year = isset($_GET['filter_academic_year']) ? sanitize_text_field($_GET['filter_academic_year']) : '';
        $year_terms = get_terms(array(
            'taxonomy'   => 'academic_year',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'DESC',
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
            'taxonomy'        => 'event_category',
            'name'            => 'event_category',
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'depth'           => 3,
            'show_count'      => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ));
    }

    /**
     * Filter College Events by taxonomy and academic year in admin query
     */
    public function admin_event_filter_query($query) {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'college_event') {
            $tax_query = $query->get('tax_query') ?: array();

            // Category filter
            if (isset($_GET['event_category']) && !empty($_GET['event_category']) && $_GET['event_category'] !== '0') {
                $slug = sanitize_text_field($_GET['event_category']);
                $tax_query[] = array(
                    'taxonomy' => 'event_category',
                    'field'    => 'slug',
                    'terms'    => $slug,
                );
            }

            // Academic Year taxonomy filter
            if (isset($_GET['filter_academic_year']) && !empty($_GET['filter_academic_year'])) {
                $ay_slug = sanitize_text_field($_GET['filter_academic_year']);
                $tax_query[] = array(
                    'taxonomy' => 'academic_year',
                    'field'    => 'slug',
                    'terms'    => $ay_slug,
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
    public function seed_default_curriculum_terms() {
        if (!taxonomy_exists('curriculum_level') || !taxonomy_exists('curriculum_department')) {
            return;
        }

        // Default levels: ปวช. and ปวส.
        $levels = array(
            'vocational'      => 'ระดับประกาศนียบัตรวิชาชีพ (ปวช.)',
            'high-vocational' => 'ระดับประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.)',
        );

        foreach ($levels as $slug => $name) {
            if (!term_exists($slug, 'curriculum_level')) {
                wp_insert_term($name, 'curriculum_level', array('slug' => $slug));
            }
        }

        // Default departments
        $departments = array(
            'industrial'             => 'ช่างอุตสาหกรรม',
            'business'               => 'บริหารธุรกิจและพาณิชยกรรม',
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
    public function set_curriculum_columns($columns) {
        $new_columns = array();
        $new_columns['cb']                    = $columns['cb'];
        $new_columns['curriculum_thumb']      = __('รูปภาพ', 'project-core');
        $new_columns['title']                 = __('ชื่อหลักสูตร / สาขาวิชา', 'project-core');
        $new_columns['curriculum_level']      = __('ระดับการศึกษา', 'project-core');
        $new_columns['curriculum_department'] = __('กลุ่มสาขาวิชา', 'project-core');
        $new_columns['curriculum_order']      = __('ลำดับ', 'project-core');
        $new_columns['date']                  = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for Curriculum
     */
    public function render_curriculum_columns($column, $post_id) {
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
    public function filter_curriculums($post_type, $which) {
        if ($post_type !== 'curriculum') {
            return;
        }

        // 1. Level Filter
        $selected_level = isset($_GET['curriculum_level']) ? sanitize_text_field($_GET['curriculum_level']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => __('— เลือกระดับการศึกษา (ทั้งหมด) —', 'project-core'),
            'taxonomy'        => 'curriculum_level',
            'name'            => 'curriculum_level',
            'orderby'         => 'name',
            'selected'        => $selected_level,
            'hierarchical'    => true,
            'depth'           => 2,
            'show_count'      => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ));

        // 2. Department Filter
        $selected_dept = isset($_GET['curriculum_department']) ? sanitize_text_field($_GET['curriculum_department']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => __('— เลือกกลุ่มสาขาวิชา (ทั้งหมด) —', 'project-core'),
            'taxonomy'        => 'curriculum_department',
            'name'            => 'curriculum_department',
            'orderby'         => 'name',
            'selected'        => $selected_dept,
            'hierarchical'    => true,
            'depth'           => 2,
            'show_count'      => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ));
    }

    /**
     * Filter Curriculum admin query by level and department
     */
    public function admin_curriculum_filter_query($query) {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'curriculum') {
            $tax_query = $query->get('tax_query') ?: array();

            if (isset($_GET['curriculum_level']) && !empty($_GET['curriculum_level']) && $_GET['curriculum_level'] !== '0') {
                $slug = sanitize_text_field($_GET['curriculum_level']);
                $tax_query[] = array(
                    'taxonomy' => 'curriculum_level',
                    'field'    => 'slug',
                    'terms'    => $slug,
                );
            }

            if (isset($_GET['curriculum_department']) && !empty($_GET['curriculum_department']) && $_GET['curriculum_department'] !== '0') {
                $slug = sanitize_text_field($_GET['curriculum_department']);
                $tax_query[] = array(
                    'taxonomy' => 'curriculum_department',
                    'field'    => 'slug',
                    'terms'    => $slug,
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
    public function seed_default_personnel_data() {
        if (!taxonomy_exists('personnel_category')) {
            return;
        }

        // 11 College Categories matching organizational structure
        $categories = array(
            'advisory'         => array('name' => 'ที่ปรึกษาสถานศึกษา', 'desc' => 'คณะที่ปรึกษาและผู้รับใบอนุญาตวิทยาลัย', 'order' => 1),
            'executive'        => array('name' => 'ผู้บริหารสถานศึกษา', 'desc' => 'คณะผู้บริหารและผู้อำนวยการวิทยาลัย', 'order' => 2),
            'general'          => array('name' => 'สาขาวิชาสามัญ', 'desc' => 'คณาจารย์หมวดวิชาสามัญสัมพันธ์และภาษา', 'order' => 3),
            'accounting'       => array('name' => 'สาขาวิชาการบัญชี', 'desc' => 'คณาจารย์สาขาวิชาการบัญชี', 'order' => 4),
            'digital-business' => array('name' => 'สาขาเทคโนโลยีธุรกิจดิจิทัล', 'desc' => 'คณาจารย์สาขาเทคโนโลยีธุรกิจดิจิทัลและคอมพิวเตอร์', 'order' => 5),
            'marketing'        => array('name' => 'สาขาวิชาการตลาด', 'desc' => 'คณาจารย์สาขาวิชาการตลาดดิจิทัล', 'order' => 6),
            'logistics'        => array('name' => 'สาขาวิชาโลจิสติกส์และซัพพลายเชน', 'desc' => 'คณาจารย์สาขาวิชาการจัดการโลจิสติกส์', 'order' => 7),
            'electrical'       => array('name' => 'สาขาอุตสาหกรรมไฟฟ้า', 'desc' => 'คณาจารย์สาขาวิชาช่างไฟฟ้ากำลัง', 'order' => 8),
            'mechanical'       => array('name' => 'สาขาอุตสาหกรรมเทคนิคเครื่องกล', 'desc' => 'คณาจารย์สาขาวิชาช่างยนต์และเทคนิคเครื่องกล', 'order' => 9),
            'mechatronics'     => array('name' => 'สาขาเมคาทรอนิกส์และหุ่นยนต์', 'desc' => 'คณาจารย์สาขาวิชาเมคาทรอนิกส์และระบบอัตโนมัติ', 'order' => 10),
            'support'          => array('name' => 'บุคลากรสายสนับสนุน', 'desc' => 'บุคลากรฝ่ายสนับสนุน เจ้าหน้าที่ธุรการ ทะเบียน และการเงิน', 'order' => 11),
        );

        foreach ($categories as $slug => $data) {
            $term = get_term_by('slug', $slug, 'personnel_category');
            if (!$term) {
                $inserted = wp_insert_term($data['name'], 'personnel_category', array(
                    'slug'        => $slug,
                    'description' => $data['desc'],
                ));
                if (!is_wp_error($inserted) && isset($inserted['term_id'])) {
                    update_term_meta($inserted['term_id'], 'category_order', $data['order']);
                }
            } else {
                update_term_meta($term->term_id, 'category_order', $data['order']);
            }
        }

        // Seed or update sample staff
        $count = wp_count_posts('personnel');
        if (isset($count->publish) && (int)$count->publish <= 11) {
            // Ensure advisory staff exist
            $sample_data = array(
                array(
                    'title'      => 'นางหนึ่งฤทัย อินทเศียร',
                    'nickname'   => 'อาจารย์หนึ่ง',
                    'category'   => 'advisory',
                    'order'      => 1,
                    'position'   => 'ผู้รับใบอนุญาตฯ',
                    'department' => 'ที่ปรึกษาสถานศึกษา',
                    'education'  => 'ปริญญาโท บริหารการศึกษา',
                    'email'      => 'nuengruthai@technosriracha.ac.th',
                    'phone'      => '038-352440 ต่อ 100',
                ),
                array(
                    'title'      => 'นางปฤษณา บุญจิตสิทธิ์ศักดิ์',
                    'nickname'   => 'อาจารย์แม่',
                    'category'   => 'advisory',
                    'order'      => 2,
                    'position'   => 'ที่ปรึกษาวิทยาลัยฯ',
                    'department' => 'ที่ปรึกษาสถานศึกษา',
                    'education'  => 'ปริญญาโท',
                    'email'      => 'advisor@technosriracha.ac.th',
                    'phone'      => '038-352440',
                ),
                array(
                    'title'      => 'นางบุญนภัส รินทร์คำ',
                    'nickname'   => '',
                    'category'   => 'executive',
                    'order'      => 3,
                    'position'   => 'ผู้อำนวยการวิทยาลัยเทคโนโลยีศรีราชา',
                    'department' => 'ผู้บริหารสถานศึกษา',
                    'education'  => 'ปริญญาโท บริหารการศึกษา',
                    'email'      => 'director@technosriracha.ac.th',
                    'phone'      => '038-352440',
                ),
                array(
                    'title'      => 'นายเขมวิทย์ บุญจิตสิทธิ์ศักดิ์',
                    'nickname'   => '',
                    'category'   => 'executive',
                    'order'      => 4,
                    'position'   => 'ผู้จัดการวิทยาลัยเทคโนโลยีศรีราชา',
                    'department' => 'ผู้บริหารสถานศึกษา',
                    'education'  => 'ปริญญาโท บริหารธุรกิจ',
                    'email'      => 'manager@technosriracha.ac.th',
                    'phone'      => '038-352440 ต่อ 101',
                ),
                array(
                    'title'      => 'นายอานนท์ สุขสวัสดิ์',
                    'nickname'   => '',
                    'category'   => 'executive',
                    'order'      => 5,
                    'position'   => 'รองผู้อำนวยการฝ่ายวิชาการ',
                    'department' => 'ผู้บริหารสถานศึกษา',
                    'education'  => 'ปริญญาโท ครุศาสตร์อุตสาหกรรมมหาบัณฑิต',
                    'email'      => 'academic.vp@technosriracha.ac.th',
                    'phone'      => '038-352440 ต่อ 103',
                ),
            );

            foreach ($sample_data as $data) {
                $existing_posts = get_posts(array(
                    'post_type'              => 'personnel',
                    'title'                  => $data['title'],
                    'post_status'            => 'all',
                    'posts_per_page'         => 1,
                    'no_found_rows'          => true,
                    'ignore_sticky_posts'    => true,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                ));
                $existing = !empty($existing_posts) ? $existing_posts[0] : null;
                if ($existing) {
                    wp_set_object_terms($existing->ID, $data['category'], 'personnel_category');
                    update_post_meta($existing->ID, 'personnel_position', $data['position']);
                    update_post_meta($existing->ID, 'personnel_nickname', $data['nickname']);
                    update_post_meta($existing->ID, 'personnel_department', $data['department']);
                    update_post_meta($existing->ID, 'personnel_education', $data['education']);
                    update_post_meta($existing->ID, 'personnel_email', $data['email']);
                    update_post_meta($existing->ID, 'personnel_phone', $data['phone']);
                    wp_update_post(array('ID' => $existing->ID, 'menu_order' => $data['order']));
                } else {
                    $pid = wp_insert_post(array(
                        'post_title'   => $data['title'],
                        'post_type'    => 'personnel',
                        'post_status'  => 'publish',
                        'menu_order'   => $data['order'],
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
        }
    }

    /**
     * Columns for Personnel admin list
     */
    public function set_personnel_columns($columns) {
        $new_columns = array();
        $new_columns['cb']               = $columns['cb'];
        $new_columns['personnel_handle'] = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเพื่อสลับลำดับ', 'project-core') . '"></span>';
        $new_columns['personnel_order']  = __('ลำดับ', 'project-core');
        $new_columns['personnel_photo']  = __('รูปถ่าย', 'project-core');
        $new_columns['title']            = __('ชื่อ - สกุล', 'project-core');
        $new_columns['personnel_pos']    = __('ตำแหน่ง', 'project-core');
        $new_columns['personnel_dept']   = __('ฝ่าย / แผนกวิชา', 'project-core');
        $new_columns['taxonomy-personnel_category'] = __('กลุ่มบุคลากร', 'project-core');
        $new_columns['date']             = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for Personnel
     */
    public function render_personnel_columns($column, $post_id) {
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
    public function admin_personnel_sort_order($query) {
        if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'personnel') {
            if (!isset($_GET['orderby'])) {
                $query->set('orderby', 'menu_order');
                $query->set('order', 'ASC');
            }
        }
    }

    /**
     * Filter Personnel by category in admin table
     */
    public function filter_personnel_by_category($post_type, $which) {
        if ($post_type !== 'personnel') {
            return;
        }

        $selected = isset($_GET['personnel_category']) ? sanitize_text_field($_GET['personnel_category']) : '';
        wp_dropdown_categories(array(
            'show_option_all' => '— กลุ่มบุคลากรทั้งหมด —',
            'taxonomy'        => 'personnel_category',
            'name'            => 'personnel_category',
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'depth'           => 2,
            'show_count'      => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ));
    }

    /**
     * Filter Personnel query by category in admin
     */
    public function admin_personnel_filter_query($query) {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && $query->get('post_type') === 'personnel') {
            if (isset($_GET['personnel_category']) && !empty($_GET['personnel_category']) && $_GET['personnel_category'] !== '0') {
                $tax_query = $query->get('tax_query') ?: array();
                $tax_query[] = array(
                    'taxonomy' => 'personnel_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_GET['personnel_category']),
                );
                $query->set('tax_query', $tax_query);
            }
        }
    }

    /**
     * Enqueue jQuery UI Sortable in Personnel list and Personnel Category taxonomy view
     */
    public function enqueue_personnel_list_scripts($hook) {
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
        }
    }

    /**
     * Render inline styles and drag & drop reorder script for Personnel & Categories
     */
    public function render_personnel_reorder_script() {
        $screen = get_current_screen();
        if (!$screen) return;

        // 1. Reordering Personnel posts
        if ($screen->id === 'edit-personnel') {
            $nonce = wp_create_nonce('project_core_personnel_reorder_nonce');
            ?>
            <style>
                .wp-list-table tbody#the-list tr { cursor: grab; transition: background 0.15s ease; }
                .wp-list-table tbody#the-list tr:hover { background-color: #f0f6fc !important; }
                .wp-list-table tbody#the-list tr.ui-sortable-helper {
                    background-color: #ffffff !important;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22) !important;
                    display: table !important; cursor: grabbing !important;
                    border: 2px solid #0047BA !important;
                }
                .wp-list-table tbody#the-list tr.ui-sortable-placeholder {
                    background-color: #e7f3ff !important; visibility: visible !important;
                    outline: 2px dashed #0047BA !important; height: 55px !important;
                }
                .personnel-drag-handle { cursor: grab; color: #50575e; font-size: 20px; display: inline-block; vertical-align: middle; }
                .column-personnel_handle { width: 32px !important; text-align: center; }
                .column-personnel_order { width: 70px !important; text-align: center; }
                .column-personnel_photo { width: 60px !important; text-align: center; }
                #personnel-reorder-notice {
                    display: none; position: fixed; top: 45px; right: 30px; z-index: 999999;
                    padding: 12px 24px; background: #007017; color: #ffffff; font-weight: 600;
                    border-radius: 6px; box-shadow: 0 6px 22px rgba(0,0,0,0.25); font-size: 14px;
                }
            </style>
            <div id="personnel-reorder-notice">✓ บันทึกลำดับบุคลากรเรียบร้อยแล้ว</div>
            <script>
            jQuery(function($) {
                var $tbody = $('#the-list');
                if (!$tbody.length) return;
                function showNotice(msg) {
                    $('#personnel-reorder-notice').text(msg || '✓ บันทึกลำดับบุคลากรเรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                    setTimeout(function() { $('#personnel-reorder-notice').fadeOut(400); }, 2500);
                }
                function updateOrderNumbers() {
                    $tbody.find('tr').each(function(index) {
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
                    helper: function(e, tr) {
                        var $originals = tr.children();
                        var $helper = tr.clone();
                        $helper.children().each(function(index) { $(this).width($originals.eq(index).width()); });
                        return $helper;
                    },
                    update: function() {
                        updateOrderNumbers();
                        var postIds = [];
                        $tbody.find('tr').each(function() {
                            var rowId = $(this).attr('id');
                            if (rowId && rowId.indexOf('post-') === 0) postIds.push(rowId.replace('post-', ''));
                        });
                        if (!postIds.length) return;
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'project_core_reorder_personnel',
                                nonce: '<?php echo esc_js($nonce); ?>',
                                post_ids: postIds
                            },
                            success: function(res) {
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
                .wp-list-table tbody#the-list tr { cursor: grab; transition: background 0.15s ease; }
                .wp-list-table tbody#the-list tr:hover { background-color: #f0f6fc !important; }
                .wp-list-table tbody#the-list tr.ui-sortable-helper {
                    background-color: #ffffff !important; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22) !important;
                    display: table !important; cursor: grabbing !important; border: 2px solid #0284c7 !important;
                }
                .wp-list-table tbody#the-list tr.ui-sortable-placeholder {
                    background-color: #e0f2fe !important; visibility: visible !important;
                    outline: 2px dashed #0284c7 !important; height: 45px !important;
                }
                .category-drag-handle { cursor: grab; color: #50575e; font-size: 20px; display: inline-block; vertical-align: middle; }
                .column-category_handle { width: 32px !important; text-align: center; }
                .column-category_order { width: 65px !important; text-align: center; }
                #personnel-cat-reorder-notice {
                    display: none; position: fixed; top: 45px; right: 30px; z-index: 999999;
                    padding: 12px 24px; background: #0284c7; color: #ffffff; font-weight: 600;
                    border-radius: 6px; box-shadow: 0 6px 22px rgba(0,0,0,0.25); font-size: 14px;
                }
            </style>
            <div id="personnel-cat-reorder-notice">✓ บันทึกลำดับกลุ่มบุคลากรเรียบร้อยแล้ว</div>
            <script>
            jQuery(function($) {
                var $tbody = $('#the-list');
                if (!$tbody.length) return;
                function showNotice(msg) {
                    $('#personnel-cat-reorder-notice').text(msg || '✓ บันทึกลำดับกลุ่มบุคลากรเรียบร้อยแล้ว').stop(true, true).fadeIn(200);
                    setTimeout(function() { $('#personnel-cat-reorder-notice').fadeOut(400); }, 2500);
                }
                function updateCatOrderNumbers() {
                    $tbody.find('tr').each(function(index) {
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
                    helper: function(e, tr) {
                        var $originals = tr.children();
                        var $helper = tr.clone();
                        $helper.children().each(function(index) { $(this).width($originals.eq(index).width()); });
                        return $helper;
                    },
                    update: function() {
                        updateCatOrderNumbers();
                        var termIds = [];
                        $tbody.find('tr').each(function() {
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
                            success: function(res) {
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
    public function ajax_reorder_personnel() {
        check_ajax_referer('project_core_personnel_reorder_nonce', 'nonce');

        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : array();
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'No post IDs received.'));
        }

        global $wpdb;
        foreach ($post_ids as $order_index => $post_id) {
            $menu_order = $order_index + 1;
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
     * AJAX handler to save personnel category order
     */
    public function ajax_reorder_personnel_categories() {
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
    public function set_sar_report_columns($columns) {
        $new_columns = array();
        $new_columns['cb']          = $columns['cb'];
        $new_columns['sar_cover']   = __('หน้าปก / ภาพตัวอย่าง', 'project-core');
        $new_columns['title']       = __('ชื่อรายงาน SAR', 'project-core');
        $new_columns['taxonomy-sar_year'] = __('ปีการศึกษา', 'project-core');
        $new_columns['sar_file']    = __('ไฟล์แนบ (PDF)', 'project-core');
        $new_columns['sar_order']   = __('ลำดับ', 'project-core');
        $new_columns['date']        = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column contents for SAR reports
     */
    public function render_sar_report_columns($column, $post_id) {
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
    public function filter_sar_reports_by_year($post_type, $which) {
        if ($post_type === 'sar_report') {
            $selected = isset($_GET['sar_year']) ? sanitize_text_field($_GET['sar_year']) : '';
            wp_dropdown_categories(array(
                'show_option_all' => __('— ทุกปีการศึกษา —', 'project-core'),
                'taxonomy'        => 'sar_year',
                'name'            => 'sar_year',
                'orderby'         => 'name',
                'order'           => 'DESC',
                'selected'        => $selected,
                'hierarchical'    => true,
                'value_field'     => 'slug',
                'hide_empty'      => false,
            ));
        }
    }

    /**
     * Admin query filter for SAR reports
     */
    public function admin_sar_report_filter_query($query) {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'sar_report') {
            if (!empty($_GET['sar_year'])) {
                $query->query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => 'sar_year',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['sar_year']),
                    ),
                );
            }
            if (!isset($_GET['orderby'])) {
                $query->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
            }
        }
    }

    /**
     * Seed default SAR years and sample reports
     */
    public function seed_default_sar_data() {
        if (!taxonomy_exists('sar_year') || !post_type_exists('sar_report')) {
            return;
        }

        // Default SAR years: 2568, 2567, 2566, 2565
        $years = array(
            '2568' => 'ปีการศึกษา 2568',
            '2567' => 'ปีการศึกษา 2567',
            '2566' => 'ปีการศึกษา 2566',
            '2565' => 'ปีการศึกษา 2565',
        );

        foreach ($years as $slug => $name) {
            if (!term_exists($slug, 'sar_year')) {
                wp_insert_term($name, 'sar_year', array('slug' => $slug));
            }
        }

        // Seed 3 realistic sample SAR reports if no reports exist yet
        $existing = get_posts(array(
            'post_type'      => 'sar_report',
            'post_status'    => 'any',
            'posts_per_page' => 1,
        ));

        if (empty($existing)) {
            $samples = array(
                array(
                    'title'       => 'รายงานการประเมินตนเองของสถานศึกษา (SAR) ประจำปีการศึกษา 2568',
                    'slug'        => 'sar-report-2568',
                    'year'        => '2568',
                    'report_no'   => 'SAR-2568-01',
                    'description' => 'รายงานผลการประเมินตนเองของวิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2568 ตามมาตรฐานการอาชีวศึกษา ประกอบด้วยผลการดำเนินงาน 3 มาตรฐาน 9 ประเด็นการประเมิน',
                    'file_url'    => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url'=> '',
                    'menu_order'  => 1,
                ),
                array(
                    'title'       => 'รายงานการประเมินตนเองของสถานศึกษา (SAR) ประจำปีการศึกษา 2567',
                    'slug'        => 'sar-report-2567',
                    'year'        => '2567',
                    'report_no'   => 'SAR-2567-01',
                    'description' => 'รายงานผลการประเมินตนเองของวิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2567 มุ่งเน้นการยกระดับคุณภาพการจัดการศึกษาสู่มาตรฐานสากลและความร่วมมือกับสถานประกอบการ',
                    'file_url'    => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url'=> '',
                    'menu_order'  => 1,
                ),
                array(
                    'title'       => 'รายงานการประเมินตนเองของสถานศึกษา (SAR) ประจำปีการศึกษา 2566',
                    'slug'        => 'sar-report-2566',
                    'year'        => '2566',
                    'report_no'   => 'SAR-2566-01',
                    'description' => 'รายงานผลการประเมินตนเองของวิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2566 สรุปผลสัมฤทธิ์ทางการเรียนและการพัฒนาทักษะวิชาชีพของนักเรียนนักศึกษา',
                    'file_url'    => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'external_url'=> '',
                    'menu_order'  => 1,
                ),
            );

            foreach ($samples as $sample) {
                $post_id = wp_insert_post(array(
                    'post_title'   => $sample['title'],
                    'post_name'    => $sample['slug'],
                    'post_status'  => 'publish',
                    'post_type'    => 'sar_report',
                    'post_content' => $sample['description'],
                    'menu_order'   => $sample['menu_order'],
                ));

                if ($post_id && !is_wp_error($post_id)) {
                    wp_set_object_terms($post_id, $sample['year'], 'sar_year');
                    update_post_meta($post_id, 'sar_file_url', $sample['file_url']);
                    update_post_meta($post_id, 'sar_report_no', $sample['report_no']);
                    update_post_meta($post_id, 'sar_external_url', $sample['external_url']);
                }
            }
        }
    }

    /**
     * Set columns for Educational MIS admin table
     */
    public function set_education_mis_columns($columns) {
        $new_columns = array();
        $new_columns['cb']               = $columns['cb'];
        $new_columns['title']            = __('ปีการศึกษา / รายการสารสนเทศ', 'project-core');
        $new_columns['mis_year']         = __('ปีการศึกษา', 'project-core');
        $new_columns['students_count']   = __('จำนวนนักศึกษา', 'project-core');
        $new_columns['personnel_count']  = __('จำนวนบุคลากร', 'project-core');
        $new_columns['employment_rate']  = __('อัตรามีงานทำ/ศึกษาต่อ', 'project-core');
        $new_columns['pdf_file']         = __('ไฟล์รายงาน PDF', 'project-core');
        $new_columns['date']             = $columns['date'];
        return $new_columns;
    }

    /**
     * Render Educational MIS admin columns
     */
    public function render_education_mis_columns($column, $post_id) {
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
                    echo '<strong>' . number_format((int)$total) . '</strong> <span style="color:#64748b;">คน</span>';
                } else {
                    echo '—';
                }
                break;

            case 'personnel_count':
                $total = get_post_meta($post_id, 'mis_personnel_total', true);
                if (!empty($total)) {
                    echo '<strong>' . number_format((int)$total) . '</strong> <span style="color:#64748b;">คน</span>';
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
    public function filter_education_mis_by_year($post_type, $which) {
        if ($post_type === 'education_mis') {
            $selected = isset($_GET['mis_academic_year']) ? sanitize_text_field($_GET['mis_academic_year']) : '';
            $terms    = get_terms(array(
                'taxonomy'   => 'mis_academic_year',
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
    public function admin_education_mis_filter_query($query) {
        global $pagenow;
        if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'education_mis') {
            if (!empty($_GET['mis_academic_year'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'mis_academic_year',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['mis_academic_year']),
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
    public function seed_default_education_mis_data() {
        if (!taxonomy_exists('mis_academic_year') || !post_type_exists('education_mis')) {
            return;
        }

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
            'post_type'      => 'education_mis',
            'post_status'    => 'any',
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
                array('name' => 'บริษัท โตโยต้า มอเตอร์ ประเทศไทย จำกัด', 'industry' => 'ยานยนต์และชิ้นส่วน', 'cooperation_type' => 'ทวิภาคี (DVE) & มอบทุนการศึกษา', 'departments' => 'ช่างยนต์, ช่างไฟฟ้า'),
                array('name' => 'บริษัท ฮอนด้า ออโตโมบิล (ประเทศไทย) จำกัด', 'industry' => 'ยานยนต์อัจฉริยะ', 'cooperation_type' => 'ทวิภาคี & รับเข้าทำงานทันที', 'departments' => 'ช่างยนต์, ช่างอิเล็กทรอนิกส์'),
                array('name' => 'บริษัท เดลต้า อีเลคโทรนิคส์ (ประเทศไทย) จำกัด (มหาชน)', 'industry' => 'อิเล็กทรอนิกส์และพลังงานหมุนเวียน', 'cooperation_type' => 'ศูนย์เรียนรู้ & ฝึกอาชีพทวิภาคี', 'departments' => 'ช่างอิเล็กทรอนิกส์, ช่างไฟฟ้ากำลัง'),
                array('name' => 'บริษัท มิตซูบิชิ มอเตอร์ส (ประเทศไทย) จำกัด', 'industry' => 'ยานยนต์และระบบอัตโนมัติ', 'cooperation_type' => 'ทวิภาคีพรีเมียม (EEC Model)', 'departments' => 'เทคนิคเครื่องกล, แมคคาทรอนิกส์'),
                array('name' => 'บมจ. ซีพี ออลล์ (CP ALL PCL)', 'industry' => 'ค้าปลีกและเทคโนโลยีสารสนเทศ', 'cooperation_type' => 'ทวิภาคี & ทุนเรียนฟรีมีรายได้', 'departments' => 'การตลาด, เทคโนโลยีสารสนเทศ'),
                array('name' => 'บริษัท เคอรี่ เอ็กซ์เพรส (ประเทศไทย) จำกัด (มหาชน)', 'industry' => 'การจัดการคลังสินค้าและโลจิสติกส์', 'cooperation_type' => 'ฝึกงานและระบบบริหารจัดส่ง', 'departments' => 'การจัดการโลจิสติกส์'),
                array('name' => 'บริษัท ดับบลิวเอชเอ อินดัสเตรียล ดีเวลลอปเมนท์ จำกัด (มหาชน)', 'industry' => 'นิคมอุตสาหกรรมและสาธารณูปโภค', 'cooperation_type' => 'MOU พัฒนากำลังคนเขต EEC', 'departments' => 'ทุกสาขาวิชาช่างและบริหาร'),
                array('name' => 'บริษัท บี.กริม เพาเวอร์ จำกัด (มหาชน)', 'industry' => 'พลังงานสะอาดและวิศวกรรมไฟฟ้า', 'cooperation_type' => 'ฝึกอาชีพช่างเทคนิคโรงไฟฟ้า', 'departments' => 'ช่างไฟฟ้ากำลัง, ช่างยนต์'),
            );

            $post_id = wp_insert_post(array(
                'post_title'   => 'ข้อมูลสารสนเทศทางการศึกษา ประจำปีการศึกษา 2568',
                'post_name'    => 'education-mis-2568',
                'post_status'  => 'publish',
                'post_type'    => 'education_mis',
                'post_content' => 'สรุปสารสนเทศทางการศึกษา วิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2568 รวบรวมข้อมูลสถิตินักเรียนนักศึกษา บุคลากร หลักสูตร ผลสัมฤทธิ์ทางการเรียน ผลการทดสอบ V-NET การมีงานทำ และความร่วมมือกับสถานประกอบการตามมาตรฐานการอาชีวศึกษา',
                'menu_order'   => 1,
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
                'post_title'   => 'ข้อมูลสารสนเทศทางการศึกษา ประจำปีการศึกษา 2567',
                'post_name'    => 'education-mis-2567',
                'post_status'  => 'publish',
                'post_type'    => 'education_mis',
                'post_content' => 'สารสนเทศทางการศึกษา วิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2567',
                'menu_order'   => 2,
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
}

