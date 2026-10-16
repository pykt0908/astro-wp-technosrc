<?php
/**
 * Site Settings & Webhook Configuration Page
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Site_Settings {

    const OPTION_KEY = 'project_core_site_settings';

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Project Settings', 'project-core'),
            __('Site Settings', 'project-core'),
            'manage_options',
            'project-core-settings',
            array($this, 'render_settings_page'),
            'dashicons-admin-generic',
            30
        );
    }

    public function register_settings() {
        register_setting('project_core_settings_group', self::OPTION_KEY, array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    /**
     * Get clean site settings with default fallbacks
     */
    public static function get_settings() {
        $defaults = array(
            'site_name'         => get_bloginfo('name'),
            'site_tagline'      => get_bloginfo('description'),
            'logo_url'          => '',
            'favicon_url'       => '',
            'contact_email'     => 'contact@technosrc.com',
            'contact_phone'     => '02-123-4567',
            'contact_address'   => 'กรุงเทพมหานคร, ประเทศไทย',
            'office_hours'      => 'จันทร์ - ศุกร์ 09:00 - 18:00 น.',
            'social_facebook'   => '',
            'social_line'       => '',
            'social_linkedin'   => '',
            'social_github'     => '',
            'footer_copyright'  => sprintf('© %s Technosrc. สงวนลิขสิทธิ์ทุกประการ.', gmdate('Y')),
            // College VTR Video Section
            'vtr_enabled'            => 1,
            'vtr_badge'              => 'VTR แนะนำวิทยาลัย',
            'vtr_title'              => 'ก้าวสู่อนาคตวิชาชีพที่มั่นคง กับวิทยาลัยเทคโนโลยีศรีราชา',
            'vtr_subtitle'           => 'Sriracha Technological College Presentation Video',
            'vtr_description'        => 'สัมผัสบรรยากาศการเรียนการสอน หลักสูตรวิชาชีพที่ทันสมัย พร้อมเครื่องมือและนวัตกรรมระดับมาตรฐานสากล ร่วมผลิตกำลังคนคุณภาพสู่ภาคอุตสาหกรรม',
            'vtr_youtube_url'        => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'vtr_rating_score'       => '4.9/5',
            'vtr_rating_text'        => 'มาตรฐาน สอศ. Based on 1,500+ นักศึกษา',
            'vtr_primary_btn_text'   => 'สมัครเรียนออนไลน์',
            'vtr_primary_btn_url'    => '/apply',
            'vtr_secondary_btn_text' => 'ดูหลักสูตรที่เปิดสอน →',
            'vtr_secondary_btn_url'  => '/academics',
            'vtr_cover_image_url'    => '',
            // Webhook Configuration
            'webhook_url'       => '',
            'webhook_secret'    => '',
            'webhook_enabled'   => 0,
        );

        $saved = get_option(self::OPTION_KEY, array());
        return wp_parse_args($saved, $defaults);
    }

    public function sanitize_settings($input) {
        $output = array();

        $output['site_name']         = sanitize_text_field($input['site_name'] ?? '');
        $output['site_tagline']      = sanitize_text_field($input['site_tagline'] ?? '');
        $output['logo_url']          = esc_url_raw($input['logo_url'] ?? '');
        $output['favicon_url']       = esc_url_raw($input['favicon_url'] ?? '');
        $output['contact_email']     = sanitize_email($input['contact_email'] ?? '');
        $output['contact_phone']     = sanitize_text_field($input['contact_phone'] ?? '');
        $output['contact_address']   = sanitize_textarea_field($input['contact_address'] ?? '');
        $output['office_hours']      = sanitize_text_field($input['office_hours'] ?? '');
        $output['social_facebook']   = esc_url_raw($input['social_facebook'] ?? '');
        $output['social_line']       = sanitize_text_field($input['social_line'] ?? '');
        $output['social_linkedin']   = esc_url_raw($input['social_linkedin'] ?? '');
        $output['social_github']     = esc_url_raw($input['social_github'] ?? '');
        $output['footer_copyright']  = sanitize_text_field($input['footer_copyright'] ?? '');

        // College VTR Video
        $output['vtr_enabled']            = !empty($input['vtr_enabled']) ? 1 : 0;
        $output['vtr_badge']              = sanitize_text_field($input['vtr_badge'] ?? '');
        $output['vtr_title']              = sanitize_text_field($input['vtr_title'] ?? '');
        $output['vtr_subtitle']           = sanitize_text_field($input['vtr_subtitle'] ?? '');
        $output['vtr_description']        = sanitize_textarea_field($input['vtr_description'] ?? '');
        $output['vtr_youtube_url']        = esc_url_raw($input['vtr_youtube_url'] ?? '');
        $output['vtr_rating_score']       = sanitize_text_field($input['vtr_rating_score'] ?? '');
        $output['vtr_rating_text']        = sanitize_text_field($input['vtr_rating_text'] ?? '');
        $output['vtr_primary_btn_text']   = sanitize_text_field($input['vtr_primary_btn_text'] ?? '');
        $output['vtr_primary_btn_url']    = esc_url_raw($input['vtr_primary_btn_url'] ?? '');
        $output['vtr_secondary_btn_text'] = sanitize_text_field($input['vtr_secondary_btn_text'] ?? '');
        $output['vtr_secondary_btn_url']  = esc_url_raw($input['vtr_secondary_btn_url'] ?? '');
        $output['vtr_cover_image_url']    = esc_url_raw($input['vtr_cover_image_url'] ?? '');

        // Webhook
        $output['webhook_url']       = esc_url_raw($input['webhook_url'] ?? '');
        $output['webhook_secret']    = sanitize_text_field($input['webhook_secret'] ?? '');
        $output['webhook_enabled']   = !empty($input['webhook_enabled']) ? 1 : 0;

        // Trigger webhook if settings change and webhooks are active
        if ($output['webhook_enabled'] && !empty($output['webhook_url'])) {
            Project_Core_Webhook_Manager::trigger('site_settings_updated', array('timestamp' => time()));
        }

        return $output;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('Project Core — Site Settings & Headless Configuration', 'project-core'); ?></h1>
            <p><?php _e('Configure global frontend settings, company contact information, and automated build webhook integration.', 'project-core'); ?></p>

            <form method="post" action="options.php">
                <?php
                settings_fields('project_core_settings_group');
                ?>
                <h2 class="title"><?php _e('General Site Information / ข้อมูลเว็บไซต์', 'project-core'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="site_name"><?php _e('Website Name / ชื่อเว็บไซต์', 'project-core'); ?></label></th>
                        <td><input type="text" id="site_name" name="<?php echo self::OPTION_KEY; ?>[site_name]" value="<?php echo esc_attr($settings['site_name']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="logo_url"><?php _e('Logo URL', 'project-core'); ?></label></th>
                        <td><input type="url" id="logo_url" name="<?php echo self::OPTION_KEY; ?>[logo_url]" value="<?php echo esc_attr($settings['logo_url']); ?>" class="large-text" placeholder="https://example.com/logo.svg" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="favicon_url"><?php _e('Favicon URL', 'project-core'); ?></label></th>
                        <td><input type="url" id="favicon_url" name="<?php echo self::OPTION_KEY; ?>[favicon_url]" value="<?php echo esc_attr($settings['favicon_url']); ?>" class="large-text" placeholder="https://example.com/favicon.svg" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Contact Information / ข้อมูลติดต่อ', 'project-core'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="contact_email"><?php _e('Contact Email / อีเมลติดต่อ', 'project-core'); ?></label></th>
                        <td><input type="email" id="contact_email" name="<?php echo self::OPTION_KEY; ?>[contact_email]" value="<?php echo esc_attr($settings['contact_email']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contact_phone"><?php _e('Phone / โทรศัพท์', 'project-core'); ?></label></th>
                        <td><input type="text" id="contact_phone" name="<?php echo self::OPTION_KEY; ?>[contact_phone]" value="<?php echo esc_attr($settings['contact_phone']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contact_address"><?php _e('Address / ที่อยู่สำนักงาน', 'project-core'); ?></label></th>
                        <td><textarea id="contact_address" name="<?php echo self::OPTION_KEY; ?>[contact_address]" rows="2" class="large-text"><?php echo esc_textarea($settings['contact_address']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="office_hours"><?php _e('Office Hours / เวลาทำการ', 'project-core'); ?></label></th>
                        <td><input type="text" id="office_hours" name="<?php echo self::OPTION_KEY; ?>[office_hours]" value="<?php echo esc_attr($settings['office_hours']); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Social Links & Footer', 'project-core'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="social_facebook"><?php _e('Facebook URL', 'project-core'); ?></label></th>
                        <td><input type="url" id="social_facebook" name="<?php echo self::OPTION_KEY; ?>[social_facebook]" value="<?php echo esc_attr($settings['social_facebook']); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="social_line"><?php _e('Line ID / Line Official', 'project-core'); ?></label></th>
                        <td><input type="text" id="social_line" name="<?php echo self::OPTION_KEY; ?>[social_line]" value="<?php echo esc_attr($settings['social_line']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="social_linkedin"><?php _e('LinkedIn URL', 'project-core'); ?></label></th>
                        <td><input type="url" id="social_linkedin" name="<?php echo self::OPTION_KEY; ?>[social_linkedin]" value="<?php echo esc_attr($settings['social_linkedin']); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="social_github"><?php _e('GitHub URL', 'project-core'); ?></label></th>
                        <td><input type="url" id="social_github" name="<?php echo self::OPTION_KEY; ?>[social_github]" value="<?php echo esc_attr($settings['social_github']); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="footer_copyright"><?php _e('Footer Copyright Text', 'project-core'); ?></label></th>
                        <td><input type="text" id="footer_copyright" name="<?php echo self::OPTION_KEY; ?>[footer_copyright]" value="<?php echo esc_attr($settings['footer_copyright']); ?>" class="large-text" /></td>
                    </tr>
                </table>

                <h2 class="title" id="vtr-section" style="display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-video-alt3" style="font-size: 24px; color: #dc2626;"></span>
                    <?php _e('College VTR Video / วิดีโอแนะนำวิทยาลัย (YouTube Embed)', 'project-core'); ?>
                </h2>
                <p class="description" style="margin-bottom: 15px;"><?php _e('ตั้งค่าคลิปวิดีโอแนะนำวิทยาลัย (VTR) ที่จะแสดงต่อจากส่วนปฏิทินในหน้าแรกของเว็บไซต์ โดยสามารถคัดลอกลิงก์จาก YouTube มาใส่ได้โดยตรง', 'project-core'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('เปิดใช้งานส่วน VTR', 'project-core'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo self::OPTION_KEY; ?>[vtr_enabled]" value="1" <?php checked($settings['vtr_enabled'], 1); ?> />
                                <?php _e('แสดงส่วนวิดีโอแนะนำวิทยาลัย (VTR Section) บนหน้าแรก', 'project-core'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_youtube_url"><?php _e('ลิงก์ YouTube (URL หรือ Embed)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_youtube_url" name="<?php echo self::OPTION_KEY; ?>[vtr_youtube_url]" value="<?php echo esc_attr($settings['vtr_youtube_url']); ?>" class="large-text" placeholder="https://www.youtube.com/watch?v=... หรือ https://youtu.be/..." />
                            <p class="description"><?php _e('รองรับทุกลิงก์ของ YouTube เช่น <code>https://www.youtube.com/watch?v=XXXXX</code> หรือ <code>https://youtu.be/XXXXX</code> หรือ <code>https://www.youtube.com/shorts/XXXXX</code> (ระบบจะดึงรหัสวิดีโอมาแสดงอัตโนมัติ)', 'project-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_badge"><?php _e('ป้ายกำกับ (Badge Text)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_badge" name="<?php echo self::OPTION_KEY; ?>[vtr_badge]" value="<?php echo esc_attr($settings['vtr_badge']); ?>" class="regular-text" placeholder="เช่น VTR แนะนำวิทยาลัย" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_title"><?php _e('หัวข้อหลัก (Title)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_title" name="<?php echo self::OPTION_KEY; ?>[vtr_title]" value="<?php echo esc_attr($settings['vtr_title']); ?>" class="large-text" placeholder="เช่น วีดิทัศน์แนะนำวิทยาลัยเทคโนโลยีศรีราชา" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_subtitle"><?php _e('หัวข้อย่อย / ภาษาอังกฤษ (Subtitle)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_subtitle" name="<?php echo self::OPTION_KEY; ?>[vtr_subtitle]" value="<?php echo esc_attr($settings['vtr_subtitle']); ?>" class="large-text" placeholder="เช่น Sriracha Technological College Presentation Video" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_description"><?php _e('คำอธิบาย (Description)', 'project-core'); ?></label></th>
                        <td>
                            <textarea id="vtr_description" name="<?php echo self::OPTION_KEY; ?>[vtr_description]" rows="3" class="large-text" placeholder="ข้อความแนะนำวิทยาลัย สั้นๆ กระชับ..."><?php echo esc_textarea($settings['vtr_description']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_rating_score"><?php _e('คะแนนรีวิว / ความน่าเชื่อถือ (Rating Score)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_rating_score" name="<?php echo self::OPTION_KEY; ?>[vtr_rating_score]" value="<?php echo esc_attr($settings['vtr_rating_score']); ?>" class="small-text" placeholder="4.9/5" />
                            <input type="text" id="vtr_rating_text" name="<?php echo self::OPTION_KEY; ?>[vtr_rating_text]" value="<?php echo esc_attr($settings['vtr_rating_text']); ?>" class="regular-text" placeholder="เช่น มาตรฐาน สอศ. Based on 1,500+ นักศึกษา" style="margin-left: 8px;" />
                            <p class="description"><?php _e('คะแนนตัวเลข และข้อความแสดงความน่าเชื่อถือ/สถิติใต้หัวข้อ (เช่น ในตัวอย่าง Trustpilot Based on ...)', 'project-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_primary_btn_text"><?php _e('ปุ่มหลัก (Primary Button)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_primary_btn_text" name="<?php echo self::OPTION_KEY; ?>[vtr_primary_btn_text]" value="<?php echo esc_attr($settings['vtr_primary_btn_text']); ?>" class="regular-text" placeholder="เช่น สมัครเรียนออนไลน์ (หรือ Reserve Slot)" />
                            <input type="text" id="vtr_primary_btn_url" name="<?php echo self::OPTION_KEY; ?>[vtr_primary_btn_url]" value="<?php echo esc_attr($settings['vtr_primary_btn_url']); ?>" class="regular-text" placeholder="เช่น /apply หรือ https://..." style="margin-left: 8px;" />
                            <p class="description"><?php _e('ข้อความบนปุ่มสีทึบ และ ลิงก์ปลายทาง (หากเว้นว่างไว้จะไม่แสดงปุ่ม)', 'project-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_secondary_btn_text"><?php _e('ปุ่มรอง (Secondary Button)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" id="vtr_secondary_btn_text" name="<?php echo self::OPTION_KEY; ?>[vtr_secondary_btn_text]" value="<?php echo esc_attr($settings['vtr_secondary_btn_text']); ?>" class="regular-text" placeholder="เช่น ดูหลักสูตรที่เปิดสอน → (หรือ Case Studies →)" />
                            <input type="text" id="vtr_secondary_btn_url" name="<?php echo self::OPTION_KEY; ?>[vtr_secondary_btn_url]" value="<?php echo esc_attr($settings['vtr_secondary_btn_url']); ?>" class="regular-text" placeholder="เช่น /academics หรือ https://..." style="margin-left: 8px;" />
                            <p class="description"><?php _e('ข้อความบนปุ่มกรอบโปร่ง และ ลิงก์ปลายทาง', 'project-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtr_cover_image_url"><?php _e('ภาพหน้าปกวิดีโอ (Custom Cover Image URL)', 'project-core'); ?></label></th>
                        <td>
                            <input type="url" id="vtr_cover_image_url" name="<?php echo self::OPTION_KEY; ?>[vtr_cover_image_url]" value="<?php echo esc_attr($settings['vtr_cover_image_url']); ?>" class="large-text" placeholder="https://... หรือปล่อยว่างเพื่อใช้ภาพปกจาก YouTube อัตโนมัติ" />
                            <p class="description"><?php _e('หากปล่อยว่างไว้ ระบบจะใช้ภาพปก Thumbnail คุณภาพสูงจาก YouTube (maxresdefault) โดยอัตโนมัติ', 'project-core'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Automated Build Webhook / การเชื่อมต่อระบบบิลด์อัตโนมัติ', 'project-core'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Webhooks', 'project-core'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo self::OPTION_KEY; ?>[webhook_enabled]" value="1" <?php checked($settings['webhook_enabled'], 1); ?> />
                                <?php _e('Trigger webhook build on content publish, update, trash, and settings changes', 'project-core'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="webhook_url"><?php _e('Webhook Relay URL', 'project-core'); ?></label></th>
                        <td>
                            <input type="url" id="webhook_url" name="<?php echo self::OPTION_KEY; ?>[webhook_url]" value="<?php echo esc_attr($settings['webhook_url']); ?>" class="large-text" placeholder="https://relay.example.com/api/webhook" />
                            <p class="description"><?php _e('Endpoint that verifies HMAC signature and calls GitHub repository_dispatch.', 'project-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="webhook_secret"><?php _e('HMAC Secret Key', 'project-core'); ?></label></th>
                        <td>
                            <input type="password" id="webhook_secret" name="<?php echo self::OPTION_KEY; ?>[webhook_secret]" value="<?php echo esc_attr($settings['webhook_secret']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Shared secret used to calculate X-Hub-Signature-256 header.', 'project-core'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings / บันทึกการตั้งค่า', 'project-core')); ?>
            </form>
        </div>
        <?php
    }
}
