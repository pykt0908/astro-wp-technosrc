<?php
/**
 * Custom Fields and Meta Boxes
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Custom_Fields {

    public function init() {
        add_action('init', array($this, 'register_meta_fields'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_fields'));

        // Event Category Taxonomy Color Fields
        add_action('event_category_add_form_fields', array($this, 'add_event_category_color_field'));
        add_action('event_category_edit_form_fields', array($this, 'edit_event_category_color_field'), 10, 2);
        add_action('created_event_category', array($this, 'save_event_category_color_field'));
        add_action('edited_event_category', array($this, 'save_event_category_color_field'));
        add_filter('manage_edit-event_category_columns', array($this, 'add_event_category_color_column'));
        add_filter('manage_event_category_custom_column', array($this, 'render_event_category_color_column'), 10, 3);

        // Personnel Category Taxonomy Order Fields
        add_action('personnel_category_add_form_fields', array($this, 'add_personnel_category_order_field'));
        add_action('personnel_category_edit_form_fields', array($this, 'edit_personnel_category_order_field'), 10, 2);
        add_action('created_personnel_category', array($this, 'save_personnel_category_order_field'));
        add_action('edited_personnel_category', array($this, 'save_personnel_category_order_field'));
        add_filter('manage_edit-personnel_category_columns', array($this, 'add_personnel_category_order_column'));
        add_filter('manage_personnel_category_custom_column', array($this, 'render_personnel_category_order_column'), 10, 3);
    }

    /**
     * Enqueue WordPress Media Uploader for Banner and Post admin
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        if ($hook === 'post-new.php' || $hook === 'post.php') {
            if (isset($post) && in_array($post->post_type, array('banner', 'post', 'college_event', 'curriculum', 'sar_report', 'education_mis', 'download_file'), true)) {
                wp_enqueue_media();
            }
        }

        // Enqueue WP Color Picker for event_category taxonomy screens
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_event_category = false;
        if ($screen && $screen->taxonomy === 'event_category') {
            $is_event_category = true;
        } elseif (($hook === 'edit-tags.php' || $hook === 'term.php') && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'event_category') {
            $is_event_category = true;
        }

        if ($is_event_category) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }

    /**
     * Register meta fields for REST API access
     */
    public function register_meta_fields() {
        // Curriculum Meta Fields
        $curriculum_fields = array(
            'curriculum_degree_th'       => 'string',
            'curriculum_badge'           => 'string',
            'curriculum_tuition_fee'     => 'string',
            'curriculum_highlights'      => 'string',
            'curriculum_careers'         => 'string',
            'curriculum_study_plan_url'     => 'string',
            'curriculum_document_cover_url' => 'string',
        );

        foreach ($curriculum_fields as $meta_key => $type) {
            register_post_meta('curriculum', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => $type,
                'auth_callback' => array($this, 'auth_check'),
            ));
        }

        // Personnel Meta Fields
        $personnel_text_fields = array(
            'personnel_position'   => 'sanitize_text_field',
            'personnel_nickname'   => 'sanitize_text_field',
            'personnel_department' => 'sanitize_text_field',
            'personnel_education'  => 'sanitize_text_field',
            'personnel_email'       => 'sanitize_email',
            'personnel_phone'       => 'sanitize_text_field',
        );

        foreach ($personnel_text_fields as $field_key => $sanitize_cb) {
            register_post_meta('personnel', $field_key, array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => $sanitize_cb,
                'auth_callback'     => array($this, 'auth_check'),
            ));
        }

        // Personnel Category Order Term Meta
        register_term_meta('personnel_category', 'category_order', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'integer',
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        // SAR Report Meta Fields
        $sar_fields = array(
            'sar_file_url'     => 'string',
            'sar_file_id'      => 'integer',
            'sar_external_url' => 'string',
            'sar_report_no'    => 'string',
        );

        foreach ($sar_fields as $meta_key => $type) {
            register_post_meta('sar_report', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => $type,
                'auth_callback' => array($this, 'auth_check'),
            ));
        }

        // Download File Meta Fields
        $download_fields = array(
            'download_source_type'   => 'string',
            'download_file_url'      => 'string',
            'download_file_id'       => 'integer',
            'download_external_url'  => 'string',
            'download_file_type'     => 'string',
            'download_file_size'     => 'string',
            'download_version'       => 'string',
            'download_description'   => 'string',
            'download_order'         => 'integer',
        );

        foreach ($download_fields as $meta_key => $type) {
            register_post_meta('download_file', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => $type,
                'auth_callback' => array($this, 'auth_check'),
            ));
        }

        // Educational MIS Meta Fields
        $mis_fields = array(
            'mis_academic_year_text'            => 'string',
            'mis_report_pdf_url'                => 'string',
            'mis_report_pdf_id'                 => 'integer',
            'mis_students_total'                => 'integer',
            'mis_students_pvch_total'           => 'integer',
            'mis_students_pvs_total'            => 'integer',
            'mis_students_by_dept'              => 'string',
            'mis_personnel_total'               => 'integer',
            'mis_personnel_executives'          => 'integer',
            'mis_personnel_teachers'            => 'integer',
            'mis_personnel_support'             => 'integer',
            'mis_personnel_breakdown'           => 'string',
            'mis_curriculum_levels_count'       => 'integer',
            'mis_departments_count'             => 'integer',
            'mis_programs_count'                => 'integer',
            'mis_curriculum_list'               => 'string',
            'mis_admissions_graduates'          => 'string',
            'mis_gpax_avg'                      => 'string',
            'mis_gpax_pvch'                     => 'string',
            'mis_gpax_pvs'                      => 'string',
            'mis_vnet_pass_rate'                => 'string',
            'mis_vocational_standard_pass_rate' => 'string',
            'mis_achievements_list'             => 'string',
            'mis_employment_total_rate'         => 'string',
            'mis_employed_field_rate'           => 'string',
            'mis_employed_other_rate'           => 'string',
            'mis_higher_education_rate'         => 'string',
            'mis_freelance_business_rate'       => 'string',
            'mis_unemployed_or_other_rate'      => 'string',
            'mis_employment_data'               => 'string',
            'mis_mou_partners_count'            => 'integer',
            'mis_dual_vocational_count'         => 'integer',
            'mis_intern_students_count'         => 'integer',
            'mis_intern_allowance_avg'          => 'integer',
            'mis_partner_enterprises'           => 'string',
        );

        foreach ($mis_fields as $meta_key => $type) {
            register_post_meta('education_mis', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => $type,
                'auth_callback' => array($this, 'auth_check'),
            ));
        }

        // Banner Meta Fields
        register_post_meta('banner', 'banner_target_url', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('banner', 'banner_desktop_image_url', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('banner', 'banner_desktop_image_id', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('banner', 'banner_mobile_image_url', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('banner', 'banner_is_active', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        // Post SEO Meta Fields
        register_post_meta('post', 'seo_meta_title', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('post', 'seo_meta_description', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('post', 'seo_og_image_url', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        // Post Photo Gallery & Views Meta Fields
        register_post_meta('post', 'post_gallery_ids', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('post', 'post_gallery_count', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'integer',
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        register_post_meta('post', 'post_views_count', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'integer',
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        // College Event Meta Fields
        $event_text_fields = array(
            'academic_year'        => 'sanitize_text_field',
            'event_start_date'     => 'sanitize_text_field',
            'event_end_date'       => 'sanitize_text_field',
            'event_time'           => 'sanitize_text_field',
            'event_location'       => 'sanitize_text_field',
            'event_note'           => 'sanitize_text_field',
            'event_organizer'      => 'sanitize_text_field',
            'event_link'           => 'esc_url_raw',
            'event_attachment_url' => 'esc_url_raw',
            'event_color'          => 'sanitize_text_field',
        );

        foreach ($event_text_fields as $field_key => $sanitize_cb) {
            register_post_meta('college_event', $field_key, array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => $sanitize_cb,
                'auth_callback'     => array($this, 'auth_check'),
            ));
        }

        register_post_meta('college_event', 'is_featured', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback'     => array($this, 'auth_check'),
        ));

        // Event Category Color Term Meta
        register_term_meta('event_category', 'category_color', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'default'           => 'blue',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => array($this, 'auth_check'),
        ));
    }

    /**
     * Register custom fields in WP REST API for posts
     */
    public function register_rest_fields() {
        register_rest_field('post', 'post_gallery', array(
            'get_callback' => function($post_arr) {
                $id = $post_arr['id'];
                $data = get_post_meta($id, 'post_gallery_data', true);
                if (is_array($data) && !empty($data)) {
                    return $data;
                }
                $ids_str = get_post_meta($id, 'post_gallery_ids', true);
                if (!empty($ids_str)) {
                    $ids = array_filter(array_map('absint', explode(',', $ids_str)));
                    $res = array();
                    foreach ($ids as $img_id) {
                        $full = wp_get_attachment_image_url($img_id, 'full');
                        $thumb = wp_get_attachment_image_url($img_id, 'medium');
                        if ($full) {
                            $res[] = array(
                                'id'       => $img_id,
                                'url'      => $full,
                                'thumbUrl' => $thumb ?: $full,
                                'alt'      => get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: '',
                            );
                        }
                    }
                    return $res;
                }
                return array();
            },
            'schema' => array(
                'description' => 'Photo album gallery array',
                'type'        => 'array',
            ),
        ));

        register_rest_field('post', 'post_views', array(
            'get_callback' => function($post_arr) {
                $views = get_post_meta($post_arr['id'], 'post_views_count', true);
                return $views !== '' ? (int) $views : 0;
            },
            'schema' => array(
                'description' => 'View count of post',
                'type'        => 'integer',
            ),
        ));
    }

    public function auth_check() {
        return current_user_can('edit_posts');
    }

    /**
     * Add Admin Meta Boxes
     */
    public function add_meta_boxes() {
        // Banner settings box
        add_meta_box(
            'project_core_banner_settings',
            __('Banner Settings / ตั้งค่าแบนเนอร์', 'project-core'),
            array($this, 'render_banner_meta_box'),
            'banner',
            'normal',
            'high'
        );

        // Post & Curriculum Photo Gallery Box (มีที่ให้ใส่ album ภาพเพิ่มเติม)
        foreach (array('post', 'curriculum') as $pt) {
            add_meta_box(
                'project_core_post_gallery',
                __('อัลบั้มรูปภาพ / Photo Album Gallery', 'project-core'),
                array($this, 'render_post_gallery_meta_box'),
                $pt,
                'normal',
                'high'
            );
        }

        // College Event details box
        add_meta_box(
            'project_core_event_details',
            __('กำหนดการและสถานที่จัดกิจกรรม / Event Details', 'project-core'),
            array($this, 'render_event_meta_box'),
            'college_event',
            'normal',
            'high'
        );

        // Post SEO box
        add_meta_box(
            'project_core_post_seo',
            __('SEO & Social Metadata / ข้อมูล SEO', 'project-core'),
            array($this, 'render_post_seo_meta_box'),
            'post',
            'normal',
            'default'
        );

        // Curriculum details box
        add_meta_box(
            'project_core_curriculum_details',
            __('ข้อมูลและจุดเด่นหลักสูตร / Curriculum Details', 'project-core'),
            array($this, 'render_curriculum_meta_box'),
            'curriculum',
            'normal',
            'high'
        );

        // Personnel details box
        add_meta_box(
            'project_core_personnel_details',
            __('ข้อมูลตำแหน่งและฝ่ายงาน / Personnel Details', 'project-core'),
            array($this, 'render_personnel_meta_box'),
            'personnel',
            'normal',
            'high'
        );

        // SAR Report details box
        add_meta_box(
            'project_core_sar_details',
            __('ข้อมูลรายงานผลการประเมินตนเอง (SAR) และการแนบไฟล์', 'project-core'),
            array($this, 'render_sar_meta_box'),
            'sar_report',
            'normal',
            'high'
        );

        // Educational MIS details box
        add_meta_box(
            'project_core_education_mis_details',
            __('ข้อมูลสารสนเทศทางการศึกษา (7 ด้านสำคัญ)', 'project-core'),
            array($this, 'render_education_mis_meta_box'),
            'education_mis',
            'normal',
            'high'
        );

        // Download File details box
        add_meta_box(
            'project_core_download_details',
            __('ข้อมูลไฟล์และเอกสารดาวน์โหลด (Download Details & Files)', 'project-core'),
            array($this, 'render_download_meta_box'),
            'download_file',
            'normal',
            'high'
        );
    }

    /**
     * Render Banner Meta Box
     */
    public function render_banner_meta_box($post) {
        wp_nonce_field('project_core_banner_nonce', 'project_core_banner_nonce_field');

        $target_url        = get_post_meta($post->ID, 'banner_target_url', true);
        $desktop_image_url = get_post_meta($post->ID, 'banner_desktop_image_url', true);
        $desktop_image_id  = get_post_meta($post->ID, 'banner_desktop_image_id', true);
        if (empty($desktop_image_url) && has_post_thumbnail($post->ID)) {
            $desktop_image_url = get_the_post_thumbnail_url($post->ID, 'full');
            $desktop_image_id  = get_post_thumbnail_id($post->ID);
        }
        $mobile_image_url  = get_post_meta($post->ID, 'banner_mobile_image_url', true);
        $is_active         = get_post_meta($post->ID, 'banner_is_active', true);
        if ($is_active === '') {
            $is_active = '1';
        }
        ?>
        <div class="project-core-banner-wrap" style="padding: 10px 0;">
            <!-- Desktop Banner Upload -->
            <div style="margin-bottom: 24px;">
                <label style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 8px; color: #1d2327;">
                    <?php _e('รูปภาพแบนเนอร์ (ขนาดแนะนำ: 1709 x 650 พิกเซล)', 'project-core'); ?>
                    <span style="color: #d63638;">*</span>
                </label>

                <!-- 1709x650 Live Preview Box -->
                <div id="banner_desktop_preview_container" style="width: 100%; max-width: 680px; aspect-ratio: 1709/650; background: #f0f0f1; border: 2px dashed #8c8f94; border-radius: 6px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
                    <img id="banner_desktop_preview_img" src="<?php echo esc_url($desktop_image_url); ?>" style="width: 100%; height: 100%; object-fit: cover; <?php echo empty($desktop_image_url) ? 'display: none;' : ''; ?>" alt="Banner Preview" />
                    <div id="banner_desktop_placeholder" style="<?php echo !empty($desktop_image_url) ? 'display: none;' : ''; ?> text-align: center; padding: 20px; color: #646970;">
                        <span class="dashicons dashicons-format-image" style="font-size: 42px; width: 42px; height: 42px; display: block; margin: 0 auto 8px; color: #8c8f94;"></span>
                        <strong style="display: block; font-size: 14px; margin-bottom: 4px;"><?php _e('ยังไม่ได้เลือกรูปภาพแบนเนอร์', 'project-core'); ?></strong>
                        <span style="font-size: 12px; color: #8c8f94;"><?php _e('คลิกปุ่ม "อัปโหลดภาพแบนเนอร์ (1709x650)" ด้านล่าง เพื่อเลือกรูปภาพจากเครื่องของคุณหรือจากคลังสื่อ', 'project-core'); ?></span>
                    </div>
                </div>

                <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" id="banner_desktop_image_url" name="banner_desktop_image_url" value="<?php echo esc_attr($desktop_image_url); ?>" />
                    <input type="hidden" id="banner_desktop_image_id" name="banner_desktop_image_id" value="<?php echo esc_attr($desktop_image_id); ?>" />

                    <button type="button" class="button button-primary button-large" id="btn_upload_desktop_banner" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" x2="12" y1="3" y2="15"/>
                        </svg>
                        <?php _e('อัปโหลดภาพแบนเนอร์ (1709x650)', 'project-core'); ?>
                    </button>

                    <button type="button" class="button button-secondary button-large" id="btn_remove_desktop_banner" style="<?php echo empty($desktop_image_url) ? 'display: none;' : 'display: inline-flex;'; ?> align-items: center; gap: 6px; color: #b32d2e;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; color: #b32d2e;">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            <line x1="10" x2="10" y1="11" y2="17"/>
                            <line x1="14" x2="14" y1="11" y2="17"/>
                        </svg>
                        <?php _e('ลบรูปภาพ', 'project-core'); ?>
                    </button>
                </div>
                <p class="description" style="margin-top: 8px;">
                    <?php _e('แนะนำให้ใช้ไฟล์ภาพแนวนอนที่มีอัตราส่วน 1709 x 650 px (หรือใกล้เคียง เช่น 1920x730, 2560x973) เพื่อให้ภาพแสดงผลได้คมชัดและเต็มหน้าจอบนหน้าแรก', 'project-core'); ?>
                </p>
            </div>

            <!-- Target Link URL -->
            <div style="margin-bottom: 24px; padding-top: 16px; border-top: 1px solid #dcdcde;">
                <label for="banner_target_url" style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 8px; color: #1d2327;">
                    <?php _e('ลิงก์ปลายทางเมื่อคลิกภาพแบนเนอร์ (Target Link URL)', 'project-core'); ?>
                </label>
                <input type="text" id="banner_target_url" name="banner_target_url" value="<?php echo esc_attr($target_url); ?>" class="large-text" placeholder="https://example.com หรือ /about หรือ /contact (เว้นว่างได้หากไม่ต้องการให้คลิก)" style="max-width: 680px; padding: 6px 12px;" />
                <p class="description" style="margin-top: 6px;">
                    <?php _e('เมื่อผู้เข้าชมคลิกที่ภาพสไลด์แบนเนอร์ในหน้าแรก ระบบจะเปิดไปยังหน้านี้ทันที (สามารถใส่ URL ภายนอกหรือลิงก์ภายในเว็บไซต์ได้)', 'project-core'); ?>
                </p>
            </div>

            <!-- Mobile Image URL (Optional) -->
            <div style="margin-bottom: 24px; padding-top: 16px; border-top: 1px solid #dcdcde;">
                <label style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 8px; color: #1d2327;">
                    <?php _e('รูปภาพสำหรับมือถือ (Mobile Image - ไม่บังคับ)', 'project-core'); ?>
                </label>

                <div id="banner_mobile_preview_container" style="width: 100%; max-width: 320px; aspect-ratio: 16/9; background: #f0f0f1; border: 2px dashed #8c8f94; border-radius: 6px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
                    <img id="banner_mobile_preview_img" src="<?php echo esc_url($mobile_image_url); ?>" style="width: 100%; height: 100%; object-fit: cover; <?php echo empty($mobile_image_url) ? 'display: none;' : ''; ?>" alt="Mobile Preview" />
                    <div id="banner_mobile_placeholder" style="<?php echo !empty($mobile_image_url) ? 'display: none;' : ''; ?> text-align: center; padding: 12px; color: #646970;">
                        <span class="dashicons dashicons-smartphone" style="font-size: 32px; width: 32px; height: 32px; display: block; margin: 0 auto 4px; color: #8c8f94;"></span>
                        <span style="font-size: 12px;"><?php _e('ใช้ภาพ Desktop ปรับอัตโนมัติ', 'project-core'); ?></span>
                    </div>
                </div>

                <div style="margin-top: 10px; display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" id="banner_mobile_image_url" name="banner_mobile_image_url" value="<?php echo esc_attr($mobile_image_url); ?>" />

                    <button type="button" class="button button-secondary" id="btn_upload_mobile_banner" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" x2="12" y1="3" y2="15"/>
                        </svg>
                        <?php _e('อัปโหลดภาพเฉพาะมือถือ', 'project-core'); ?>
                    </button>

                    <button type="button" class="button button-secondary" id="btn_remove_mobile_banner" style="<?php echo empty($mobile_image_url) ? 'display: none;' : 'display: inline-flex;'; ?> align-items: center; gap: 6px; color: #b32d2e;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; color: #b32d2e;">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            <line x1="10" x2="10" y1="11" y2="17"/>
                            <line x1="14" x2="14" y1="11" y2="17"/>
                        </svg>
                        <?php _e('ลบภาพมือถือ', 'project-core'); ?>
                    </button>
                </div>
                <p class="description" style="margin-top: 6px;">
                    <?php _e('หากไม่ได้อัปโหลดภาพมือถือ ระบบจะย่อขนาดภาพ Desktop (1709x650) ให้พอดีกับหน้าจอมือถือโดยอัตโนมัติ', 'project-core'); ?>
                </p>
            </div>

            <!-- Active Checkbox -->
            <div style="padding-top: 16px; border-top: 1px solid #dcdcde;">
                <label style="font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; color: #1d2327;">
                    <input type="checkbox" id="banner_is_active" name="banner_is_active" value="1" <?php checked($is_active, '1'); ?> style="width: 18px; height: 18px;" />
                    <span><?php _e('เปิดใช้งานแบนเนอร์นี้ในหน้าแรก (Active Status)', 'project-core'); ?></span>
                </label>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Desktop Uploader
            var desktopMediaFrame;
            $('#btn_upload_desktop_banner').on('click', function(e) {
                e.preventDefault();
                if (desktopMediaFrame) {
                    desktopMediaFrame.open();
                    return;
                }
                desktopMediaFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดภาพแบนเนอร์ (ขนาดแนะนำ 1709 x 650)', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้ภาพนี้เป็นแบนเนอร์', 'project-core')); ?>' },
                    library: { type: 'image' },
                    multiple: false
                });

                desktopMediaFrame.on('select', function() {
                    var attachment = desktopMediaFrame.state().get('selection').first().toJSON();
                    var fullUrl = (attachment.sizes && attachment.sizes.full) ? attachment.sizes.full.url : attachment.url;
                    $('#banner_desktop_image_url').val(fullUrl);
                    $('#banner_desktop_image_id').val(attachment.id);
                    $('#banner_desktop_preview_img').attr('src', fullUrl).show();
                    $('#banner_desktop_placeholder').hide();
                    $('#btn_remove_desktop_banner').css('display', 'inline-flex');
                });

                desktopMediaFrame.open();
            });

            $('#btn_remove_desktop_banner').on('click', function(e) {
                e.preventDefault();
                $('#banner_desktop_image_url').val('');
                $('#banner_desktop_image_id').val('');
                $('#banner_desktop_preview_img').attr('src', '').hide();
                $('#banner_desktop_placeholder').show();
                $(this).hide();
            });

            // Mobile Uploader
            var mobileMediaFrame;
            $('#btn_upload_mobile_banner').on('click', function(e) {
                e.preventDefault();
                if (mobileMediaFrame) {
                    mobileMediaFrame.open();
                    return;
                }
                mobileMediaFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดรูปภาพสำหรับมือถือ', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้ภาพนี้สำหรับมือถือ', 'project-core')); ?>' },
                    library: { type: 'image' },
                    multiple: false
                });

                mobileMediaFrame.on('select', function() {
                    var attachment = mobileMediaFrame.state().get('selection').first().toJSON();
                    var fullUrl = (attachment.sizes && attachment.sizes.full) ? attachment.sizes.full.url : attachment.url;
                    $('#banner_mobile_image_url').val(fullUrl);
                    $('#banner_mobile_preview_img').attr('src', fullUrl).show();
                    $('#banner_mobile_placeholder').hide();
                    $('#btn_remove_mobile_banner').css('display', 'inline-flex');
                });

                mobileMediaFrame.open();
            });

            $('#btn_remove_mobile_banner').on('click', function(e) {
                e.preventDefault();
                $('#banner_mobile_image_url').val('');
                $('#banner_mobile_preview_img').attr('src', '').hide();
                $('#banner_mobile_placeholder').show();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Render Post Photo Gallery Meta Box
     */
    public function render_post_gallery_meta_box($post) {
        wp_nonce_field('project_core_gallery_nonce', 'project_core_gallery_nonce_field');
        $gallery_ids_str = get_post_meta($post->ID, 'post_gallery_ids', true) ?: '';
        $gallery_ids = array_filter(array_map('absint', explode(',', $gallery_ids_str)));
        ?>
        <div class="project-core-gallery-wrap" style="padding: 10px 0;">
            <p class="description" style="margin-bottom: 12px; font-size: 13px;">
                <?php _e('อัปโหลดหรือเลือกรูปภาพเพิ่มเติมสำหรับกิจกรรมนี้เพื่อสร้างอัลบั้มภาพ (กดปุ่มด้านล่างเพื่อเลือกได้หลายรูปพร้อมกัน)', 'project-core'); ?>
            </p>

            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap;">
                <input type="hidden" id="post_gallery_ids" name="post_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" />
                <button type="button" class="button button-primary button-large" id="btn_upload_gallery_images" style="display: inline-flex; align-items: center; gap: 7px; padding: 0 14px; height: 38px; line-height: 1;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; display: block;">
                        <path d="M18 22H4a2 2 0 0 1-2-2V6"/>
                        <path d="m22 13-1.296-1.296a2.41 2.41 0 0 0-3.408 0L11 18"/>
                        <circle cx="12" cy="8" r="2"/>
                        <rect width="16" height="16" x="6" y="2" rx="2"/>
                    </svg>
                    <span><?php _e('เพิ่มรูปภาพเข้าอัลบั้ม (เลือกหลายรูปได้)', 'project-core'); ?></span>
                </button>
                <button type="button" class="button button-secondary" id="btn_clear_gallery" style="<?php echo empty($gallery_ids) ? 'display: none;' : 'display: inline-flex;'; ?> align-items: center; gap: 6px; color: #b32d2e; border-color: #b32d2e; padding: 0 12px; height: 38px; line-height: 1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; display: block; color: #b32d2e;">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        <line x1="10" x2="10" y1="11" y2="17"/>
                        <line x1="14" x2="14" y1="11" y2="17"/>
                    </svg>
                    <span><?php _e('ล้างอัลบั้มทั้งหมด', 'project-core'); ?></span>
                </button>
                <span id="gallery_count_label" style="font-size: 13px; color: #50575e; font-weight: 600;">
                    <?php echo sprintf(__('รูปภาพในอัลบั้ม: %d รูป', 'project-core'), count($gallery_ids)); ?>
                </span>
            </div>

            <!-- Thumbnail Grid Container -->
            <div id="post_gallery_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 6px; padding: 12px; min-height: 90px;">
                <?php
                if (!empty($gallery_ids)) {
                    foreach ($gallery_ids as $img_id) {
                        $thumb = wp_get_attachment_image_url($img_id, 'medium');
                        if (!$thumb) continue;
                        ?>
                        <div class="gallery-item-card" data-id="<?php echo esc_attr($img_id); ?>" style="position: relative; border-radius: 6px; overflow: hidden; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #dcdcde;">
                            <img src="<?php echo esc_url($thumb); ?>" style="width: 100%; aspect-ratio: 1; object-fit: cover; display: block;" />
                            <button type="button" class="btn-remove-gallery-item" data-id="<?php echo esc_attr($img_id); ?>" style="position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,0.7); color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; line-height: 1;" title="<?php esc_attr_e('ลบรูปนี้ออกจากอัลบั้ม', 'project-core'); ?>">
                                &times;
                            </button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <p id="gallery_empty_notice" style="<?php echo !empty($gallery_ids) ? 'display: none;' : ''; ?> text-align: center; color: #8c8f94; margin: 15px 0;">
                <?php _e('ยังไม่มีรูปภาพในอัลบั้ม คลิกปุ่ม "เพิ่มรูปภาพเข้าอัลบั้ม" เพื่อเลือกรูป', 'project-core'); ?>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var galleryFrame;
            var $galleryGrid = $('#post_gallery_grid');
            var $hiddenIds = $('#post_gallery_ids');
            var $clearBtn = $('#btn_clear_gallery');
            var $countLabel = $('#gallery_count_label');
            var $emptyNotice = $('#gallery_empty_notice');

            function getIds() {
                var val = $hiddenIds.val();
                return val ? val.split(',').filter(Boolean).map(function(n) { return parseInt(n, 10); }) : [];
            }

            function updateDisplay() {
                var ids = getIds();
                $countLabel.text('รูปภาพในอัลบั้ม: ' + ids.length + ' รูป');
                if (ids.length > 0) {
                    $clearBtn.show();
                    $emptyNotice.hide();
                } else {
                    $clearBtn.hide();
                    $emptyNotice.show();
                }
            }

            $('#btn_upload_gallery_images').on('click', function(e) {
                e.preventDefault();
                if (galleryFrame) {
                    galleryFrame.open();
                    return;
                }

                galleryFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกรูปภาพเข้าอัลบั้มกิจกรรม (กด Ctrl หรือ Shift เพื่อเลือกหลายรูป)', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('เพิ่มรูปภาพที่เลือกเข้าอัลบั้ม', 'project-core')); ?>' },
                    library: { type: 'image' },
                    multiple: true
                });

                galleryFrame.on('select', function() {
                    var selection = galleryFrame.state().get('selection');
                    var currentIds = getIds();

                    selection.each(function(attachment) {
                        var att = attachment.toJSON();
                        if (currentIds.indexOf(att.id) === -1) {
                            currentIds.push(att.id);
                            var imgUrl = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
                            var cardHtml = '<div class="gallery-item-card" data-id="' + att.id + '" style="position: relative; border-radius: 6px; overflow: hidden; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #dcdcde;">' +
                                '<img src="' + imgUrl + '" style="width: 100%; aspect-ratio: 1; object-fit: cover; display: block;" />' +
                                '<button type="button" class="btn-remove-gallery-item" data-id="' + att.id + '" style="position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,0.7); color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; line-height: 1;" title="ลบรูปนี้">&times;</button>' +
                                '</div>';
                            $galleryGrid.append(cardHtml);
                        }
                    });

                    $hiddenIds.val(currentIds.join(','));
                    updateDisplay();
                });

                galleryFrame.open();
            });

            // Remove single item
            $galleryGrid.on('click', '.btn-remove-gallery-item', function(e) {
                e.preventDefault();
                var removeId = parseInt($(this).data('id'), 10);
                var currentIds = getIds();
                var newIds = currentIds.filter(function(id) { return id !== removeId; });
                $hiddenIds.val(newIds.join(','));
                $(this).closest('.gallery-item-card').remove();
                updateDisplay();
            });

            // Clear all
            $clearBtn.on('click', function(e) {
                e.preventDefault();
                if (confirm('<?php echo esc_js(__('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพทั้งหมดออกจากอัลบั้มนี้?', 'project-core')); ?>')) {
                    $hiddenIds.val('');
                    $galleryGrid.empty();
                    updateDisplay();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render Post SEO & View Count Meta Box
     */
    public function render_post_seo_meta_box($post) {
        wp_nonce_field('project_core_seo_nonce', 'project_core_seo_nonce_field');
        $title = get_post_meta($post->ID, 'seo_meta_title', true);
        $desc  = get_post_meta($post->ID, 'seo_meta_description', true);
        $views = get_post_meta($post->ID, 'post_views_count', true);
        if ($views === '') $views = '15';
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="seo_meta_title"><?php _e('SEO Meta Title', 'project-core'); ?></label></th>
                <td><input type="text" id="seo_meta_title" name="seo_meta_title" value="<?php echo esc_attr($title); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="seo_meta_description"><?php _e('SEO Meta Description', 'project-core'); ?></label></th>
                <td><textarea id="seo_meta_description" name="seo_meta_description" rows="3" class="large-text"><?php echo esc_textarea($desc); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="post_views_count"><?php _e('จำนวนการเข้าชม (Views count)', 'project-core'); ?></label></th>
                <td>
                    <input type="number" id="post_views_count" name="post_views_count" value="<?php echo esc_attr($views); ?>" style="width: 120px;" min="0" />
                    <span class="description"><?php _e('ตัวเลขยอดวิวแสดงบนการ์ดข่าวสาร', 'project-core'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render College Event Meta Box
     */
    public function render_event_meta_box($post) {
        wp_nonce_field('project_core_event_nonce', 'project_core_event_nonce_field');

        // Academic Year terms from taxonomy
        $ay_terms = get_terms(array(
            'taxonomy'   => 'academic_year',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'DESC',
        ));

        // Get currently assigned term for post
        $post_ay_terms = get_the_terms($post->ID, 'academic_year');
        $academic_year = ($post_ay_terms && !is_wp_error($post_ay_terms) && !empty($post_ay_terms)) 
            ? $post_ay_terms[0]->name 
            : get_post_meta($post->ID, 'academic_year', true);
        if (empty($academic_year)) {
            $academic_year = '2569';
        }
        $start_date     = get_post_meta($post->ID, 'event_start_date', true);
        $end_date       = get_post_meta($post->ID, 'event_end_date', true);
        $time           = get_post_meta($post->ID, 'event_time', true);
        $location       = get_post_meta($post->ID, 'event_location', true);
        $note           = get_post_meta($post->ID, 'event_note', true);
        $organizer      = get_post_meta($post->ID, 'event_organizer', true);
        $link           = get_post_meta($post->ID, 'event_link', true);
        $attachment_url = get_post_meta($post->ID, 'event_attachment_url', true);
        $color          = get_post_meta($post->ID, 'event_color', true) ?: 'blue';
        $is_featured    = get_post_meta($post->ID, 'is_featured', true);
        ?>
        <table class="form-table" style="max-width: 900px;">
            <tr>
                <th scope="row"><label for="academic_year"><strong><?php _e('ภาคเรียน / ปีการศึกษา (Academic Term) *', 'project-core'); ?></strong></label></th>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <select id="academic_year" name="academic_year" style="min-width: 190px; font-weight: 700; font-size: 14px; padding: 4px 8px; color: #0047BA;">
                            <?php if (!empty($ay_terms) && !is_wp_error($ay_terms)) : ?>
                                <?php foreach ($ay_terms as $t) : ?>
                                    <?php $display_text = strpos($t->name, '/') !== false ? 'ภาคเรียนที่ ' . $t->name : 'ปีการศึกษา ' . $t->name; ?>
                                    <option value="<?php echo esc_attr($t->name); ?>" <?php selected($academic_year, $t->name); ?>><?php echo esc_html($display_text); ?></option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="1/2569" <?php selected($academic_year, '1/2569'); ?>>ภาคเรียนที่ 1/2569</option>
                                <option value="2/2569" <?php selected($academic_year, '2/2569'); ?>>ภาคเรียนที่ 2/2569</option>
                            <?php endif; ?>
                        </select>
                        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=academic_year&post_type=college_event')); ?>" target="_blank" class="button" title="ไปหน้าเพิ่ม ลบ แก้ไข รายการภาคเรียนและปีการศึกษา">
                            <span class="dashicons dashicons-category" style="font-size: 16px; line-height: 28px; vertical-align: middle;"></span> <?php _e('จัดการภาคเรียน / ปีการศึกษา (เพิ่ม / ลบ / แก้ไข)', 'project-core'); ?> ↗
                        </a>
                    </div>
                    <p class="description" style="margin-top: 5px;"><?php _e('เช่น 1/2569, 2/2569 หรือคลิกปุ่ม "จัดการภาคเรียน / ปีการศึกษา" เพื่อเพิ่มภาคเรียนใหม่ เช่น 1/2570 หรือแก้ไข/ลบภาคเรียนเดิม', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_start_date"><strong><?php _e('วันที่เริ่มต้นกิจกรรม *', 'project-core'); ?></strong></label></th>
                <td>
                    <input type="date" id="event_start_date" name="event_start_date" value="<?php echo esc_attr($start_date); ?>" required style="padding: 5px 10px; font-size: 14px;" />
                    <span class="description"><?php _e('วันที่เริ่มจัดกิจกรรม (ค.ศ. เช่น 2026-10-26 ระบบหน้าบ้านจะแปลงเป็น พ.ศ. ให้อัตโนมัติ)', 'project-core'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_end_date"><?php _e('วันที่สิ้นสุดกิจกรรม', 'project-core'); ?></label></th>
                <td>
                    <input type="date" id="event_end_date" name="event_end_date" value="<?php echo esc_attr($end_date); ?>" style="padding: 5px 10px; font-size: 14px;" />
                    <span class="description"><?php _e('เว้นว่างไว้ได้หากเป็นกิจกรรมวันเดียว', 'project-core'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_time"><?php _e('เวลาจัดกิจกรรม', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="event_time" name="event_time" value="<?php echo esc_attr($time); ?>" class="regular-text" placeholder="เช่น 08:30 - 16:30 น. หรือ ตลอดวัน" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_location"><?php _e('สถานที่จัดกิจกรรม', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($location); ?>" class="large-text" placeholder="เช่น หอประชุมใหญ่ อาคารเฉลิมพระเกียรติ หรือ ห้องประชุม 101" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_note"><?php _e('หมายเหตุ', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="event_note" name="event_note" value="<?php echo esc_attr($note); ?>" class="large-text" placeholder="เช่น เฉพาะนักเรียน ปวช.1 หรือข้อความหมายเหตุอื่นๆ (ใส่หรือไม่ใส่ก็ได้)" />
                    <p class="description"><?php _e('ข้อความหมายเหตุเพิ่มเติม จะนำไปแสดงในช่องหมายเหตุเมื่อสั่งพิมพ์ปฏิทิน (จะใส่หรือไม่ใส่ก็ได้)', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_color"><?php _e('ธีมสีของกิจกรรม (Badge Theme)', 'project-core'); ?></label></th>
                <td>
                    <select id="event_color" name="event_color" style="padding: 4px 8px;">
                        <option value="auto" <?php selected($color, 'auto'); ?>>✨ ใช้สีตามหมวดหมู่กิจกรรมอัตโนมัติ (แนะนำ)</option>
                        <option value="blue" <?php selected($color, 'blue'); ?>>สีน้ำเงิน (Royal Blue)</option>
                        <option value="emerald" <?php selected($color, 'emerald'); ?>>สีเขียว (Emerald)</option>
                        <option value="amber" <?php selected($color, 'amber'); ?>>สีส้มเหลือง (Amber)</option>
                        <option value="rose" <?php selected($color, 'rose'); ?>>สีแดง (Rose)</option>
                        <option value="purple" <?php selected($color, 'purple'); ?>>สีม่วง (Purple)</option>
                    </select>
                    <p class="description" style="margin-top: 4px;"><?php _e('ปกติระบบจะใช้สีตามที่ตั้งค่าไว้ใน "หมวดหมู่กิจกรรม" ให้อัตโนมัติ จึงไม่ต้องเลือกสีทุกครั้ง', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_link"><?php _e('ลิงก์รายละเอียดเพิ่มเติม', 'project-core'); ?></label></th>
                <td>
                    <input type="url" id="event_link" name="event_link" value="<?php echo esc_attr($link); ?>" class="large-text" placeholder="https://... (เว้นว่างได้)" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="event_attachment_url"><?php _e('เอกสารประกาศ / ปฏิทิน PDF', 'project-core'); ?></label></th>
                <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" id="event_attachment_url" name="event_attachment_url" value="<?php echo esc_attr($attachment_url); ?>" class="large-text" placeholder="https://... ลิงก์ไฟล์ประกาศหรือไฟล์แนบ PDF" />
                        <button type="button" class="button" id="btn_upload_event_pdf">เลือกไฟล์สื่อ</button>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('กิจกรรมไฮไลท์', 'project-core'); ?></th>
                <td>
                    <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php checked($is_featured, '1'); ?> />
                        <strong><?php _e('ตั้งเป็นกิจกรรมสำคัญ / ไฮไลท์ (ติดป้ายดาว ★ โดดเด่น)', 'project-core'); ?></strong>
                    </label>
                </td>
            </tr>
        </table>
        <script>
        jQuery(document).ready(function($) {
            var eventPdfFrame;
            $('#btn_upload_event_pdf').on('click', function(e) {
                e.preventDefault();
                if (eventPdfFrame) {
                    eventPdfFrame.open();
                    return;
                }
                eventPdfFrame = wp.media({
                    title: 'เลือกหรืออัปโหลดเอกสารแนบ',
                    button: { text: 'ใช้ไฟล์นี้' },
                    multiple: false
                });
                eventPdfFrame.on('select', function() {
                    var attachment = eventPdfFrame.state().get('selection').first().toJSON();
                    $('#event_attachment_url').val(attachment.url);
                });
                eventPdfFrame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Save Meta Boxes with Nonce and Permission validation
     */
    public function save_meta_boxes($post_id, $post) {
        // Prevent autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save Banner Meta
        if (isset($_POST['project_core_banner_nonce_field']) && wp_verify_nonce($_POST['project_core_banner_nonce_field'], 'project_core_banner_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $target_url        = isset($_POST['banner_target_url']) ? esc_url_raw($_POST['banner_target_url']) : '';
                $desktop_image_url = isset($_POST['banner_desktop_image_url']) ? esc_url_raw($_POST['banner_desktop_image_url']) : '';
                $desktop_image_id  = isset($_POST['banner_desktop_image_id']) ? absint($_POST['banner_desktop_image_id']) : 0;
                $mobile_image_url  = isset($_POST['banner_mobile_image_url']) ? esc_url_raw($_POST['banner_mobile_image_url']) : '';
                $is_active         = isset($_POST['banner_is_active']) ? '1' : '0';

                update_post_meta($post_id, 'banner_target_url', $target_url);
                update_post_meta($post_id, 'banner_desktop_image_url', $desktop_image_url);
                update_post_meta($post_id, 'banner_desktop_image_id', $desktop_image_id);
                update_post_meta($post_id, 'banner_mobile_image_url', $mobile_image_url);
                update_post_meta($post_id, 'banner_is_active', $is_active);

                // Synchronize WordPress featured image thumbnail
                if ($desktop_image_id > 0) {
                    set_post_thumbnail($post_id, $desktop_image_id);
                } elseif (empty($desktop_image_url)) {
                    delete_post_thumbnail($post_id);
                }
            }
        }

        // Save College Event Meta
        if (isset($_POST['project_core_event_nonce_field']) && wp_verify_nonce($_POST['project_core_event_nonce_field'], 'project_core_event_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $academic_year  = isset($_POST['academic_year']) ? sanitize_text_field($_POST['academic_year']) : '';
                $start_date     = isset($_POST['event_start_date']) ? sanitize_text_field($_POST['event_start_date']) : '';
                $end_date       = isset($_POST['event_end_date']) ? sanitize_text_field($_POST['event_end_date']) : '';
                $time           = isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : '';
                $location       = isset($_POST['event_location']) ? sanitize_text_field($_POST['event_location']) : '';
                $note           = isset($_POST['event_note']) ? sanitize_text_field($_POST['event_note']) : '';
                $organizer      = isset($_POST['event_organizer']) ? sanitize_text_field($_POST['event_organizer']) : '';
                $link           = isset($_POST['event_link']) ? esc_url_raw($_POST['event_link']) : '';
                $attachment_url = isset($_POST['event_attachment_url']) ? esc_url_raw($_POST['event_attachment_url']) : '';
                $color          = isset($_POST['event_color']) ? sanitize_text_field($_POST['event_color']) : 'blue';
                $is_featured    = isset($_POST['is_featured']) ? '1' : '0';

                if (empty($academic_year) && !empty($start_date)) {
                    $parts = explode('-', $start_date);
                    $y = (int) $parts[0];
                    $m = isset($parts[1]) ? (int) $parts[1] : 6;
                    $ay_calc = ($m < 5) ? ($y - 1) : $y;
                    $th_year = $ay_calc + 543;
                    $term_num = ($m >= 5 && $m <= 10) ? '1' : '2';
                    $academic_year = "{$term_num}/{$th_year}";
                } elseif (empty($academic_year)) {
                    $academic_year = '1/2569';
                }

                // Ensure term exists in academic_year taxonomy and set it
                if (!term_exists($academic_year, 'academic_year')) {
                    $slug = sanitize_title(str_replace('/', '-', $academic_year));
                    wp_insert_term($academic_year, 'academic_year', array('slug' => $slug));
                }
                wp_set_object_terms($post_id, $academic_year, 'academic_year', false);

                update_post_meta($post_id, 'academic_year', $academic_year);
                update_post_meta($post_id, 'event_start_date', $start_date);
                update_post_meta($post_id, 'event_end_date', $end_date);
                update_post_meta($post_id, 'event_time', $time);
                update_post_meta($post_id, 'event_location', $location);
                update_post_meta($post_id, 'event_note', $note);
                update_post_meta($post_id, 'event_organizer', $organizer);
                update_post_meta($post_id, 'event_link', $link);
                update_post_meta($post_id, 'event_attachment_url', $attachment_url);
                update_post_meta($post_id, 'event_color', $color);
                update_post_meta($post_id, 'is_featured', $is_featured);

                // If editor content is empty or only whitespace/empty tags, clear post_excerpt too
                $current_content = get_post_field('post_content', $post_id);
                $clean_cnt = trim(wp_strip_all_tags($current_content));
                $clean_cnt = str_replace(array('&nbsp;', "\xc2\xa0"), '', $clean_cnt);
                if (empty($clean_cnt)) {
                    global $wpdb;
                    $wpdb->update($wpdb->posts, array('post_excerpt' => ''), array('ID' => $post_id));
                }
            }
        }

        // Save Post Photo Gallery Meta
        if (isset($_POST['project_core_gallery_nonce_field']) && wp_verify_nonce($_POST['project_core_gallery_nonce_field'], 'project_core_gallery_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $raw_ids = isset($_POST['post_gallery_ids']) ? sanitize_text_field($_POST['post_gallery_ids']) : '';
                $clean_ids = array_filter(array_map('absint', explode(',', $raw_ids)));

                update_post_meta($post_id, 'post_gallery_ids', implode(',', $clean_ids));
                update_post_meta($post_id, 'post_gallery_count', count($clean_ids));

                $gallery_data = array();
                foreach ($clean_ids as $img_id) {
                    $full  = wp_get_attachment_image_url($img_id, 'full');
                    $thumb = wp_get_attachment_image_url($img_id, 'medium');
                    if ($full) {
                        $gallery_data[] = array(
                            'id'       => $img_id,
                            'url'      => $full,
                            'thumbUrl' => $thumb ?: $full,
                            'alt'      => get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: '',
                        );
                    }
                }
                update_post_meta($post_id, 'post_gallery_data', $gallery_data);
            }
        }

        // Save Post SEO & Views Meta
        if (isset($_POST['project_core_seo_nonce_field']) && wp_verify_nonce($_POST['project_core_seo_nonce_field'], 'project_core_seo_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $meta_title = isset($_POST['seo_meta_title']) ? sanitize_text_field($_POST['seo_meta_title']) : '';
                $meta_desc  = isset($_POST['seo_meta_description']) ? sanitize_textarea_field($_POST['seo_meta_description']) : '';
                $og_image   = isset($_POST['seo_og_image_url']) ? esc_url_raw($_POST['seo_og_image_url']) : '';
                $views      = isset($_POST['post_views_count']) ? absint($_POST['post_views_count']) : 0;

                update_post_meta($post_id, 'seo_meta_title', $meta_title);
                update_post_meta($post_id, 'seo_meta_description', $meta_desc);
                update_post_meta($post_id, 'seo_og_image_url', $og_image);
                update_post_meta($post_id, 'post_views_count', $views);
            }
        }

        // Save Curriculum Meta
        if (isset($_POST['project_core_curriculum_nonce_field']) && wp_verify_nonce($_POST['project_core_curriculum_nonce_field'], 'project_core_curriculum_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                // Update slug if custom slug provided
                if (isset($_POST['curriculum_custom_slug'])) {
                    $new_slug = trim((string) $_POST['curriculum_custom_slug']);
                    if (!empty($new_slug)) {
                        $target_slug = sanitize_title(urldecode($new_slug));
                        if ($target_slug !== $post->post_name) {
                            remove_action('save_post', array($this, 'save_meta_boxes'), 10);
                            wp_update_post(array(
                                'ID'        => $post_id,
                                'post_name' => $target_slug,
                            ));
                            add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
                        }
                    }
                }

                $keys = array(
                    'curriculum_degree_th'       => 'sanitize_text_field',
                    'curriculum_badge'           => 'sanitize_text_field',
                    'curriculum_tuition_fee'     => 'sanitize_text_field',
                    'curriculum_highlights'      => 'sanitize_textarea_field',
                    'curriculum_careers'         => 'sanitize_textarea_field',
                    'curriculum_study_plan_url'     => 'esc_url_raw',
                    'curriculum_document_cover_url' => 'esc_url_raw',
                );

                foreach ($keys as $key => $sanitizer) {
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, call_user_func($sanitizer, $_POST[$key]));
                    }
                }
            }
        }

        // Save Personnel Meta
        if (isset($_POST['project_core_personnel_nonce_field']) && wp_verify_nonce($_POST['project_core_personnel_nonce_field'], 'project_core_personnel_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $personnel_keys = array(
                    'personnel_position'   => 'sanitize_text_field',
                    'personnel_nickname'   => 'sanitize_text_field',
                    'personnel_department' => 'sanitize_text_field',
                    'personnel_education'  => 'sanitize_text_field',
                    'personnel_email'       => 'sanitize_email',
                    'personnel_phone'       => 'sanitize_text_field',
                    'personnel_dept_order'  => 'absint',
                );

                foreach ($personnel_keys as $key => $sanitizer) {
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, call_user_func($sanitizer, $_POST[$key]));
                    }
                }

                // If personnel_department matches a personnel_category term, auto-assign the taxonomy term
                if (!empty($_POST['personnel_department'])) {
                    $dept_val = sanitize_text_field($_POST['personnel_department']);
                    $matched_term = get_term_by('name', $dept_val, 'personnel_category');
                    if ($matched_term && !is_wp_error($matched_term)) {
                        wp_set_object_terms($post_id, (int)$matched_term->term_id, 'personnel_category');
                    }
                }
            }
        }

        // Save SAR Report Meta
        if (isset($_POST['project_core_sar_nonce_field']) && wp_verify_nonce($_POST['project_core_sar_nonce_field'], 'project_core_sar_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $sar_keys = array(
                    'sar_file_url'     => 'esc_url_raw',
                    'sar_file_id'      => 'absint',
                    'sar_external_url' => 'esc_url_raw',
                    'sar_report_no'    => 'sanitize_text_field',
                );

                foreach ($sar_keys as $key => $sanitizer) {
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, call_user_func($sanitizer, $_POST[$key]));
                    }
                }
            }
        }

        // Save Educational MIS Meta
        if (isset($_POST['project_core_mis_nonce_field']) && wp_verify_nonce($_POST['project_core_mis_nonce_field'], 'project_core_mis_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $mis_scalar_keys = array(
                    'mis_academic_year_text'            => 'sanitize_text_field',
                    'mis_report_pdf_url'                => 'esc_url_raw',
                    'mis_report_pdf_id'                 => 'absint',
                    'mis_students_total'                => 'absint',
                    'mis_students_pvch_total'           => 'absint',
                    'mis_students_pvs_total'            => 'absint',
                    'mis_personnel_total'               => 'absint',
                    'mis_personnel_executives'          => 'absint',
                    'mis_personnel_teachers'            => 'absint',
                    'mis_personnel_support'             => 'absint',
                    'mis_curriculum_levels_count'       => 'absint',
                    'mis_departments_count'             => 'absint',
                    'mis_programs_count'                => 'absint',
                    'mis_gpax_avg'                      => 'sanitize_text_field',
                    'mis_gpax_pvch'                     => 'sanitize_text_field',
                    'mis_gpax_pvs'                      => 'sanitize_text_field',
                    'mis_vnet_pass_rate'                => 'sanitize_text_field',
                    'mis_vocational_standard_pass_rate' => 'sanitize_text_field',
                    'mis_employment_total_rate'         => 'sanitize_text_field',
                    'mis_employed_field_rate'           => 'sanitize_text_field',
                    'mis_employed_other_rate'           => 'sanitize_text_field',
                    'mis_higher_education_rate'         => 'sanitize_text_field',
                    'mis_freelance_business_rate'       => 'sanitize_text_field',
                    'mis_unemployed_or_other_rate'      => 'sanitize_text_field',
                    'mis_mou_partners_count'            => 'absint',
                    'mis_dual_vocational_count'         => 'absint',
                    'mis_intern_students_count'         => 'absint',
                    'mis_intern_allowance_avg'          => 'absint',
                );

                foreach ($mis_scalar_keys as $key => $sanitizer) {
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, call_user_func($sanitizer, $_POST[$key]));
                    }
                }

                // JSON repeater fields
                $json_fields = array(
                    'mis_students_by_dept',
                    'mis_personnel_breakdown',
                    'mis_curriculum_list',
                    'mis_admissions_graduates',
                    'mis_achievements_list',
                    'mis_employment_data',
                    'mis_partner_enterprises',
                );

                foreach ($json_fields as $jkey) {
                    if (isset($_POST[$jkey])) {
                        $raw = wp_unslash($_POST[$jkey]);
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) {
                            if ($jkey === 'mis_employment_data') {
                                $sum_count = 0;
                                foreach ($decoded as $item) {
                                    $sum_count += isset($item['count']) ? absint($item['count']) : 0;
                                }
                                $clean_emp = array();
                                $field_pct = null;
                                $other_pct = null;
                                $higher_pct = null;
                                $free_pct = null;
                                $unemp_pct = null;
                                $success_cnt = 0;

                                foreach ($decoded as $item) {
                                    $cnt = isset($item['count']) ? absint($item['count']) : 0;
                                    $pct_num = ($sum_count > 0) ? ($cnt / $sum_count) * 100 : 0;
                                    $pct_str = ($sum_count > 0) ? number_format($pct_num, 1) . '%' : '';
                                    $cat = isset($item['category']) ? sanitize_text_field($item['category']) : '';

                                    if (mb_strpos($cat, 'ตรงสาขา') !== false) {
                                        $field_pct = number_format($pct_num, 1);
                                        $success_cnt += $cnt;
                                    } elseif (mb_strpos($cat, 'ต่างสาขา') !== false || mb_strpos($cat, 'สาขาอื่น') !== false) {
                                        $other_pct = number_format($pct_num, 1);
                                        $success_cnt += $cnt;
                                    } elseif (mb_strpos($cat, 'ศึกษาต่อ') !== false) {
                                        $higher_pct = number_format($pct_num, 1);
                                        $success_cnt += $cnt;
                                    } elseif (mb_strpos($cat, 'อิสระ') !== false || mb_strpos($cat, 'ผู้ประกอบการ') !== false) {
                                        $free_pct = number_format($pct_num, 1);
                                        $success_cnt += $cnt;
                                    } elseif (mb_strpos($cat, 'รอ') !== false || mb_strpos($cat, 'เกณฑ์ทหาร') !== false || mb_strpos($cat, 'อื่น') !== false) {
                                        $unemp_pct = number_format($pct_num, 1);
                                    }

                                    $clean_emp[] = array(
                                        'category'   => $cat,
                                        'count'      => $cnt,
                                        'percent'    => $pct_str,
                                        'avg_salary' => isset($item['avg_salary']) ? sanitize_text_field($item['avg_salary']) : '',
                                    );
                                }
                                $decoded = $clean_emp;

                                if ($sum_count > 0) {
                                    $total_success = number_format(($success_cnt / $sum_count) * 100, 1);
                                    update_post_meta($post_id, 'mis_employment_total_rate', $total_success);
                                    if ($field_pct !== null) update_post_meta($post_id, 'mis_employed_field_rate', $field_pct);
                                    if ($other_pct !== null) update_post_meta($post_id, 'mis_employed_other_rate', $other_pct);
                                    if ($higher_pct !== null) update_post_meta($post_id, 'mis_higher_education_rate', $higher_pct);
                                    if ($free_pct !== null) update_post_meta($post_id, 'mis_freelance_business_rate', $free_pct);
                                    if ($unemp_pct !== null) update_post_meta($post_id, 'mis_unemployed_or_other_rate', $unemp_pct);
                                }
                            }
                            if ($jkey === 'mis_partner_enterprises') {
                                $clean_partners = array();
                                foreach ($decoded as $item) {
                                    $clean_partners[] = array(
                                        'logo_url'         => isset($item['logo_url']) ? esc_url_raw($item['logo_url']) : '',
                                        'name'             => isset($item['name']) ? sanitize_text_field($item['name']) : '',
                                        'industry'         => isset($item['industry']) ? sanitize_text_field($item['industry']) : '',
                                        'cooperation_type' => isset($item['cooperation_type']) ? sanitize_text_field($item['cooperation_type']) : '',
                                        'departments'      => isset($item['departments']) ? sanitize_text_field($item['departments']) : '',
                                    );
                                }
                                $decoded = $clean_partners;
                            }
                            update_post_meta($post_id, $jkey, wp_json_encode($decoded, JSON_UNESCAPED_UNICODE));
                        }
                    }
                }
            }
        }

        // Save Download File Meta
        if (isset($_POST['project_core_download_nonce_field']) && wp_verify_nonce($_POST['project_core_download_nonce_field'], 'project_core_download_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $download_keys = array(
                    'download_source_type'  => 'sanitize_text_field',
                    'download_file_url'     => 'esc_url_raw',
                    'download_file_id'      => 'absint',
                    'download_external_url' => 'esc_url_raw',
                    'download_file_type'    => 'sanitize_text_field',
                    'download_file_size'    => 'sanitize_text_field',
                    'download_version'      => 'sanitize_text_field',
                    'download_description'  => 'sanitize_textarea_field',
                    'download_order'        => 'absint',
                );

                foreach ($download_keys as $key => $sanitizer) {
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, call_user_func($sanitizer, $_POST[$key]));
                    }
                }

                // Sync menu_order with download_order
                if (isset($_POST['download_order'])) {
                    $order = absint($_POST['download_order']);
                    wp_update_post(array(
                        'ID'         => $post_id,
                        'menu_order' => $order,
                    ));
                }
            }
        }
    }

    /**
     * Render Curriculum Meta Box
     */
    public function render_curriculum_meta_box($post) {
        wp_nonce_field('project_core_curriculum_nonce', 'project_core_curriculum_nonce_field');

        $current_slug    = urldecode($post->post_name);
        $degree_th       = get_post_meta($post->ID, 'curriculum_degree_th', true) ?: '';
        $badge           = get_post_meta($post->ID, 'curriculum_badge', true) ?: '';
        $tuition_fee     = get_post_meta($post->ID, 'curriculum_tuition_fee', true) ?: '';
        $highlights      = get_post_meta($post->ID, 'curriculum_highlights', true) ?: '';
        $careers         = get_post_meta($post->ID, 'curriculum_careers', true) ?: '';
        $study_plan_url      = get_post_meta($post->ID, 'curriculum_study_plan_url', true) ?: '';
        $document_cover_url  = get_post_meta($post->ID, 'curriculum_document_cover_url', true) ?: '';
        ?>
        <table class="form-table" style="margin-top: 0;">
            <tr>
                <th scope="row"><label for="curriculum_custom_slug"><?php _e('สลัก URL (URL Slug / ลิงก์สาขา)', 'project-core'); ?></label></th>
                <td>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <span style="color: #64748b; font-family: monospace; font-size: 13px;">/curriculum/</span>
                        <input type="text" id="curriculum_custom_slug" name="curriculum_custom_slug" value="<?php echo esc_attr($current_slug); ?>" style="width: 320px; font-family: monospace;" placeholder="เช่น marketing-vocational" />
                        <span style="color: #64748b; font-family: monospace; font-size: 13px;">/</span>
                    </div>
                    <p class="description"><?php _e('กำหนดชื่อ URL Slug ภาษาอังกฤษหรือภาษาไทยได้ตามต้องการ (เช่น <code>marketing-vocational</code> หรือ <code>การตลาด-ปวช</code>) หากเว้นว่างไว้ WordPress จะตั้งให้อัตโนมัติ', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_degree_th"><?php _e('วุฒิการศึกษาที่ได้รับ (ภาษาไทย)', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_degree_th" name="curriculum_degree_th" value="<?php echo esc_attr($degree_th); ?>" class="large-text" placeholder="เช่น ประกาศนียบัตรวิชาชีพ (ปวช.) สาขาวิชาช่างยนต์" />
                    <p class="description"><?php _e('ระบุชื่อวุฒิการศึกษาเต็ม เช่น ประกาศนียบัตรวิชาชีพ (ปวช.) / ประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.)', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_badge"><?php _e('ป้ายกำกับพิเศษ (Badge)', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_badge" name="curriculum_badge" value="<?php echo esc_attr($badge); ?>" style="width: 250px;" placeholder="เช่น ยอดนิยม, EEC, ทวิภาคี 100%" />
                    <p class="description"><?php _e('ข้อความป้ายกำกับขนาดเล็กแสดงบนการ์ดหลักสูตร (เว้นว่างได้)', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_tuition_fee"><?php _e('ค่าธรรมเนียม / ค่าเทอมเริ่มต้น', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_tuition_fee" name="curriculum_tuition_fee" value="<?php echo esc_attr($tuition_fee); ?>" style="width: 250px;" placeholder="เช่น ตามระเบียบวิทยาลัย / ผ่อนชำระได้" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_highlights"><?php _e('จุดเด่นและทักษะที่จะได้รับ', 'project-core'); ?></label></th>
                <td>
                    <textarea id="curriculum_highlights" name="curriculum_highlights" rows="4" class="large-text" placeholder="ระบุจุดเด่นหรือทักษะ (บรรทัดละ 1 ข้อ)&#10;เช่น เรียนรู้ระบบยานยนต์สมัยใหม่และยานยนต์ไฟฟ้า&#10;ฝึกงานจริงในศูนย์บริการมาตรฐาน&#10;ได้รับใบประกาศนียบัตรวิชาชีพ"><?php echo esc_textarea($highlights); ?></textarea>
                    <p class="description"><?php _e('พิมพ์จุดเด่นแยกแต่ละบรรทัด ระบบจะนำไปสร้างเป็นรายการ bullet points สวยงาม', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_careers"><?php _e('แนวทางการประกอบอาชีพ', 'project-core'); ?></label></th>
                <td>
                    <textarea id="curriculum_careers" name="curriculum_careers" rows="4" class="large-text" placeholder="ระบุสายงานและอาชีพหลังสำเร็จการศึกษา (บรรทัดละ 1 ข้อ)&#10;เช่น ช่างเทคนิคยานยนต์ประจำศูนย์บริการ&#10;เจ้าของธุรกิจอู่ซ่อมบำรุงยานยนต์&#10;ผู้ตรวจสอบคุณภาพในโรงงานประกอบรถยนต์"><?php echo esc_textarea($careers); ?></textarea>
                    <p class="description"><?php _e('พิมพ์อาชีพแยกแต่ละบรรทัด', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_study_plan_url"><?php _e('ไฟล์เอกสารหลักสูตรฉบับสมบูรณ์ (PDF)', 'project-core'); ?></label></th>
                <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" id="curriculum_study_plan_url" name="curriculum_study_plan_url" value="<?php echo esc_attr($study_plan_url); ?>" class="large-text" placeholder="https://... ลิงก์ดาวน์โหลดเอกสารหลักสูตรฉบับสมบูรณ์ PDF" />
                        <button type="button" class="button" id="btn_upload_curriculum_pdf"><?php _e('เลือกไฟล์ PDF', 'project-core'); ?></button>
                    </div>
                    <p class="description"><?php _e('อัปโหลดไฟล์ PDF ฉบับเต็ม ระบบหน้าเว็บจะนำหน้าแรกของ PDF มาแสดงเป็นภาพตัวอย่างให้อัตโนมัติ', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_document_cover_url"><?php _e('ภาพหน้าแรก / หน้าปกเอกสาร (ทางเลือก)', 'project-core'); ?></label></th>
                <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" id="curriculum_document_cover_url" name="curriculum_document_cover_url" value="<?php echo esc_attr($document_cover_url); ?>" class="large-text" placeholder="https://... ลิงก์รูปภาพหน้าแรกของเอกสารหลักสูตร" />
                        <button type="button" class="button" id="btn_upload_curriculum_cover"><?php _e('เลือกรูปภาพ', 'project-core'); ?></button>
                    </div>
                    <p class="description"><?php _e('หากต้องการกำหนดรูปภาพหน้าแรกของเอกสารเฉพาะ สามารถระบุได้ที่นี่ (หากเว้นว่างไว้ ระบบจะเรนเดอร์หน้าแรกจากไฟล์ PDF หรือสร้างหน้าแรกมาตรฐานให้อัตโนมัติ)', 'project-core'); ?></p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            var currPdfFrame;
            $('#btn_upload_curriculum_pdf').on('click', function(e) {
                e.preventDefault();
                if (currPdfFrame) {
                    currPdfFrame.open();
                    return;
                }
                currPdfFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดเอกสารแผนการเรียน PDF', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้ไฟล์นี้', 'project-core')); ?>' },
                    multiple: false
                });
                currPdfFrame.on('select', function() {
                    var attachment = currPdfFrame.state().get('selection').first().toJSON();
                    $('#curriculum_study_plan_url').val(attachment.url);
                });
                currPdfFrame.open();
            });

            var currCoverFrame;
            $('#btn_upload_curriculum_cover').on('click', function(e) {
                e.preventDefault();
                if (currCoverFrame) {
                    currCoverFrame.open();
                    return;
                }
                currCoverFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกรูปภาพหน้าแรก / หน้าปกเอกสารหลักสูตร', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้รูปนี้', 'project-core')); ?>' },
                    library: { type: 'image' },
                    multiple: false
                });
                currCoverFrame.on('select', function() {
                    var attachment = currCoverFrame.state().get('selection').first().toJSON();
                    $('#curriculum_document_cover_url').val(attachment.url);
                });
                currCoverFrame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Render Personnel Meta Box
     */
    public function render_personnel_meta_box($post) {
        wp_nonce_field('project_core_personnel_nonce', 'project_core_personnel_nonce_field');

        $position   = get_post_meta($post->ID, 'personnel_position', true);
        $nickname   = get_post_meta($post->ID, 'personnel_nickname', true);
        $department = get_post_meta($post->ID, 'personnel_department', true);
        $education  = get_post_meta($post->ID, 'personnel_education', true);
        $email      = get_post_meta($post->ID, 'personnel_email', true);
        $phone      = get_post_meta($post->ID, 'personnel_phone', true);

        // Fetch categories from personnel_category taxonomy
        $personnel_cats = get_terms(array(
            'taxonomy'   => 'personnel_category',
            'hide_empty' => false,
            'orderby'    => 'meta_value_num name',
            'meta_key'   => 'category_order',
            'order'      => 'ASC',
        ));

        // Fetch divisions from org_unit
        $divisions = get_posts(array(
            'post_type'      => 'org_unit',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_org_unit_type',
                    'value' => 'division',
                ),
            ),
            'meta_key'       => '_org_unit_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        // Determine if current department is custom
        $known_options = array();
        if (!empty($personnel_cats) && !is_wp_error($personnel_cats)) {
            foreach ($personnel_cats as $cat) {
                $known_options[] = $cat->name;
            }
        }
        if (!empty($divisions)) {
            foreach ($divisions as $div) {
                $known_options[] = $div->post_title;
            }
        }
        $is_custom = (!empty($department) && !in_array($department, $known_options, true));
        ?>
        <table class="form-table" style="margin-top: 0;">
            <tr>
                <th scope="row"><label for="personnel_position"><?php _e('ตำแหน่ง / หน้าที่รับผิดชอบ', 'project-core'); ?> <span style="color:#d63638;">*</span></label></th>
                <td>
                    <input type="text" id="personnel_position" name="personnel_position" value="<?php echo esc_attr($position); ?>" class="large-text" style="max-width: 450px;" placeholder="เช่น ผู้รับใบอนุญาตฯ, ที่ปรึกษาวิทยาลัยฯ, ผู้อำนวยการ" />
                    <p class="description"><?php _e('ระบุชื่อตำแหน่งหลักของบุคลากร (จะแสดงในการ์ดแบบป้ายสีเข้ม)', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="personnel_nickname"><?php _e('ชื่อเรียก / ฉายา (เช่น อาจารย์หนึ่ง)', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="personnel_nickname" name="personnel_nickname" value="<?php echo esc_attr($nickname); ?>" class="regular-text" placeholder="เช่น อาจารย์หนึ่ง หรือ อาจารย์แม่ (เว้นว่างได้)" />
                    <p class="description"><?php _e('จะแสดงเป็นวงเล็บสีเทาใต้ชื่อ เช่น (อาจารย์หนึ่ง)', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="personnel_department_select"><?php _e('ฝ่ายงาน / แผนกวิชา', 'project-core'); ?></label></th>
                <td>
                    <select id="personnel_department_select" class="regular-text" style="max-width: 450px; width: 100%;">
                        <option value="">-- <?php _e('เลือกกลุ่ม / ฝ่ายงาน / แผนกวิชา', 'project-core'); ?> --</option>
                        <?php if (!empty($personnel_cats) && !is_wp_error($personnel_cats)): ?>
                            <optgroup label="<?php esc_attr_e('กลุ่มบุคลากร / สาขาวิชา', 'project-core'); ?>">
                                <?php foreach ($personnel_cats as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->name); ?>" <?php selected($department, $cat->name); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($divisions)): ?>
                            <optgroup label="<?php esc_attr_e('ฝ่ายงาน (โครงสร้างการบริหาร)', 'project-core'); ?>">
                                <?php foreach ($divisions as $div): ?>
                                    <option value="<?php echo esc_attr($div->post_title); ?>" <?php selected($department, $div->post_title); ?>>
                                        <?php echo esc_html($div->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <option value="__custom__" <?php selected($is_custom, true); ?>>
                            <?php _e('✏️ กำหนดเอง / ระบุข้อความอื่น...', 'project-core'); ?>
                        </option>
                    </select>

                    <div id="personnel_department_custom_wrap" style="<?php echo ($is_custom ? 'margin-top:8px;' : 'display:none; margin-top:8px;'); ?>">
                        <input type="text" id="personnel_department" name="personnel_department" value="<?php echo esc_attr($department); ?>" class="large-text" style="max-width: 450px;" placeholder="<?php esc_attr_e('เช่น ที่ปรึกษาสถานศึกษา, ผู้บริหารสถานศึกษา, สาขาวิชาการบัญชี', 'project-core'); ?>" />
                    </div>

                    <p class="description">
                        <?php _e('เลือกจากกลุ่มที่สร้างไว้แล้วในระบบ หรือเลือก "กำหนดเอง" เพื่อพิมพ์ข้อความใหม่', 'project-core'); ?>
                        | <a href="<?php echo admin_url('edit-tags.php?taxonomy=personnel_category&post_type=personnel'); ?>" target="_blank"><?php _e('+ จัดการกลุ่มบุคลากร', 'project-core'); ?></a>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="personnel_education"><?php _e('วุฒิการศึกษา', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="personnel_education" name="personnel_education" value="<?php echo esc_attr($education); ?>" class="large-text" style="max-width: 450px;" placeholder="เช่น ปริญญาโท บริหารการศึกษา, ค.อ.บ. วิศวกรรมเครื่องกล" />
                    <p class="description"><?php _e('วุฒิการศึกษาสูงสุด หรือสาขาวิชาที่สำเร็จการศึกษา', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="personnel_email"><?php _e('อีเมลติดต่อ', 'project-core'); ?></label></th>
                <td>
                    <input type="email" id="personnel_email" name="personnel_email" value="<?php echo esc_attr($email); ?>" class="regular-text" placeholder="example@technosriracha.ac.th" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="personnel_phone"><?php _e('เบอร์โทรศัพท์ / เบอร์ภายใน', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="personnel_phone" name="personnel_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" placeholder="038-352440 ต่อ 101" />
                </td>
            </tr>
        </table>
        <p style="background: #f0f7ff; border-left: 4px solid #0047BA; padding: 12px 16px; margin-top: 15px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <span>
                <strong>💡 คำแนะนำรูปถ่าย:</strong> กำหนดรูปถ่ายของบุคลากรที่ช่อง <strong>"รูปประจำเรื่อง" (Featured Image)</strong> ทางขวามือ (แนะนำอัตราส่วนรูปแนวตั้ง 3:4 เพื่อความสวยงาม) และสามารถจัดเรียงลำดับบุคลากรภายในแต่ละฝ่ายได้ง่ายๆ ที่เมนู <strong>จัดลำดับตามฝ่าย</strong>
            </span>
            <a href="<?php echo admin_url('edit.php?post_type=personnel&page=personnel-reorder'); ?>" class="button button-secondary" target="_blank" style="font-weight: 600; color: #0047BA; border-color: #0047BA;">
                🔀 จัดลำดับบุคลากรตามฝ่าย
            </a>
        </p>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var select = document.getElementById('personnel_department_select');
            var input = document.getElementById('personnel_department');
            var wrap = document.getElementById('personnel_department_custom_wrap');
            if (!select || !input || !wrap) return;

            select.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    wrap.style.display = 'block';
                    input.focus();
                } else {
                    wrap.style.display = 'none';
                    input.value = this.value;
                }
            });

            var form = select.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (select.value && select.value !== '__custom__') {
                        input.value = select.value;
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Convert color name or hex into valid hex string
     */
    public function get_category_color_hex($val) {
        $map = array(
            'blue'    => '#0047BA',
            'emerald' => '#059669',
            'green'   => '#059669',
            'amber'   => '#d97706',
            'yellow'  => '#d97706',
            'orange'  => '#d97706',
            'rose'    => '#e11d48',
            'red'     => '#e11d48',
            'purple'  => '#7c3aed',
        );
        if (!empty($val) && isset($map[$val])) {
            return $map[$val];
        }
        if (!empty($val) && preg_match('/^#[a-fA-F0-9]{3,6}$/', $val)) {
            return $val;
        }
        return '#0047BA';
    }

    /**
     * Add color picker field to Add Event Category form
     */
    public function add_event_category_color_field() {
        ?>
        <div class="form-field term-color-wrap">
            <label for="category_color"><?php _e('สีประจำหมวดหมู่ (Category Color Picker & Calendar Dot)', 'project-core'); ?></label>
            <div style="margin-bottom: 8px;">
                <input type="text" name="category_color" id="category_color" value="#0047BA" class="event-category-color-picker" data-default-color="#0047BA" />
            </div>
            <div style="margin-top: 6px; display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <span style="font-size: 11px; color: #64748b; font-weight: 600;">สีแนะนำ:</span>
                <button type="button" class="button button-small color-preset-btn" data-color="#0047BA" style="background:#0047BA;color:#fff;border:none;font-weight:600;border-radius:4px;">🔵 น้ำเงิน</button>
                <button type="button" class="button button-small color-preset-btn" data-color="#059669" style="background:#059669;color:#fff;border:none;font-weight:600;border-radius:4px;">🟢 เขียว</button>
                <button type="button" class="button button-small color-preset-btn" data-color="#d97706" style="background:#d97706;color:#fff;border:none;font-weight:600;border-radius:4px;">🟠 ส้ม</button>
                <button type="button" class="button button-small color-preset-btn" data-color="#e11d48" style="background:#e11d48;color:#fff;border:none;font-weight:600;border-radius:4px;">🔴 แดง</button>
                <button type="button" class="button button-small color-preset-btn" data-color="#7c3aed" style="background:#7c3aed;color:#fff;border:none;font-weight:600;border-radius:4px;">🟣 ม่วง</button>
                <button type="button" class="button button-small color-preset-btn" data-color="#0284c7" style="background:#0284c7;color:#fff;border:none;font-weight:600;border-radius:4px;">🔷 ฟ้า</button>
            </div>
            <p class="description"><?php _e('คลิกเลือกสีจาก Color Picker หรือกรอกรหัสสี Hex (#RRGGBB) สีนี้จะนำไปแสดงเป็นจุดสีใต้วันที่บนปฏิทิน และแถบสีประจำแถวกิจกรรมโดยอัตโนมัติ', 'project-core'); ?></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            function initColorPickers() {
                if ($.fn.wpColorPicker) {
                    $('.event-category-color-picker').not('.wp-color-picker').wpColorPicker({
                        defaultColor: '#0047BA'
                    });
                }
            }
            initColorPickers();
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.data && settings.data.indexOf('action=add-tag') !== -1) {
                    if ($.fn.wpColorPicker && $('.event-category-color-picker').data('wpColorPicker')) {
                        $('.event-category-color-picker').wpColorPicker('color', '#0047BA');
                    } else {
                        $('.event-category-color-picker').val('#0047BA');
                    }
                }
            });
            $(document).on('click', '.color-preset-btn', function(e) {
                e.preventDefault();
                var c = $(this).data('color');
                var $input = $(this).closest('.term-color-wrap').find('.event-category-color-picker');
                if ($.fn.wpColorPicker && $input.data('wpColorPicker')) {
                    $input.wpColorPicker('color', c);
                } else {
                    $input.val(c).trigger('change');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Edit color picker field on Edit Event Category form
     */
    public function edit_event_category_color_field($term) {
        $color_raw = get_term_meta($term->term_id, 'category_color', true) ?: '#0047BA';
        $color = $this->get_category_color_hex($color_raw);
        ?>
        <tr class="form-field term-color-wrap">
            <th scope="row"><label for="category_color"><?php _e('สีประจำหมวดหมู่ (Category Color Picker & Calendar Dot)', 'project-core'); ?></label></th>
            <td>
                <div style="margin-bottom: 8px;">
                    <input type="text" name="category_color" id="category_color" value="<?php echo esc_attr($color); ?>" class="event-category-color-picker" data-default-color="#0047BA" />
                </div>
                <div style="margin-top: 6px; display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 600;">สีแนะนำ:</span>
                    <button type="button" class="button button-small color-preset-btn" data-color="#0047BA" style="background:#0047BA;color:#fff;border:none;font-weight:600;border-radius:4px;">🔵 น้ำเงิน</button>
                    <button type="button" class="button button-small color-preset-btn" data-color="#059669" style="background:#059669;color:#fff;border:none;font-weight:600;border-radius:4px;">🟢 เขียว</button>
                    <button type="button" class="button button-small color-preset-btn" data-color="#d97706" style="background:#d97706;color:#fff;border:none;font-weight:600;border-radius:4px;">🟠 ส้ม</button>
                    <button type="button" class="button button-small color-preset-btn" data-color="#e11d48" style="background:#e11d48;color:#fff;border:none;font-weight:600;border-radius:4px;">🔴 แดง</button>
                    <button type="button" class="button button-small color-preset-btn" data-color="#7c3aed" style="background:#7c3aed;color:#fff;border:none;font-weight:600;border-radius:4px;">🟣 ม่วง</button>
                    <button type="button" class="button button-small color-preset-btn" data-color="#0284c7" style="background:#0284c7;color:#fff;border:none;font-weight:600;border-radius:4px;">🔷 ฟ้า</button>
                </div>
                <p class="description"><?php _e('คลิกเลือกสีจาก Color Picker หรือกรอกรหัสสี Hex (#RRGGBB) สีนี้จะนำไปแสดงเป็นจุดสีใต้วันที่บนปฏิทิน และแถบสีประจำแถวกิจกรรมโดยอัตโนมัติ', 'project-core'); ?></p>
            </td>
        </tr>
        <script>
        jQuery(document).ready(function($) {
            if ($.fn.wpColorPicker) {
                $('.event-category-color-picker').wpColorPicker({
                    defaultColor: '#0047BA'
                });
            }
            $(document).on('click', '.color-preset-btn', function(e) {
                e.preventDefault();
                var c = $(this).data('color');
                var $input = $(this).closest('.term-color-wrap').find('.event-category-color-picker');
                if ($.fn.wpColorPicker && $input.data('wpColorPicker')) {
                    $input.wpColorPicker('color', c);
                } else {
                    $input.val(c).trigger('change');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save category color field
     */
    public function save_event_category_color_field($term_id) {
        if (isset($_POST['category_color'])) {
            $raw = sanitize_text_field($_POST['category_color']);
            $val = sanitize_hex_color($raw);
            if (!$val) {
                $val = $this->get_category_color_hex($raw);
            }
            update_term_meta($term_id, 'category_color', $val);
        }
    }

    /**
     * Add column header to event_category admin table
     */
    public function add_event_category_color_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'name') {
                $new_columns['category_color'] = __('สีประจำหมวด (จุดปฏิทิน)', 'project-core');
            }
        }
        return $new_columns;
    }

    /**
     * Render column content in event_category admin table
     */
    public function render_event_category_color_column($content, $column_name, $term_id) {
        if ($column_name === 'category_color') {
            $color = get_term_meta($term_id, 'category_color', true) ?: '#0047BA';
            $hex = $this->get_category_color_hex($color);
            return sprintf(
                '<span style="display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                    <span style="display:inline-block; width:16px; height:16px; border-radius:50%%; background:%s; box-shadow:0 0 0 1px rgba(0,0,0,0.15); flex-shrink:0;"></span>
                    <code style="font-size:11px; background:#f1f5f9; color:#334155; padding:2px 6px; border-radius:4px; font-family:monospace;">%s</code>
                </span>',
                esc_attr($hex),
                esc_html($hex)
            );
        }
        return $content;
    }

    /**
     * Add order field to Add Personnel Category form
     */
    public function add_personnel_category_order_field() {
        ?>
        <div class="form-field term-order-wrap">
            <label for="category_order"><?php _e('ลำดับการแสดงผล (Category Order)', 'project-core'); ?></label>
            <input type="number" name="category_order" id="category_order" value="0" min="0" step="1" style="width: 120px;" />
            <p class="description"><?php _e('ระบุตัวเลขลำดับการแสดงผลของกลุ่ม (เช่น 1, 2, 3...) เลขน้อยกว่าจะแสดงก่อนบนหน้าเว็บ', 'project-core'); ?></p>
        </div>
        <?php
    }

    /**
     * Edit order field on Edit Personnel Category form
     */
    public function edit_personnel_category_order_field($term) {
        $order = (int) get_term_meta($term->term_id, 'category_order', true);
        ?>
        <tr class="form-field term-order-wrap">
            <th scope="row"><label for="category_order"><?php _e('ลำดับการแสดงผล (Category Order)', 'project-core'); ?></label></th>
            <td>
                <input type="number" name="category_order" id="category_order" value="<?php echo esc_attr($order); ?>" min="0" step="1" style="width: 120px;" />
                <p class="description"><?php _e('ระบุตัวเลขลำดับการแสดงผลของกลุ่ม (เช่น 1, 2, 3...) เลขน้อยกว่าจะแสดงก่อนบนหน้าเว็บ', 'project-core'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save category order field
     */
    public function save_personnel_category_order_field($term_id) {
        if (isset($_POST['category_order'])) {
            $val = absint($_POST['category_order']);
            update_term_meta($term_id, 'category_order', $val);
        }
    }

    /**
     * Add column header to personnel_category admin table
     */
    public function add_personnel_category_order_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'cb') {
                $new_columns['category_handle'] = '<span class="dashicons dashicons-move" title="' . esc_attr__('ลากเพื่อสลับลำดับ', 'project-core') . '"></span>';
                $new_columns['category_order']  = __('ลำดับ', 'project-core');
            }
        }
        return $new_columns;
    }

    /**
     * Render column content in personnel_category admin table
     */
    public function render_personnel_category_order_column($content, $column_name, $term_id) {
        if ($column_name === 'category_handle') {
            return '<span class="dashicons dashicons-move category-drag-handle" style="cursor: grab; color: #50575e; font-size: 20px; line-height: 28px;" title="คลิกค้างแล้วลากเพื่อสลับลำดับกลุ่ม"></span>';
        }
        if ($column_name === 'category_order') {
            $order = (int) get_term_meta($term_id, 'category_order', true);
            return '<span class="category-order-badge" style="display: inline-block; min-width: 32px; text-align: center; padding: 3px 8px; background: #e0f2fe; color: #0284c7; border-radius: 6px; font-weight: 700; font-size: 12px;">#' . esc_html($order) . '</span>';
        }
        return $content;
    }

    /**
     * Render SAR Report Meta Box
     */
    public function render_sar_meta_box($post) {
        wp_nonce_field('project_core_sar_nonce', 'project_core_sar_nonce_field');

        $file_url     = get_post_meta($post->ID, 'sar_file_url', true) ?: '';
        $file_id      = get_post_meta($post->ID, 'sar_file_id', true) ?: '';
        $external_url = get_post_meta($post->ID, 'sar_external_url', true) ?: '';
        $report_no    = get_post_meta($post->ID, 'sar_report_no', true) ?: '';
        ?>
        <table class="form-table" style="margin-top: 0;">
            <tr>
                <th scope="row"><label for="sar_report_no"><?php _e('รหัสเอกสาร / ฉบับที่', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="sar_report_no" name="sar_report_no" value="<?php echo esc_attr($report_no); ?>" class="regular-text" placeholder="เช่น SAR-2568, ฉบับสมบูรณ์" />
                    <p class="description"><?php _e('รหัสอ้างอิงเอกสารรายงาน (ถ้ามี)', 'project-core'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="sar_file_url"><?php _e('ไฟล์แนบรายงาน (PDF / เอกสาร)', 'project-core'); ?> <span style="color:#d63638;">*</span></label></th>
                <td>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; max-width: 650px;">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                            <input type="text" id="sar_file_url" name="sar_file_url" value="<?php echo esc_attr($file_url); ?>" class="large-text" placeholder="https://... ลิงก์ไฟล์ PDF" style="font-family: monospace; font-size: 13px;" />
                            <input type="hidden" id="sar_file_id" name="sar_file_id" value="<?php echo esc_attr($file_id); ?>" />
                            <button type="button" class="button button-primary" id="btn_upload_sar_file" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
                                <?php _e('เลือก / อัปโหลดไฟล์', 'project-core'); ?>
                            </button>
                            <button type="button" class="button" id="btn_clear_sar_file" style="<?php echo empty($file_url) ? 'display: none;' : ''; ?> color: #b91c1c;">
                                <?php _e('ลบไฟล์', 'project-core'); ?>
                            </button>
                        </div>

                        <div id="sar_file_preview" style="<?php echo empty($file_url) ? 'display: none;' : ''; ?>">
                            <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="button button-small" style="display: inline-flex; align-items: center; gap: 4px; color: #0047BA;">
                                <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; line-height: 14px;"></span>
                                <?php _e('ทดลองเปิดดูไฟล์', 'project-core'); ?>
                            </a>
                        </div>
                    </div>
                    <p class="description"><?php _e('กดปุ่ม "เลือก / อัปโหลดไฟล์" เพื่อเลือกไฟล์ PDF จากเครื่องหรือคลังสื่อ', 'project-core'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="sar_external_url"><?php _e('ลิงก์เปิดอ่านออนไลน์ (AnyFlip / E-Book)', 'project-core'); ?></label></th>
                <td>
                    <input type="url" id="sar_external_url" name="sar_external_url" value="<?php echo esc_attr($external_url); ?>" class="large-text" placeholder="https://anyflip.com/... หรือ Google Drive" />
                    <p class="description"><?php _e('หากมีลิงก์ E-Book / Flipbook สามารถใส่เพื่อให้นักเรียนหรือบุคคลทั่วไปกดอ่านแบบออนไลน์ได้โดยตรง', 'project-core'); ?></p>
                </td>
            </tr>
        </table>

        <div style="background: #eff6ff; border-left: 4px solid #0047BA; padding: 12px 16px; margin-top: 15px; border-radius: 0 6px 6px 0;">
            <p style="margin: 0; font-size: 13px; color: #1e3a8a;">
                <strong>💡 คำแนะนำ:</strong> กรุณาเลือก <strong>"ปีการศึกษา"</strong> ที่กล่องด้านขวามือ เพื่อจัดกลุ่มรายงาน SAR ให้อยู่ในปีการศึกษาที่ถูกต้อง และสามารถกำหนด <strong>"ภาพหน้าปก"</strong> ที่กล่อง Featured Image ทางขวามือได้
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var sarFileFrame;
            $('#btn_upload_sar_file').on('click', function(e) {
                e.preventDefault();
                if (sarFileFrame) {
                    sarFileFrame.open();
                    return;
                }
                sarFileFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดไฟล์รายงาน SAR (PDF)', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้ไฟล์นี้', 'project-core')); ?>' },
                    multiple: false
                });
                sarFileFrame.on('select', function() {
                    var attachment = sarFileFrame.state().get('selection').first().toJSON();
                    $('#sar_file_url').val(attachment.url);
                    $('#sar_file_id').val(attachment.id);

                    $('#sar_file_preview').show().find('a').attr('href', attachment.url);
                    $('#btn_clear_sar_file').show();
                });
                sarFileFrame.open();
            });

            $('#btn_clear_sar_file').on('click', function(e) {
                e.preventDefault();
                $('#sar_file_url').val('');
                $('#sar_file_id').val('');
                $('#sar_file_preview').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Render Educational MIS Comprehensive Meta Box
     */
    public function render_education_mis_meta_box($post) {
        wp_nonce_field('project_core_mis_nonce', 'project_core_mis_nonce_field');

        // General
        $year_text = get_post_meta($post->ID, 'mis_academic_year_text', true) ?: '';
        $pdf_url   = get_post_meta($post->ID, 'mis_report_pdf_url', true) ?: '';
        $pdf_id    = get_post_meta($post->ID, 'mis_report_pdf_id', true) ?: '';

        // 1. Students
        $students_total = get_post_meta($post->ID, 'mis_students_total', true) ?: '';
        $students_pvch  = get_post_meta($post->ID, 'mis_students_pvch_total', true) ?: '';
        $students_pvs   = get_post_meta($post->ID, 'mis_students_pvs_total', true) ?: '';
        $students_dept  = get_post_meta($post->ID, 'mis_students_by_dept', true) ?: '[]';

        // 2. Personnel
        $personnel_total = get_post_meta($post->ID, 'mis_personnel_total', true) ?: '';
        $personnel_exec  = get_post_meta($post->ID, 'mis_personnel_executives', true) ?: '';
        $personnel_teach = get_post_meta($post->ID, 'mis_personnel_teachers', true) ?: '';
        $personnel_supp  = get_post_meta($post->ID, 'mis_personnel_support', true) ?: '';
        $personnel_dept  = get_post_meta($post->ID, 'mis_personnel_breakdown', true) ?: '[]';

        // 3. Curriculum
        $curr_levels     = get_post_meta($post->ID, 'mis_curriculum_levels_count', true) ?: '2';
        $curr_depts      = get_post_meta($post->ID, 'mis_departments_count', true) ?: '8';
        $curr_progs      = get_post_meta($post->ID, 'mis_programs_count', true) ?: '14';
        $curr_list       = get_post_meta($post->ID, 'mis_curriculum_list', true) ?: '[]';

        // 4. Admissions & Graduates
        $admissions_data = get_post_meta($post->ID, 'mis_admissions_graduates', true) ?: '[]';

        // 5. Achievements
        $gpax_avg        = get_post_meta($post->ID, 'mis_gpax_avg', true) ?: '';
        $gpax_pvch       = get_post_meta($post->ID, 'mis_gpax_pvch', true) ?: '';
        $gpax_pvs        = get_post_meta($post->ID, 'mis_gpax_pvs', true) ?: '';
        $vnet_rate       = get_post_meta($post->ID, 'mis_vnet_pass_rate', true) ?: '';
        $standard_rate   = get_post_meta($post->ID, 'mis_vocational_standard_pass_rate', true) ?: '';
        $achieve_list    = get_post_meta($post->ID, 'mis_achievements_list', true) ?: '[]';

        // 6. Employment & Higher Ed
        $emp_total_rate  = get_post_meta($post->ID, 'mis_employment_total_rate', true) ?: '';
        $emp_field_rate  = get_post_meta($post->ID, 'mis_employed_field_rate', true) ?: '';
        $emp_other_rate  = get_post_meta($post->ID, 'mis_employed_other_rate', true) ?: '';
        $emp_higher_rate = get_post_meta($post->ID, 'mis_higher_education_rate', true) ?: '';
        $emp_free_rate   = get_post_meta($post->ID, 'mis_freelance_business_rate', true) ?: '';
        $emp_unemp_rate  = get_post_meta($post->ID, 'mis_unemployed_or_other_rate', true) ?: '';
        $emp_data        = get_post_meta($post->ID, 'mis_employment_data', true) ?: '[]';

        // 7. Partner Enterprises
        $mou_count       = get_post_meta($post->ID, 'mis_mou_partners_count', true) ?: '';
        $dual_count      = get_post_meta($post->ID, 'mis_dual_vocational_count', true) ?: '';
        $intern_count    = get_post_meta($post->ID, 'mis_intern_students_count', true) ?: '';
        $intern_pay      = get_post_meta($post->ID, 'mis_intern_allowance_avg', true) ?: '';
        $partner_list    = get_post_meta($post->ID, 'mis_partner_enterprises', true) ?: '[]';
        ?>
        <style>
            .mis-nav-tabs { display: flex; flex-wrap: wrap; gap: 4px; border-bottom: 2px solid #0047BA; margin-bottom: 20px; padding-bottom: 4px; }
            .mis-nav-tab { padding: 8px 14px; background: #f1f5f9; border: 1px solid #cbd5e1; border-bottom: none; border-radius: 6px 6px 0 0; cursor: pointer; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; }
            .mis-nav-tab.active { background: #0047BA; color: #ffffff; border-color: #0047BA; }
            .mis-tab-content { display: none; }
            .mis-tab-content.active { display: block; }
            .mis-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px; }
            .mis-stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; }
            .mis-stat-card label { font-size: 12px; font-weight: bold; color: #64748b; display: block; margin-bottom: 6px; }
            .mis-stat-card input { width: 100%; font-size: 16px; font-weight: 600; }
            .mis-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
            .mis-table th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 12px; text-align: left; }
            .mis-table td { border: 1px solid #e2e8f0; padding: 6px 8px; font-size: 13px; }
            .mis-table input, .mis-table select { width: 100%; box-sizing: border-box; }
            .mis-btn-remove { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; border-radius: 4px; cursor: pointer; padding: 4px 8px; font-size: 11px; }
            .mis-btn-remove:hover { background: #fecaca; }
            .mis-btn-add { background: #eff6ff; color: #1d4ed8; border: 1px dashed #93c5fd; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: 600; margin-top: 10px; display: inline-flex; align-items: center; gap: 4px; }
            .mis-btn-add:hover { background: #dbeafe; }
        </style>

        <div class="mis-admin-wrapper">
            <!-- Year Heading & Quick Year Selector Info -->
            <div style="background: #f0f7ff; border-left: 4px solid #0047BA; padding: 12px 16px; border-radius: 0 8px 8px 0; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #0047BA; font-size: 14px;"><?php _e('ข้อมูลสารสนเทศทางการศึกษา (7 ด้านมาตรฐาน)', 'project-core'); ?></strong>
                    <p style="margin: 3px 0 0; color: #475569; font-size: 12px;"><?php _e('กรอกข้อมูลสรุปและตารางรายการในแต่ละด้าน เพื่อแสดงผลบนหน้าเว็บไซต์สารสนเทศทางการศึกษา', 'project-core'); ?></p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="mis_academic_year_text" style="font-weight: bold; font-size: 13px;"><?php _e('ปีการศึกษา:', 'project-core'); ?></label>
                    <input type="text" id="mis_academic_year_text" name="mis_academic_year_text" value="<?php echo esc_attr($year_text); ?>" placeholder="เช่น 2568" style="width: 100px; font-weight: bold; text-align: center;" />
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="mis-nav-tabs" id="mis-tabs-nav">
                <button type="button" class="mis-nav-tab active" data-tab="tab-students">1. นักเรียน–นักศึกษา</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-personnel">2. บุคลากร</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-curriculum">3. หลักสูตร & สาขาวิชา</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-admissions">4. สถิติรับเข้า & จบการศึกษา</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-achievements">5. ผลสัมฤทธิ์ & V-NET</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-employment">6. มีงานทำ & ศึกษาต่อ</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-partners">7. สถานประกอบการคู่ความร่วมมือ</button>
                <button type="button" class="mis-nav-tab" data-tab="tab-report">8. ไฟล์รายงาน PDF</button>
            </div>

            <!-- Tab 1: นักเรียน–นักศึกษา -->
            <div class="mis-tab-content active" id="tab-students">
                <div class="mis-stat-grid">
                    <div class="mis-stat-card">
                        <label><?php _e('จำนวนนักเรียน-นักศึกษา รวมทั้งสิ้น (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_students_total" value="<?php echo esc_attr($students_total); ?>" placeholder="1850" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ระดับ ปวช. รวม (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_students_pvch_total" value="<?php echo esc_attr($students_pvch); ?>" placeholder="1120" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ระดับ ปวส. รวม (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_students_pvs_total" value="<?php echo esc_attr($students_pvs); ?>" placeholder="730" />
                    </div>
                </div>

                <h4 style="margin: 16px 0 6px;"><?php _e('รายละเอียดสถิตินักเรียน–นักศึกษา แยกตามระดับชั้นและสาขาวิชา', 'project-core'); ?></h4>
                <table class="mis-table" id="table-students-dept">
                    <thead>
                        <tr>
                            <th style="width: 15%;"><?php _e('ระดับชั้น', 'project-core'); ?></th>
                            <th style="width: 35%;"><?php _e('สาขาวิชา', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('ชาย (คน)', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('หญิง (คน)', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('รวม (คน)', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-student-row">＋ <?php _e('เพิ่มสาขาวิชา', 'project-core'); ?></button>
                <textarea id="mis_students_by_dept" name="mis_students_by_dept" style="display:none;"><?php echo esc_textarea($students_dept); ?></textarea>
            </div>

            <!-- Tab 2: บุคลากร -->
            <div class="mis-tab-content" id="tab-personnel">
                <div class="mis-stat-grid">
                    <div class="mis-stat-card">
                        <label><?php _e('รวมบุคลากรทั้งหมด (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_personnel_total" value="<?php echo esc_attr($personnel_total); ?>" placeholder="85" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ผู้บริหารสถานศึกษา (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_personnel_executives" value="<?php echo esc_attr($personnel_exec); ?>" placeholder="5" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ครูประจำการ / ครูผู้สอน (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_personnel_teachers" value="<?php echo esc_attr($personnel_teach); ?>" placeholder="58" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('บุคลากรสายสนับสนุน (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_personnel_support" value="<?php echo esc_attr($personnel_supp); ?>" placeholder="22" />
                    </div>
                </div>

                <h4 style="margin: 16px 0 6px;"><?php _e('จำแนกบุคลากรตามฝ่ายงาน / แผนกวิชา', 'project-core'); ?></h4>
                <table class="mis-table" id="table-personnel-breakdown">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php _e('กลุ่ม / แผนกงาน', 'project-core'); ?></th>
                            <th style="width: 35%;"><?php _e('ตำแหน่ง / บทบาท', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('จำนวน (คน)', 'project-core'); ?></th>
                            <th style="width: 20%;"><?php _e('หมายเหตุ / วุฒิ', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-personnel-row">＋ <?php _e('เพิ่มรายการฝ่ายงาน', 'project-core'); ?></button>
                <textarea id="mis_personnel_breakdown" name="mis_personnel_breakdown" style="display:none;"><?php echo esc_textarea($personnel_dept); ?></textarea>
            </div>

            <!-- Tab 3: หลักสูตร & สาขาวิชา -->
            <div class="mis-tab-content" id="tab-curriculum">
                <div class="mis-stat-grid">
                    <div class="mis-stat-card">
                        <label><?php _e('จำนวนระดับหลักสูตร (ระดับ)', 'project-core'); ?></label>
                        <input type="number" name="mis_curriculum_levels_count" value="<?php echo esc_attr($curr_levels); ?>" placeholder="2" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('จำนวนสาขาวิชาที่เปิดสอน (สาขาวิชา)', 'project-core'); ?></label>
                        <input type="number" name="mis_departments_count" value="<?php echo esc_attr($curr_depts); ?>" placeholder="8" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('จำนวนสาขางานทั้งหมด (สาขางาน)', 'project-core'); ?></label>
                        <input type="number" name="mis_programs_count" value="<?php echo esc_attr($curr_progs); ?>" placeholder="14" />
                    </div>
                </div>

                <h4 style="margin: 16px 0 6px;"><?php _e('รายชื่อหลักสูตร สาขาวิชา และสาขางานที่เปิดสอน', 'project-core'); ?></h4>
                <table class="mis-table" id="table-curriculum-list">
                    <thead>
                        <tr>
                            <th style="width: 15%;"><?php _e('ระดับ', 'project-core'); ?></th>
                            <th style="width: 25%;"><?php _e('สาขาวิชา', 'project-core'); ?></th>
                            <th style="width: 35%;"><?php _e('สาขางานที่เปิดสอน', 'project-core'); ?></th>
                            <th style="width: 20%;"><?php _e('รูปแบบการศึกษา', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-curriculum-row">＋ <?php _e('เพิ่มหลักสูตร/สาขาวิชา', 'project-core'); ?></button>
                <textarea id="mis_curriculum_list" name="mis_curriculum_list" style="display:none;"><?php echo esc_textarea($curr_list); ?></textarea>
            </div>

            <!-- Tab 4: สถิติรับเข้า & จบการศึกษา -->
            <div class="mis-tab-content" id="tab-admissions">
                <p style="color: #64748b; font-size: 13px;"><?php _e('สถิติเปรียบเทียบจำนวนผู้เข้าเรียนใหม่และผู้สำเร็จการศึกษาในแต่ละปีการศึกษา', 'project-core'); ?></p>
                <table class="mis-table" id="table-admissions-graduates">
                    <thead>
                        <tr>
                            <th style="width: 12%;"><?php _e('ปีการศึกษา', 'project-core'); ?></th>
                            <th style="width: 13%;"><?php _e('รับเข้า ปวช. (คน)', 'project-core'); ?></th>
                            <th style="width: 13%;"><?php _e('รับเข้า ปวส. (คน)', 'project-core'); ?></th>
                            <th style="width: 13%;"><?php _e('รวมรับเข้า (คน)', 'project-core'); ?></th>
                            <th style="width: 13%;"><?php _e('สำเร็จ ปวช. (คน)', 'project-core'); ?></th>
                            <th style="width: 13%;"><?php _e('สำเร็จ ปวส. (คน)', 'project-core'); ?></th>
                            <th style="width: 13%;"><?php _e('รวมผู้สำเร็จ (คน)', 'project-core'); ?></th>
                            <th style="width: 10%;"><?php _e('ร้อยละ (%)', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-admissions-row">＋ <?php _e('เพิ่มแถวปีการศึกษา', 'project-core'); ?></button>
                <textarea id="mis_admissions_graduates" name="mis_admissions_graduates" style="display:none;"><?php echo esc_textarea($admissions_data); ?></textarea>
            </div>

            <!-- Tab 5: ผลสัมฤทธิ์ & V-NET -->
            <div class="mis-tab-content" id="tab-achievements">
                <div class="mis-stat-grid">
                    <div class="mis-stat-card">
                        <label><?php _e('ผลการเรียนเฉลี่ยสะสมรวม (GPAX)', 'project-core'); ?></label>
                        <input type="text" name="mis_gpax_avg" value="<?php echo esc_attr($gpax_avg); ?>" placeholder="3.28" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('GPAX เฉลี่ยระดับ ปวช.', 'project-core'); ?></label>
                        <input type="text" name="mis_gpax_pvch" value="<?php echo esc_attr($gpax_pvch); ?>" placeholder="3.21" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('GPAX เฉลี่ยระดับ ปวส.', 'project-core'); ?></label>
                        <input type="text" name="mis_gpax_pvs" value="<?php echo esc_attr($gpax_pvs); ?>" placeholder="3.36" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ร้อยละการผ่านเกณฑ์ V-NET (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_vnet_pass_rate" value="<?php echo esc_attr($vnet_rate); ?>" placeholder="89.4" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ร้อยละการผ่านเกณฑ์มาตรฐานวิชาชีพ (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_vocational_standard_pass_rate" value="<?php echo esc_attr($standard_rate); ?>" placeholder="96.2" />
                    </div>
                </div>

                <h4 style="margin: 16px 0 6px;"><?php _e('ผลสัมฤทธิ์ทางการเรียนและการทดสอบ จำแนกตามแผนกวิชา', 'project-core'); ?></h4>
                <table class="mis-table" id="table-achievements-list">
                    <thead>
                        <tr>
                            <th style="width: 35%;"><?php _e('แผนกวิชา / สาระการเรียนรู้', 'project-core'); ?></th>
                            <th style="width: 20%;"><?php _e('GPAX เฉลี่ย', 'project-core'); ?></th>
                            <th style="width: 20%;"><?php _e('ผลทดสอบ V-NET (%)', 'project-core'); ?></th>
                            <th style="width: 20%;"><?php _e('ผ่านประเมินมาตรฐานวิชาชีพ (%)', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-achieve-row">＋ <?php _e('เพิ่มแถวแผนกวิชา', 'project-core'); ?></button>
                <textarea id="mis_achievements_list" name="mis_achievements_list" style="display:none;"><?php echo esc_textarea($achieve_list); ?></textarea>
            </div>

            <!-- Tab 6: มีงานทำ & ศึกษาต่อ -->
            <div class="mis-tab-content" id="tab-employment">
                <div class="mis-stat-grid">
                    <div class="mis-stat-card" style="border-left: 4px solid #10b981;">
                        <label><?php _e('อัตราความสำเร็จรวม (มีงานทำ+ศึกษาต่อ) (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_employment_total_rate" value="<?php echo esc_attr($emp_total_rate); ?>" placeholder="96.8" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ได้งานทำตรงสาขาวิชา (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_employed_field_rate" value="<?php echo esc_attr($emp_field_rate); ?>" placeholder="62.5" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ได้งานทำต่างสาขาวิชา (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_employed_other_rate" value="<?php echo esc_attr($emp_other_rate); ?>" placeholder="14.3" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ศึกษาต่อในระดับสูงขึ้น (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_higher_education_rate" value="<?php echo esc_attr($emp_higher_rate); ?>" placeholder="15.0" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('ประกอบอาชีพอิสระ/ธุรกิจส่วนตัว (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_freelance_business_rate" value="<?php echo esc_attr($emp_free_rate); ?>" placeholder="5.0" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('อื่นๆ / เตรียมตัว (%)', 'project-core'); ?></label>
                        <input type="text" name="mis_unemployed_or_other_rate" value="<?php echo esc_attr($emp_unemp_rate); ?>" placeholder="3.2" />
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: baseline; margin: 16px 0 6px;">
                    <h4 style="margin: 0;"><?php _e('ข้อมูลสัดส่วนและการประกอบอาชีพของผู้สำเร็จการศึกษา', 'project-core'); ?></h4>
                    <span style="font-size: 12px; color: #0284c7; font-weight: 500;">⚡ กรอกเพียงจำนวนคน (คน) ระบบจะคำนวณร้อยละ (%) และอัปเดตสรุปด้านบนให้อัตโนมัติ</span>
                </div>
                <table class="mis-table" id="table-employment-data">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?php _e('สถานะ / กลุ่มการมีงานทำ', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('จำนวน (คน)', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('ร้อยละ (%) [คำนวณอัตโนมัติ]', 'project-core'); ?></th>
                            <th style="width: 25%;"><?php _e('ฐานเงินเดือนเฉลี่ย (บาท/เดือน)', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr style="background: #f8fafc; font-weight: bold; border-top: 2px solid #cbd5e1;">
                            <td style="text-align: right; color: #334155;"><?php _e('รวมผู้สำเร็จการศึกษาทั้งหมด:', 'project-core'); ?></td>
                            <td id="emp-total-count" style="font-family: monospace; font-size: 13px; color: #0047BA;">0 คน</td>
                            <td id="emp-total-pct" style="font-family: monospace; font-size: 13px; color: #059669;">100.0%</td>
                            <td colspan="2" style="font-size: 11px; color: #64748b; font-weight: normal;">(คำนวณสัดส่วนร้อยละให้อัตโนมัติจากจำนวนคน)</td>
                        </tr>
                    </tfoot>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-employment-row">＋ <?php _e('เพิ่มแถวข้อมูลการมีงานทำ', 'project-core'); ?></button>
                <textarea id="mis_employment_data" name="mis_employment_data" style="display:none;"><?php echo esc_textarea($emp_data); ?></textarea>
            </div>

            <!-- Tab 7: สถานประกอบการคู่ความร่วมมือ -->
            <div class="mis-tab-content" id="tab-partners">
                <div class="mis-stat-grid">
                    <div class="mis-stat-card">
                        <label><?php _e('จำนวนสถานประกอบการที่ทำ MOU (แห่ง)', 'project-core'); ?></label>
                        <input type="number" name="mis_mou_partners_count" value="<?php echo esc_attr($mou_count); ?>" placeholder="54" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('สถานประกอบการฝึกทวิภาคี (แห่ง)', 'project-core'); ?></label>
                        <input type="number" name="mis_dual_vocational_count" value="<?php echo esc_attr($dual_count); ?>" placeholder="42" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('นักศึกษาเข้าร่วมฝึกอาชีพ (คน)', 'project-core'); ?></label>
                        <input type="number" name="mis_intern_students_count" value="<?php echo esc_attr($intern_count); ?>" placeholder="640" />
                    </div>
                    <div class="mis-stat-card">
                        <label><?php _e('เบี้ยเลี้ยงฝึกอาชีพเฉลี่ย (บาท/เดือน)', 'project-core'); ?></label>
                        <input type="number" name="mis_intern_allowance_avg" value="<?php echo esc_attr($intern_pay); ?>" placeholder="10500" />
                    </div>
                </div>

                <h4 style="margin: 16px 0 6px;"><?php _e('รายชื่อสถานประกอบการคู่ความร่วมมือและการฝึกอาชีพชั้นนำ', 'project-core'); ?></h4>
                <table class="mis-table" id="table-partner-enterprises">
                    <thead>
                        <tr>
                            <th style="width: 12%; text-align: center;"><?php _e('โลโก้', 'project-core'); ?></th>
                            <th style="width: 25%;"><?php _e('ชื่อสถานประกอบการ / องค์กร', 'project-core'); ?></th>
                            <th style="width: 23%;"><?php _e('ประเภทอุตสาหกรรม / กลุ่มธุรกิจ', 'project-core'); ?></th>
                            <th style="width: 20%;"><?php _e('รูปแบบความร่วมมือ', 'project-core'); ?></th>
                            <th style="width: 15%;"><?php _e('สาขาวิชาที่ร่วมมือ', 'project-core'); ?></th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="mis-btn-add" id="btn-add-partner-row">＋ <?php _e('เพิ่มสถานประกอบการ', 'project-core'); ?></button>
                <textarea id="mis_partner_enterprises" name="mis_partner_enterprises" style="display:none;"><?php echo esc_textarea($partner_list); ?></textarea>
            </div>

            <!-- Tab 8: ไฟล์รายงาน PDF -->
            <div class="mis-tab-content" id="tab-report">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; max-width: 700px;">
                    <label for="mis_report_pdf_url" style="display: block; font-weight: bold; margin-bottom: 8px;">
                        <?php _e('เอกสารรายงานสารสนเทศทางการศึกษาฉบับสมบูรณ์ (PDF)', 'project-core'); ?>
                    </label>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                        <input type="text" id="mis_report_pdf_url" name="mis_report_pdf_url" value="<?php echo esc_attr($pdf_url); ?>" class="large-text" placeholder="https://... ลิงก์ไฟล์ PDF รายงานฉบับสมบูรณ์" style="font-family: monospace; font-size: 13px;" />
                        <input type="hidden" id="mis_report_pdf_id" name="mis_report_pdf_id" value="<?php echo esc_attr($pdf_id); ?>" />
                        <button type="button" class="button button-primary" id="btn_upload_mis_pdf" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
                            <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
                            <?php _e('อัปโหลดไฟล์', 'project-core'); ?>
                        </button>
                        <button type="button" class="button" id="btn_clear_mis_pdf" style="<?php echo empty($pdf_url) ? 'display: none;' : ''; ?> color: #b91c1c;">
                            <?php _e('ลบไฟล์', 'project-core'); ?>
                        </button>
                    </div>
                    <div id="mis_pdf_preview" style="<?php echo empty($pdf_url) ? 'display: none;' : ''; ?> margin-top: 6px;">
                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="button button-small" style="display: inline-flex; align-items: center; gap: 4px; color: #0047BA;">
                            <span class="dashicons dashicons-pdf" style="font-size: 14px; width: 14px; height: 14px; line-height: 14px;"></span>
                            <?php _e('ทดลองเปิดดูไฟล์ PDF', 'project-core'); ?>
                        </a>
                    </div>
                    <p class="description" style="margin-top: 8px; color: #64748b;">
                        <?php _e('รองรับไฟล์ .pdf ขนาดไม่เกิน 50MB เพื่อให้นักเรียน ผู้ปกครอง และหน่วยงานภายนอกสามารถดาวน์โหลดรายงานฉบับสมบูรณ์ได้จากหน้าเว็บ', 'project-core'); ?>
                    </p>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('#mis-tabs-nav .mis-nav-tab').on('click', function(e) {
                e.preventDefault();
                $('#mis-tabs-nav .mis-nav-tab').removeClass('active');
                $(this).addClass('active');
                var tabId = $(this).data('tab');
                $('.mis-tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
            });

            // Helper to render student rows
            function renderStudentRows() {
                var data = [];
                try { data = JSON.parse($('#mis_students_by_dept').val() || '[]'); } catch(e) { data = []; }
                var $tbody = $('#table-students-dept tbody').empty();
                data.forEach(function(row, idx) {
                    var tr = $('<tr>' +
                        '<td><select class="r-level"><option value="ปวช." '+(row.level==='ปวช.'?'selected':'')+'>ปวช.</option><option value="ปวส." '+(row.level==='ปวส.'?'selected':'')+'>ปวส.</option></select></td>' +
                        '<td><input type="text" class="r-dept" value="'+(row.department||'')+'" placeholder="ชื่อสาขาวิชา"></td>' +
                        '<td><input type="number" class="r-male" value="'+(row.male||0)+'"></td>' +
                        '<td><input type="number" class="r-female" value="'+(row.female||0)+'"></td>' +
                        '<td><input type="number" class="r-total" value="'+(row.total||0)+'"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
            }

            function saveStudentRows() {
                var data = [];
                $('#table-students-dept tbody tr').each(function() {
                    data.push({
                        level: $(this).find('.r-level').val(),
                        department: $(this).find('.r-dept').val(),
                        male: parseInt($(this).find('.r-male').val(), 10) || 0,
                        female: parseInt($(this).find('.r-female').val(), 10) || 0,
                        total: parseInt($(this).find('.r-total').val(), 10) || 0
                    });
                });
                $('#mis_students_by_dept').val(JSON.stringify(data));
            }

            $('#btn-add-student-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_students_by_dept').val() || '[]'); } catch(e){}
                data.push({ level: 'ปวช.', department: '', male: 0, female: 0, total: 0 });
                $('#mis_students_by_dept').val(JSON.stringify(data));
                renderStudentRows();
            });

            $('#table-students-dept').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_students_by_dept').val() || '[]');
                data.splice(idx, 1);
                $('#mis_students_by_dept').val(JSON.stringify(data));
                renderStudentRows();
            });

            $('#table-students-dept').on('input change', 'input, select', function() {
                // Auto calculate total male+female if user changes male or female
                var $tr = $(this).closest('tr');
                if ($(this).hasClass('r-male') || $(this).hasClass('r-female')) {
                    var m = parseInt($tr.find('.r-male').val(), 10) || 0;
                    var f = parseInt($tr.find('.r-female').val(), 10) || 0;
                    $tr.find('.r-total').val(m + f);
                }
                saveStudentRows();
            });

            // Helper to render personnel rows
            function renderPersonnelRows() {
                var data = [];
                try { data = JSON.parse($('#mis_personnel_breakdown').val() || '[]'); } catch(e){}
                var $tbody = $('#table-personnel-breakdown tbody').empty();
                data.forEach(function(row, idx) {
                    var tr = $('<tr>' +
                        '<td><input type="text" class="r-cat" value="'+(row.category||'')+'" placeholder="แผนก/กลุ่มงาน"></td>' +
                        '<td><input type="text" class="r-pos" value="'+(row.position||'')+'" placeholder="ตำแหน่ง"></td>' +
                        '<td><input type="number" class="r-count" value="'+(row.count||0)+'"></td>' +
                        '<td><input type="text" class="r-notes" value="'+(row.notes||'')+'" placeholder="วุฒิ/หมายเหตุ"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
            }
            function savePersonnelRows() {
                var data = [];
                $('#table-personnel-breakdown tbody tr').each(function() {
                    data.push({
                        category: $(this).find('.r-cat').val(),
                        position: $(this).find('.r-pos').val(),
                        count: parseInt($(this).find('.r-count').val(), 10) || 0,
                        notes: $(this).find('.r-notes').val()
                    });
                });
                $('#mis_personnel_breakdown').val(JSON.stringify(data));
            }
            $('#btn-add-personnel-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_personnel_breakdown').val() || '[]'); } catch(e){}
                data.push({ category: '', position: '', count: 0, notes: '' });
                $('#mis_personnel_breakdown').val(JSON.stringify(data));
                renderPersonnelRows();
            });
            $('#table-personnel-breakdown').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_personnel_breakdown').val() || '[]');
                data.splice(idx, 1);
                $('#mis_personnel_breakdown').val(JSON.stringify(data));
                renderPersonnelRows();
            });
            $('#table-personnel-breakdown').on('input change', 'input', savePersonnelRows);

            // Helper to render curriculum rows
            function renderCurriculumRows() {
                var data = [];
                try { data = JSON.parse($('#mis_curriculum_list').val() || '[]'); } catch(e){}
                var $tbody = $('#table-curriculum-list tbody').empty();
                data.forEach(function(row, idx) {
                    var tr = $('<tr>' +
                        '<td><select class="r-curr-lvl"><option value="ปวช." '+(row.level==='ปวช.'?'selected':'')+'>ปวช.</option><option value="ปวส." '+(row.level==='ปวส.'?'selected':'')+'>ปวส.</option></select></td>' +
                        '<td><input type="text" class="r-curr-dept" value="'+(row.department||'')+'" placeholder="สาขาวิชา"></td>' +
                        '<td><input type="text" class="r-curr-spec" value="'+(row.specialties||'')+'" placeholder="สาขางานที่เปิดสอน"></td>' +
                        '<td><input type="text" class="r-curr-mode" value="'+(row.study_mode||'')+'" placeholder="เช่น ปกติ / ทวิภาคี"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
            }
            function saveCurriculumRows() {
                var data = [];
                $('#table-curriculum-list tbody tr').each(function() {
                    data.push({
                        level: $(this).find('.r-curr-lvl').val(),
                        department: $(this).find('.r-curr-dept').val(),
                        specialties: $(this).find('.r-curr-spec').val(),
                        study_mode: $(this).find('.r-curr-mode').val()
                    });
                });
                $('#mis_curriculum_list').val(JSON.stringify(data));
            }
            $('#btn-add-curriculum-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_curriculum_list').val() || '[]'); } catch(e){}
                data.push({ level: 'ปวช.', department: '', specialties: '', study_mode: 'ปกติ และ ทวิภาคี' });
                $('#mis_curriculum_list').val(JSON.stringify(data));
                renderCurriculumRows();
            });
            $('#table-curriculum-list').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_curriculum_list').val() || '[]');
                data.splice(idx, 1);
                $('#mis_curriculum_list').val(JSON.stringify(data));
                renderCurriculumRows();
            });
            $('#table-curriculum-list').on('input change', 'input, select', saveCurriculumRows);

            // Helper to render admissions rows
            function renderAdmissionsRows() {
                var data = [];
                try { data = JSON.parse($('#mis_admissions_graduates').val() || '[]'); } catch(e){}
                var $tbody = $('#table-admissions-graduates tbody').empty();
                data.forEach(function(row, idx) {
                    var tr = $('<tr>' +
                        '<td><input type="text" class="r-adm-yr" value="'+(row.year||'')+'" placeholder="2568"></td>' +
                        '<td><input type="number" class="r-adm-inpvch" value="'+(row.intake_pvch||0)+'"></td>' +
                        '<td><input type="number" class="r-adm-inpvs" value="'+(row.intake_pvs||0)+'"></td>' +
                        '<td><input type="number" class="r-adm-intot" value="'+(row.intake_total||0)+'"></td>' +
                        '<td><input type="number" class="r-adm-grpvch" value="'+(row.grad_pvch||0)+'"></td>' +
                        '<td><input type="number" class="r-adm-grpvs" value="'+(row.grad_pvs||0)+'"></td>' +
                        '<td><input type="number" class="r-adm-grtot" value="'+(row.grad_total||0)+'"></td>' +
                        '<td><input type="text" class="r-adm-rate" value="'+(row.grad_rate||'')+'" placeholder="95.5%"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
            }
            function saveAdmissionsRows() {
                var data = [];
                $('#table-admissions-graduates tbody tr').each(function() {
                    data.push({
                        year: $(this).find('.r-adm-yr').val(),
                        intake_pvch: parseInt($(this).find('.r-adm-inpvch').val(), 10) || 0,
                        intake_pvs: parseInt($(this).find('.r-adm-inpvs').val(), 10) || 0,
                        intake_total: parseInt($(this).find('.r-adm-intot').val(), 10) || 0,
                        grad_pvch: parseInt($(this).find('.r-adm-grpvch').val(), 10) || 0,
                        grad_pvs: parseInt($(this).find('.r-adm-grpvs').val(), 10) || 0,
                        grad_total: parseInt($(this).find('.r-adm-grtot').val(), 10) || 0,
                        grad_rate: $(this).find('.r-adm-rate').val()
                    });
                });
                $('#mis_admissions_graduates').val(JSON.stringify(data));
            }
            $('#btn-add-admissions-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_admissions_graduates').val() || '[]'); } catch(e){}
                data.push({ year: '', intake_pvch: 0, intake_pvs: 0, intake_total: 0, grad_pvch: 0, grad_pvs: 0, grad_total: 0, grad_rate: '' });
                $('#mis_admissions_graduates').val(JSON.stringify(data));
                renderAdmissionsRows();
            });
            $('#table-admissions-graduates').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_admissions_graduates').val() || '[]');
                data.splice(idx, 1);
                $('#mis_admissions_graduates').val(JSON.stringify(data));
                renderAdmissionsRows();
            });
            $('#table-admissions-graduates').on('input change', 'input', saveAdmissionsRows);

            // Helper to render achievements rows
            function renderAchievementsRows() {
                var data = [];
                try { data = JSON.parse($('#mis_achievements_list').val() || '[]'); } catch(e){}
                var $tbody = $('#table-achievements-list tbody').empty();
                data.forEach(function(row, idx) {
                    var tr = $('<tr>' +
                        '<td><input type="text" class="r-ach-dept" value="'+(row.department||'')+'" placeholder="แผนกวิชา"></td>' +
                        '<td><input type="text" class="r-ach-gpax" value="'+(row.gpax||'')+'" placeholder="3.25"></td>' +
                        '<td><input type="text" class="r-ach-vnet" value="'+(row.vnet_score||'')+'" placeholder="78.5%"></td>' +
                        '<td><input type="text" class="r-ach-std" value="'+(row.standard_pass_rate||'')+'" placeholder="96.0%"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
            }
            function saveAchievementsRows() {
                var data = [];
                $('#table-achievements-list tbody tr').each(function() {
                    data.push({
                        department: $(this).find('.r-ach-dept').val(),
                        gpax: $(this).find('.r-ach-gpax').val(),
                        vnet_score: $(this).find('.r-ach-vnet').val(),
                        standard_pass_rate: $(this).find('.r-ach-std').val()
                    });
                });
                $('#mis_achievements_list').val(JSON.stringify(data));
            }
            $('#btn-add-achieve-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_achievements_list').val() || '[]'); } catch(e){}
                data.push({ department: '', gpax: '3.00', vnet_score: '75.0%', standard_pass_rate: '95.0%' });
                $('#mis_achievements_list').val(JSON.stringify(data));
                renderAchievementsRows();
            });
            $('#table-achievements-list').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_achievements_list').val() || '[]');
                data.splice(idx, 1);
                $('#mis_achievements_list').val(JSON.stringify(data));
                renderAchievementsRows();
            });
            $('#table-achievements-list').on('input change', 'input', saveAchievementsRows);

            // Helper to recalculate employment stats from count inputs
            function recalculateEmploymentStats() {
                var total = 0;
                var $rows = $('#table-employment-data tbody tr');
                
                $rows.each(function() {
                    var cnt = parseInt($(this).find('.r-emp-cnt').val(), 10) || 0;
                    total += cnt;
                });

                var fieldRate = 0;
                var otherRate = 0;
                var higherEdRate = 0;
                var freelanceRate = 0;
                var unemployedRate = 0;
                var successCount = 0;

                $rows.each(function() {
                    var $catInput = $(this).find('.r-emp-cat');
                    var cat = ($catInput.val() || '').trim();
                    var cnt = parseInt($(this).find('.r-emp-cnt').val(), 10) || 0;
                    var pct = 0;
                    if (total > 0) {
                        pct = (cnt / total) * 100;
                    }
                    var pctFormatted = total > 0 ? (pct.toFixed(1) + '%') : '';
                    $(this).find('.r-emp-pct').val(pctFormatted);

                    // Match category keywords to auto-sync top summary cards
                    if (cat.indexOf('ตรงสาขา') !== -1) {
                        fieldRate = pct;
                        successCount += cnt;
                    } else if (cat.indexOf('สาขาอื่น') !== -1 || cat.indexOf('ต่างสาขา') !== -1) {
                        otherRate = pct;
                        successCount += cnt;
                    } else if (cat.indexOf('ศึกษาต่อ') !== -1 || cat.indexOf('ปริญญาตรี') !== -1) {
                        higherEdRate = pct;
                        successCount += cnt;
                    } else if (cat.indexOf('อิสระ') !== -1 || cat.indexOf('ผู้ประกอบการ') !== -1 || cat.indexOf('Startup') !== -1) {
                        freelanceRate = pct;
                        successCount += cnt;
                    } else if (cat.indexOf('รอ') !== -1 || cat.indexOf('เกณฑ์ทหาร') !== -1 || cat.indexOf('อื่น') !== -1) {
                        unemployedRate = pct;
                    }
                });

                // Update table summary footer
                $('#emp-total-count').text(total.toLocaleString() + ' คน');
                $('#emp-total-pct').text(total > 0 ? '100.0%' : '0.0%');

                // Auto-sync top summary cards if total > 0
                if (total > 0) {
                    var totalSuccessRate = (successCount / total) * 100;
                    if (totalSuccessRate > 0) {
                        $('input[name="mis_employment_total_rate"]').val(totalSuccessRate.toFixed(1));
                    }
                    if (fieldRate > 0) $('input[name="mis_employed_field_rate"]').val(fieldRate.toFixed(1));
                    if (otherRate > 0) $('input[name="mis_employed_other_rate"]').val(otherRate.toFixed(1));
                    if (higherEdRate > 0) $('input[name="mis_higher_education_rate"]').val(higherEdRate.toFixed(1));
                    if (freelanceRate > 0) $('input[name="mis_freelance_business_rate"]').val(freelanceRate.toFixed(1));
                    if (unemployedRate > 0) $('input[name="mis_unemployed_or_other_rate"]').val(unemployedRate.toFixed(1));
                }
            }

            // Helper to render employment rows
            function renderEmploymentRows() {
                var data = [];
                try { data = JSON.parse($('#mis_employment_data').val() || '[]'); } catch(e){}
                var $tbody = $('#table-employment-data tbody').empty();
                data.forEach(function(row, idx) {
                    var tr = $('<tr>' +
                        '<td><input type="text" class="r-emp-cat" value="'+(row.category||'')+'" placeholder="กลุ่ม/ประเภท เช่น มีงานทำตรงสาขาวิชา"></td>' +
                        '<td><input type="number" class="r-emp-cnt" value="'+(row.count||0)+'" min="0" style="font-weight: bold; color: #0047BA;"></td>' +
                        '<td><input type="text" class="r-emp-pct" value="'+(row.percent||'')+'" placeholder="คำนวณอัตโนมัติ" readonly style="background: #f8fafc; font-weight: 600; color: #059669; cursor: default;"></td>' +
                        '<td><input type="text" class="r-emp-sal" value="'+(row.avg_salary||'')+'" placeholder="15,000 - 20,000"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
                recalculateEmploymentStats();
            }
            function saveEmploymentRows() {
                recalculateEmploymentStats();
                var data = [];
                $('#table-employment-data tbody tr').each(function() {
                    data.push({
                        category: $(this).find('.r-emp-cat').val(),
                        count: parseInt($(this).find('.r-emp-cnt').val(), 10) || 0,
                        percent: $(this).find('.r-emp-pct').val(),
                        avg_salary: $(this).find('.r-emp-sal').val()
                    });
                });
                $('#mis_employment_data').val(JSON.stringify(data));
            }
            $('#btn-add-employment-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_employment_data').val() || '[]'); } catch(e){}
                data.push({ category: '', count: 0, percent: '', avg_salary: '' });
                $('#mis_employment_data').val(JSON.stringify(data));
                renderEmploymentRows();
            });
            $('#table-employment-data').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_employment_data').val() || '[]');
                data.splice(idx, 1);
                $('#mis_employment_data').val(JSON.stringify(data));
                renderEmploymentRows();
            });
            $('#table-employment-data').on('input change', 'input', saveEmploymentRows);

            // Helper to render partner rows
            function renderPartnerRows() {
                var data = [];
                try { data = JSON.parse($('#mis_partner_enterprises').val() || '[]'); } catch(e){}
                var $tbody = $('#table-partner-enterprises tbody').empty();
                data.forEach(function(row, idx) {
                    var logoUrl = row.logo_url || '';
                    var previewStyle = logoUrl 
                        ? 'background-image: url(' + logoUrl + '); background-size: contain; background-repeat: no-repeat; background-position: center;' 
                        : 'background: #f1f5f9; display: flex; align-items: center; justify-content: center;';
                    var previewContent = logoUrl ? '' : '<span style="color:#94a3b8; font-size:10px;">ไม่มีรูป</span>';

                    var tr = $('<tr>' +
                        '<td style="text-align:center; vertical-align: top; padding: 6px;">' +
                            '<div class="part-logo-box" style="width: 44px; height: 44px; margin: 0 auto 4px; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; ' + previewStyle + '">' + previewContent + '</div>' +
                            '<input type="hidden" class="r-part-logo" value="' + (logoUrl) + '">' +
                            '<div style="display:flex; gap:2px; justify-content:center;">' +
                                '<button type="button" class="button button-small btn-upload-row-logo" title="เลือก/อัปโหลดโลโก้" style="padding: 0 4px; font-size: 11px;">เลือก</button>' +
                                '<button type="button" class="button button-small btn-clear-row-logo" title="ลบโลโก้" style="padding: 0 4px; font-size: 11px; ' + (logoUrl ? '' : 'display:none;') + '">✕</button>' +
                            '</div>' +
                        '</td>' +
                        '<td><input type="text" class="r-part-name" value="'+(row.name||'')+'" placeholder="ชื่อบริษัท/สถานประกอบการ"></td>' +
                        '<td><input type="text" class="r-part-ind" value="'+(row.industry||'')+'" placeholder="อุตสาหกรรม"></td>' +
                        '<td><input type="text" class="r-part-coop" value="'+(row.cooperation_type||'')+'" placeholder="ทวิภาคี / ฝึกงาน"></td>' +
                        '<td><input type="text" class="r-part-dept" value="'+(row.departments||'')+'" placeholder="สาขาวิชา"></td>' +
                        '<td style="text-align:center;"><button type="button" class="mis-btn-remove" data-idx="'+idx+'">ลบ</button></td>' +
                    '</tr>');
                    $tbody.append(tr);
                });
            }
            function savePartnerRows() {
                var data = [];
                $('#table-partner-enterprises tbody tr').each(function() {
                    data.push({
                        logo_url: $(this).find('.r-part-logo').val() || '',
                        name: $(this).find('.r-part-name').val(),
                        industry: $(this).find('.r-part-ind').val(),
                        cooperation_type: $(this).find('.r-part-coop').val(),
                        departments: $(this).find('.r-part-dept').val()
                    });
                });
                $('#mis_partner_enterprises').val(JSON.stringify(data));
            }
            $('#btn-add-partner-row').on('click', function() {
                var data = [];
                try { data = JSON.parse($('#mis_partner_enterprises').val() || '[]'); } catch(e){}
                data.push({ logo_url: '', name: '', industry: '', cooperation_type: 'ทวิภาคี (DVE)', departments: '' });
                $('#mis_partner_enterprises').val(JSON.stringify(data));
                renderPartnerRows();
            });
            $('#table-partner-enterprises').on('click', '.mis-btn-remove', function() {
                var idx = $(this).data('idx');
                var data = JSON.parse($('#mis_partner_enterprises').val() || '[]');
                data.splice(idx, 1);
                $('#mis_partner_enterprises').val(JSON.stringify(data));
                renderPartnerRows();
            });
            $('#table-partner-enterprises').on('input change', 'input', savePartnerRows);

            // Partner Logo Media Frame
            $('#table-partner-enterprises').on('click', '.btn-upload-row-logo', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $tr = $btn.closest('tr');
                var $input = $tr.find('.r-part-logo');
                var $box = $tr.find('.part-logo-box');
                var $clearBtn = $tr.find('.btn-clear-row-logo');

                var frame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดโลโก้สถานประกอบการ', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้รูปนี้เป็นโลโก้', 'project-core')); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    $input.val(url);
                    $box.css({
                        'background-image': 'url(' + url + ')',
                        'background-size': 'contain',
                        'background-repeat': 'no-repeat',
                        'background-position': 'center'
                    }).html('');
                    $clearBtn.show();
                    savePartnerRows();
                });

                frame.open();
            });

            $('#table-partner-enterprises').on('click', '.btn-clear-row-logo', function(e) {
                e.preventDefault();
                var $tr = $(this).closest('tr');
                $tr.find('.r-part-logo').val('');
                $tr.find('.part-logo-box').css({
                    'background-image': 'none',
                    'background': '#f1f5f9'
                }).html('<span style="color:#94a3b8; font-size:10px;">ไม่มีรูป</span>');
                $(this).hide();
                savePartnerRows();
            });

            // Initial renders
            renderStudentRows();
            renderPersonnelRows();
            renderCurriculumRows();
            renderAdmissionsRows();
            renderAchievementsRows();
            renderEmploymentRows();
            renderPartnerRows();

            // PDF Media Frame
            var misPdfFrame;
            $('#btn_upload_mis_pdf').on('click', function(e) {
                e.preventDefault();
                if (misPdfFrame) {
                    misPdfFrame.open();
                    return;
                }
                misPdfFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดไฟล์รายงานสารสนเทศทางการศึกษา (PDF)', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้ไฟล์นี้', 'project-core')); ?>' },
                    multiple: false
                });
                misPdfFrame.on('select', function() {
                    var attachment = misPdfFrame.state().get('selection').first().toJSON();
                    $('#mis_report_pdf_url').val(attachment.url);
                    $('#mis_report_pdf_id').val(attachment.id);
                    $('#mis_pdf_preview').show().find('a').attr('href', attachment.url);
                    $('#btn_clear_mis_pdf').show();
                });
                misPdfFrame.open();
            });

            $('#btn_clear_mis_pdf').on('click', function(e) {
                e.preventDefault();
                $('#mis_report_pdf_url').val('');
                $('#mis_report_pdf_id').val('');
                $('#mis_pdf_preview').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Render Download File Meta Box
     */
    public function render_download_meta_box($post) {
        wp_nonce_field('project_core_download_nonce', 'project_core_download_nonce_field');

        $source_type  = get_post_meta($post->ID, 'download_source_type', true) ?: 'file';
        $file_url     = get_post_meta($post->ID, 'download_file_url', true) ?: '';
        $file_id      = get_post_meta($post->ID, 'download_file_id', true) ?: '';
        $external_url = get_post_meta($post->ID, 'download_external_url', true) ?: '';
        $file_type    = get_post_meta($post->ID, 'download_file_type', true) ?: 'pdf';
        $file_size    = get_post_meta($post->ID, 'download_file_size', true) ?: '';
        $version      = get_post_meta($post->ID, 'download_version', true) ?: '';
        $description  = get_post_meta($post->ID, 'download_description', true) ?: '';
        $order        = get_post_meta($post->ID, 'download_order', true);
        if ($order === '') $order = $post->menu_order ?: 0;
        ?>
        <table class="form-table" style="margin-top: 0;">
            <!-- Source Type Selector -->
            <tr>
                <th scope="row"><label><?php _e('รูปแบบการแนบไฟล์', 'project-core'); ?></label></th>
                <td>
                    <fieldset>
                        <label style="margin-right: 20px; font-weight: 600;">
                            <input type="radio" name="download_source_type" value="file" <?php checked($source_type, 'file'); ?> class="download-source-radio" />
                            <span class="dashicons dashicons-upload" style="font-size: 16px; vertical-align: text-bottom;"></span> <?php _e('อัปโหลดไฟล์ (WordPress Media)', 'project-core'); ?>
                        </label>
                        <label style="font-weight: 600;">
                            <input type="radio" name="download_source_type" value="link" <?php checked($source_type, 'link'); ?> class="download-source-radio" />
                            <span class="dashicons dashicons-admin-links" style="font-size: 16px; vertical-align: text-bottom;"></span> <?php _e('ใส่ลิงก์ภายนอก (Google Drive / Canva / ลิงก์ตรง)', 'project-core'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <!-- File Upload Section -->
            <tr id="row_download_file" style="<?php echo ($source_type === 'link') ? 'display: none;' : ''; ?>">
                <th scope="row"><label for="download_file_url"><?php _e('ไฟล์เอกสารสำหรับดาวน์โหลด', 'project-core'); ?> <span style="color:#d63638;">*</span></label></th>
                <td>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; max-width: 650px;">
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                            <input type="text" id="download_file_url" name="download_file_url" value="<?php echo esc_attr($file_url); ?>" class="large-text" placeholder="https://... ลิงก์ไฟล์" style="font-family: monospace; font-size: 13px;" />
                            <input type="hidden" id="download_file_id" name="download_file_id" value="<?php echo esc_attr($file_id); ?>" />
                            <button type="button" class="button button-primary" id="btn_upload_download_file" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
                                <?php _e('เลือก / อัปโหลดไฟล์', 'project-core'); ?>
                            </button>
                            <button type="button" class="button" id="btn_clear_download_file" style="<?php echo empty($file_url) ? 'display: none;' : ''; ?> color: #b91c1c;">
                                <?php _e('ลบไฟล์', 'project-core'); ?>
                            </button>
                        </div>

                        <div id="download_file_preview" style="<?php echo empty($file_url) ? 'display: none;' : ''; ?>">
                            <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="button button-small" style="display: inline-flex; align-items: center; gap: 4px; color: #0047BA;">
                                <span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px; line-height: 14px;"></span>
                                <?php _e('ทดลองเปิดดูไฟล์', 'project-core'); ?>
                            </a>
                        </div>
                    </div>
                    <p class="description"><?php _e('กดปุ่ม "เลือก / อัปโหลดไฟล์" เพื่อเลือกไฟล์ PDF, Word, Excel, PowerPoint, ZIP ฯลฯ ระบบจะตรวจจับประเภทไฟล์และขนาดให้อัตโนมัติ', 'project-core'); ?></p>
                </td>
            </tr>

            <!-- External Link Section -->
            <tr id="row_download_link" style="<?php echo ($source_type === 'file') ? 'display: none;' : ''; ?>">
                <th scope="row"><label for="download_external_url"><?php _e('ลิงก์ไฟล์ภายนอก', 'project-core'); ?> <span style="color:#d63638;">*</span></label></th>
                <td>
                    <input type="url" id="download_external_url" name="download_external_url" value="<?php echo esc_attr($external_url); ?>" class="large-text" placeholder="https://drive.google.com/... หรือ https://www.canva.com/..." />
                    <p class="description"><?php _e('ใส่ลิงก์ไฟล์ที่แชร์จาก Google Drive, OneDrive, Canva หรือเว็บไซต์ภายนอก', 'project-core'); ?></p>
                </td>
            </tr>

            <!-- File Type Dropdown -->
            <tr>
                <th scope="row"><label for="download_file_type"><?php _e('ประเภทไฟล์ / นามสกุล', 'project-core'); ?> <span style="color:#d63638;">*</span></label></th>
                <td>
                    <select id="download_file_type" name="download_file_type" style="min-width: 180px;">
                        <option value="pdf" <?php selected($file_type, 'pdf'); ?>>PDF Document (.pdf)</option>
                        <option value="docx" <?php selected($file_type, 'docx'); ?>>Microsoft Word (.docx)</option>
                        <option value="doc" <?php selected($file_type, 'doc'); ?>>Microsoft Word (.doc)</option>
                        <option value="xlsx" <?php selected($file_type, 'xlsx'); ?>>Microsoft Excel (.xlsx)</option>
                        <option value="xls" <?php selected($file_type, 'xls'); ?>>Microsoft Excel (.xls)</option>
                        <option value="pptx" <?php selected($file_type, 'pptx'); ?>>Microsoft PowerPoint (.pptx)</option>
                        <option value="ppt" <?php selected($file_type, 'ppt'); ?>>Microsoft PowerPoint (.ppt)</option>
                        <option value="zip" <?php selected($file_type, 'zip'); ?>>ZIP Archive (.zip)</option>
                        <option value="rar" <?php selected($file_type, 'rar'); ?>>RAR Archive (.rar)</option>
                        <option value="image" <?php selected($file_type, 'image'); ?>>รูปภาพ (JPG, PNG)</option>
                        <option value="video" <?php selected($file_type, 'video'); ?>>วิดีโอ (MP4)</option>
                        <option value="link" <?php selected($file_type, 'link'); ?>>ลิงก์ภายนอก / เว็บไซต์</option>
                    </select>
                    <p class="description"><?php _e('ใช้สำหรับแสดงไอคอนประเภทไฟล์บนหน้าเว็บไซต์ (ระบบจะเปลี่ยนให้อัตโนมัติเมื่ออัปโหลดไฟล์)', 'project-core'); ?></p>
                </td>
            </tr>

            <!-- File Size -->
            <tr>
                <th scope="row"><label for="download_file_size"><?php _e('ขนาดไฟล์', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="download_file_size" name="download_file_size" value="<?php echo esc_attr($file_size); ?>" class="regular-text" placeholder="เช่น 2.4 MB, 450 KB" />
                    <p class="description"><?php _e('ขนาดไฟล์ที่จะแสดงให้ผู้ใช้งานทราบ (คำนวณให้อัตโนมัติเมื่ออัปโหลด)', 'project-core'); ?></p>
                </td>
            </tr>

            <!-- Version / Document ID -->
            <tr>
                <th scope="row"><label for="download_version"><?php _e('รหัสเอกสาร / เวอร์ชัน', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="download_version" name="download_version" value="<?php echo esc_attr($version); ?>" class="regular-text" placeholder="เช่น วทศ.01, ฉบับปรับปรุง 2568, v1.0" />
                    <p class="description"><?php _e('รหัสแบบฟอร์ม หรือเวอร์ชันเอกสารสำหรับอ้างอิง', 'project-core'); ?></p>
                </td>
            </tr>

            <!-- Description / Summary -->
            <tr>
                <th scope="row"><label for="download_description"><?php _e('คำอธิบายเอกสารอย่างย่อ', 'project-core'); ?></label></th>
                <td>
                    <textarea id="download_description" name="download_description" rows="3" class="large-text" placeholder="คำอธิบายสั้นๆ เกี่ยวกับวัตถุประสงค์และการนำเอกสารไปใช้งาน..."><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>

            <!-- Display Order -->
            <tr>
                <th scope="row"><label for="download_order"><?php _e('ลำดับการแสดงผล (Order)', 'project-core'); ?></label></th>
                <td>
                    <input type="number" id="download_order" name="download_order" value="<?php echo esc_attr($order); ?>" min="0" style="width: 100px;" />
                    <p class="description"><?php _e('ตัวเลขน้อยจะแสดงก่อน เช่น 1, 2, 3...', 'project-core'); ?></p>
                </td>
            </tr>
        </table>

        <div style="background: #eff6ff; border-left: 4px solid #0047BA; padding: 12px 16px; margin-top: 15px; border-radius: 0 6px 6px 0;">
            <p style="margin: 0; font-size: 13px; color: #1e3a8a;">
                <strong>💡 คำแนะนำ:</strong> กรุณาเลือก <strong>"หมวดหมู่ดาวน์โหลด"</strong> ที่กล่องด้านขวามือ เพื่อจัดกลุ่มเอกสารให้อยู่ในหมวดหมู่ที่ต้องการ เช่น แบบฟอร์มคำร้อง, ระเบียบข้อบังคับ หรือคู่มือนักศึกษา
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle Source Type
            $('.download-source-radio').on('change', function() {
                var val = $(this).val();
                if (val === 'file') {
                    $('#row_download_file').show();
                    $('#row_download_link').hide();
                } else {
                    $('#row_download_file').hide();
                    $('#row_download_link').show();
                    if (!$('#download_file_type').val() || $('#download_file_type').val() === 'pdf') {
                        $('#download_file_type').val('link');
                    }
                }
            });

            // Media Uploader
            var downloadFileFrame;
            $('#btn_upload_download_file').on('click', function(e) {
                e.preventDefault();
                if (downloadFileFrame) {
                    downloadFileFrame.open();
                    return;
                }
                downloadFileFrame = wp.media({
                    title: '<?php echo esc_js(__('เลือกหรืออัปโหลดไฟล์เอกสารดาวน์โหลด', 'project-core')); ?>',
                    button: { text: '<?php echo esc_js(__('ใช้ไฟล์นี้', 'project-core')); ?>' },
                    multiple: false
                });
                downloadFileFrame.on('select', function() {
                    var attachment = downloadFileFrame.state().get('selection').first().toJSON();
                    $('#download_file_url').val(attachment.url);
                    $('#download_file_id').val(attachment.id);

                    // Auto-detect extension
                    var filename = attachment.filename || attachment.url || '';
                    var ext = filename.split('.').pop().toLowerCase();
                    if (['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'zip', 'rar'].indexOf(ext) !== -1) {
                        $('#download_file_type').val(ext);
                    } else if (['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(ext) !== -1) {
                        $('#download_file_type').val('image');
                    } else if (['mp4', 'mov', 'webm'].indexOf(ext) !== -1) {
                        $('#download_file_type').val('video');
                    }

                    // Auto-detect file size
                    if (attachment.filesizeHumanReadable) {
                        $('#download_file_size').val(attachment.filesizeHumanReadable);
                    } else if (attachment.filesizeInBytes) {
                        var bytes = attachment.filesizeInBytes;
                        var sz = (bytes >= 1048576) ? (bytes / 1048576).toFixed(1) + ' MB' : (bytes / 1024).toFixed(0) + ' KB';
                        $('#download_file_size').val(sz);
                    }

                    $('#download_file_preview').show().find('a').attr('href', attachment.url);
                    $('#btn_clear_download_file').show();
                });
                downloadFileFrame.open();
            });

            $('#btn_clear_download_file').on('click', function(e) {
                e.preventDefault();
                $('#download_file_url').val('');
                $('#download_file_id').val('');
                $('#download_file_preview').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
    }
}
