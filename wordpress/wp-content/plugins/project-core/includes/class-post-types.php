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
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 22,
            'menu_icon'          => 'dashicons-welcome-learn-more',
            'supports'           => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
            'show_in_rest'       => true,
            'rest_base'          => 'curriculums',
        );

        register_post_type('curriculum', $curriculum_args);
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

        // Seed default terms if empty
        $this->seed_default_curriculum_terms();
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
        $new_columns['curriculum_duration']   = __('ระยะเวลา', 'project-core');
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

            case 'curriculum_duration':
                $duration = get_post_meta($post_id, 'curriculum_duration', true);
                echo !empty($duration) ? esc_html($duration) : '<span style="color: #999;">—</span>';
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
}

