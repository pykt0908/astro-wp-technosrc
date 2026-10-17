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
    }

    /**
     * Enqueue WordPress Media Uploader for Banner and Post admin
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        if ($hook === 'post-new.php' || $hook === 'post.php') {
            if (isset($post) && in_array($post->post_type, array('banner', 'post', 'college_event', 'curriculum'), true)) {
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
            'curriculum_degree_en'       => 'string',
            'curriculum_duration'        => 'string',
            'curriculum_requirements'    => 'string',
            'curriculum_badge'           => 'string',
            'curriculum_tuition_fee'     => 'string',
            'curriculum_highlights'      => 'string',
            'curriculum_careers'         => 'string',
            'curriculum_study_plan_url'  => 'string',
        );

        foreach ($curriculum_fields as $meta_key => $type) {
            register_post_meta('curriculum', $meta_key, array(
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

        // Post Photo Gallery Box (มีที่ให้ใส่ album ภาพเพิ่มเติม)
        add_meta_box(
            'project_core_post_gallery',
            __('อัลบั้มภาพกิจกรรม / Photo Album Gallery', 'project-core'),
            array($this, 'render_post_gallery_meta_box'),
            'post',
            'normal',
            'high'
        );

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

                    <button type="button" class="button button-primary button-large" id="btn_upload_desktop_banner" style="display: inline-flex; align-items: center; gap: 4px;">
                        <span class="dashicons dashicons-upload" style="margin-top: -1px;"></span>
                        <?php _e('อัปโหลดภาพแบนเนอร์ (1709x650)', 'project-core'); ?>
                    </button>

                    <button type="button" class="button button-secondary button-large" id="btn_remove_desktop_banner" style="<?php echo empty($desktop_image_url) ? 'display: none;' : 'display: inline-flex;'; ?> align-items: center; gap: 4px; color: #b32d2e;">
                        <span class="dashicons dashicons-trash" style="margin-top: -1px;"></span>
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

                    <button type="button" class="button button-secondary" id="btn_upload_mobile_banner" style="display: inline-flex; align-items: center; gap: 4px;">
                        <span class="dashicons dashicons-upload" style="margin-top: -1px;"></span>
                        <?php _e('อัปโหลดภาพเฉพาะมือถือ', 'project-core'); ?>
                    </button>

                    <button type="button" class="button button-secondary" id="btn_remove_mobile_banner" style="<?php echo empty($mobile_image_url) ? 'display: none;' : 'display: inline-flex;'; ?> align-items: center; gap: 4px; color: #b32d2e;">
                        <span class="dashicons dashicons-trash" style="margin-top: -1px;"></span>
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
                <button type="button" class="button button-primary button-large" id="btn_upload_gallery_images" style="display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-images-alt2" style="margin-top: -1px;"></span>
                    <?php _e('เพิ่มรูปภาพเข้าอัลบั้ม (เลือกหลายรูปได้)', 'project-core'); ?>
                </button>
                <button type="button" class="button button-secondary" id="btn_clear_gallery" style="<?php echo empty($gallery_ids) ? 'display: none;' : 'display: inline-flex;'; ?> align-items: center; gap: 4px; color: #b32d2e;">
                    <span class="dashicons dashicons-trash" style="margin-top: -1px;"></span>
                    <?php _e('ล้างอัลบั้มทั้งหมด', 'project-core'); ?>
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
                $keys = array(
                    'curriculum_degree_th'       => 'sanitize_text_field',
                    'curriculum_degree_en'       => 'sanitize_text_field',
                    'curriculum_duration'        => 'sanitize_text_field',
                    'curriculum_requirements'    => 'sanitize_text_field',
                    'curriculum_badge'           => 'sanitize_text_field',
                    'curriculum_tuition_fee'     => 'sanitize_text_field',
                    'curriculum_highlights'      => 'sanitize_textarea_field',
                    'curriculum_careers'         => 'sanitize_textarea_field',
                    'curriculum_study_plan_url'  => 'esc_url_raw',
                );

                foreach ($keys as $key => $sanitizer) {
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, call_user_func($sanitizer, $_POST[$key]));
                    }
                }
            }
        }
    }

    /**
     * Render Curriculum Meta Box
     */
    public function render_curriculum_meta_box($post) {
        wp_nonce_field('project_core_curriculum_nonce', 'project_core_curriculum_nonce_field');

        $degree_th       = get_post_meta($post->ID, 'curriculum_degree_th', true) ?: '';
        $degree_en       = get_post_meta($post->ID, 'curriculum_degree_en', true) ?: '';
        $duration        = get_post_meta($post->ID, 'curriculum_duration', true) ?: '';
        $requirements    = get_post_meta($post->ID, 'curriculum_requirements', true) ?: '';
        $badge           = get_post_meta($post->ID, 'curriculum_badge', true) ?: '';
        $tuition_fee     = get_post_meta($post->ID, 'curriculum_tuition_fee', true) ?: '';
        $highlights      = get_post_meta($post->ID, 'curriculum_highlights', true) ?: '';
        $careers         = get_post_meta($post->ID, 'curriculum_careers', true) ?: '';
        $study_plan_url  = get_post_meta($post->ID, 'curriculum_study_plan_url', true) ?: '';
        ?>
        <table class="form-table" style="margin-top: 0;">
            <tr>
                <th scope="row"><label for="curriculum_degree_th"><?php _e('วุฒิการศึกษาที่ได้รับ (ภาษาไทย)', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_degree_th" name="curriculum_degree_th" value="<?php echo esc_attr($degree_th); ?>" class="large-text" placeholder="เช่น ประกาศนียบัตรวิชาชีพ (ปวช.) สาขาวิชาช่างยนต์" />
                    <p class="description"><?php _e('ระบุชื่อวุฒิการศึกษาเต็ม เช่น ประกาศนียบัตรวิชาชีพ (ปวช.) / ประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.)', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_degree_en"><?php _e('วุฒิการศึกษา (ภาษาอังกฤษ)', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_degree_en" name="curriculum_degree_en" value="<?php echo esc_attr($degree_en); ?>" class="large-text" placeholder="เช่น Vocational Certificate in Automotive Technology" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_duration"><?php _e('ระยะเวลาการศึกษา', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_duration" name="curriculum_duration" value="<?php echo esc_attr($duration); ?>" style="width: 200px;" placeholder="เช่น 3 ปี / 2 ปี" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="curriculum_requirements"><?php _e('คุณสมบัติผู้สมัคร', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="curriculum_requirements" name="curriculum_requirements" value="<?php echo esc_attr($requirements); ?>" class="large-text" placeholder="เช่น สำเร็จการศึกษาระดับ ม.3 หรือเทียบเท่า / สำเร็จการศึกษา ปวช. หรือ ม.6" />
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
                <th scope="row"><label for="curriculum_study_plan_url"><?php _e('ไฟล์แผนการเรียน (PDF)', 'project-core'); ?></label></th>
                <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" id="curriculum_study_plan_url" name="curriculum_study_plan_url" value="<?php echo esc_attr($study_plan_url); ?>" class="large-text" placeholder="https://... ลิงก์ดาวน์โหลดแผนการเรียน PDF" />
                        <button type="button" class="button" id="btn_upload_curriculum_pdf"><?php _e('เลือกไฟล์สื่อ', 'project-core'); ?></button>
                    </div>
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
}
