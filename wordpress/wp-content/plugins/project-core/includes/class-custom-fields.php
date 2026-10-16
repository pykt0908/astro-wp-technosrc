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
    }

    /**
     * Enqueue WordPress Media Uploader for Banner admin
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        if ($hook === 'post-new.php' || $hook === 'post.php') {
            if (isset($post) && $post->post_type === 'banner') {
                wp_enqueue_media();
            }
        }
    }

    /**
     * Register meta fields for REST API access
     */
    public function register_meta_fields() {
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

        // Post SEO box
        add_meta_box(
            'project_core_post_seo',
            __('SEO & Social Metadata / ข้อมูล SEO', 'project-core'),
            array($this, 'render_post_seo_meta_box'),
            'post',
            'normal',
            'default'
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

        // Save Post SEO Meta
        if (isset($_POST['project_core_seo_nonce_field']) && wp_verify_nonce($_POST['project_core_seo_nonce_field'], 'project_core_seo_nonce')) {
            if (current_user_can('edit_post', $post_id)) {
                $meta_title = isset($_POST['seo_meta_title']) ? sanitize_text_field($_POST['seo_meta_title']) : '';
                $meta_desc  = isset($_POST['seo_meta_description']) ? sanitize_textarea_field($_POST['seo_meta_description']) : '';
                $og_image   = isset($_POST['seo_og_image_url']) ? esc_url_raw($_POST['seo_og_image_url']) : '';

                update_post_meta($post_id, 'seo_meta_title', $meta_title);
                update_post_meta($post_id, 'seo_meta_description', $meta_desc);
                update_post_meta($post_id, 'seo_og_image_url', $og_image);
            }
        }
    }
}
