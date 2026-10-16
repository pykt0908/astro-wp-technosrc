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

        $target_url       = get_post_meta($post->ID, 'banner_target_url', true);
        $mobile_image_url = get_post_meta($post->ID, 'banner_mobile_image_url', true);
        $is_active        = get_post_meta($post->ID, 'banner_is_active', true);
        if ($is_active === '') {
            $is_active = '1';
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="banner_target_url"><?php _e('Target Link URL / ลิงก์ปลายทาง', 'project-core'); ?></label></th>
                <td>
                    <input type="url" id="banner_target_url" name="banner_target_url" value="<?php echo esc_attr($target_url); ?>" class="regular-text" placeholder="https://example.com/promo" />
                    <p class="description"><?php _e('URL to navigate when clicked. Leave empty if banner is not clickable.', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="banner_mobile_image_url"><?php _e('Mobile Image URL / รูปภาพสำหรับมือถือ', 'project-core'); ?></label></th>
                <td>
                    <input type="url" id="banner_mobile_image_url" name="banner_mobile_image_url" value="<?php echo esc_attr($mobile_image_url); ?>" class="regular-text" placeholder="https://..." />
                    <p class="description"><?php _e('Optional mobile crop image URL (Desktop image uses Featured Image).', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="banner_is_active"><?php _e('Active Status / สถานะเปิดใช้งาน', 'project-core'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="banner_is_active" name="banner_is_active" value="1" <?php checked($is_active, '1'); ?> />
                        <?php _e('Enable banner on frontend display', 'project-core'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Post SEO Meta Box
     */
    public function render_post_seo_meta_box($post) {
        wp_nonce_field('project_core_seo_nonce', 'project_core_seo_nonce_field');

        $meta_title = get_post_meta($post->ID, 'seo_meta_title', true);
        $meta_desc  = get_post_meta($post->ID, 'seo_meta_description', true);
        $og_image   = get_post_meta($post->ID, 'seo_og_image_url', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="seo_meta_title"><?php _e('SEO Title / หัวข้อ SEO', 'project-core'); ?></label></th>
                <td>
                    <input type="text" id="seo_meta_title" name="seo_meta_title" value="<?php echo esc_attr($meta_title); ?>" class="large-text" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                    <p class="description"><?php _e('Custom title tag. If left blank, post title will be used.', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="seo_meta_description"><?php _e('SEO Description / คำอธิบาย SEO', 'project-core'); ?></label></th>
                <td>
                    <textarea id="seo_meta_description" name="seo_meta_description" rows="3" class="large-text"><?php echo esc_textarea($meta_desc); ?></textarea>
                    <p class="description"><?php _e('Custom meta description for search engines and social sharing.', 'project-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="seo_og_image_url"><?php _e('Custom OG Image URL', 'project-core'); ?></label></th>
                <td>
                    <input type="url" id="seo_og_image_url" name="seo_og_image_url" value="<?php echo esc_attr($og_image); ?>" class="large-text" />
                    <p class="description"><?php _e('Optional custom Open Graph image URL. Defaults to Featured Image.', 'project-core'); ?></p>
                </td>
            </tr>
        </table>
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
                $target_url       = isset($_POST['banner_target_url']) ? esc_url_raw($_POST['banner_target_url']) : '';
                $mobile_image_url = isset($_POST['banner_mobile_image_url']) ? esc_url_raw($_POST['banner_mobile_image_url']) : '';
                $is_active        = isset($_POST['banner_is_active']) ? '1' : '0';

                update_post_meta($post_id, 'banner_target_url', $target_url);
                update_post_meta($post_id, 'banner_mobile_image_url', $mobile_image_url);
                update_post_meta($post_id, 'banner_is_active', $is_active);
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
