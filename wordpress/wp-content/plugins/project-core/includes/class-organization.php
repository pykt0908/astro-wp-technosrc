<?php
/**
 * Organization Structure Engine for Headless WordPress
 * Handles Organization Units, Positions, Personnel Relations, Settings, and REST API.
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Organization {

    const SETTINGS_OPTION = 'project_core_org_settings';

    public function init() {
        // Register Post Types
        add_action('init', array($this, 'register_post_types'));

        // Admin Menu
        add_action('admin_menu', array($this, 'register_admin_menus'));

        // Meta Boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_org_unit', array($this, 'save_org_unit_meta'), 10, 2);
        add_action('save_post_org_position', array($this, 'save_org_position_meta'), 10, 2);

        // Admin Columns for Org Unit
        add_filter('manage_org_unit_posts_columns', array($this, 'set_org_unit_columns'));
        add_action('manage_org_unit_posts_custom_column', array($this, 'render_org_unit_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_org_unit_sort_order'));

        // Admin Columns for Org Position
        add_filter('manage_org_position_posts_columns', array($this, 'set_org_position_columns'));
        add_action('manage_org_position_posts_custom_column', array($this, 'render_org_position_columns'), 10, 2);
        add_action('pre_get_posts', array($this, 'admin_org_position_sort_order'));

        // REST API Routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Admin Seed Action
        add_action('admin_post_project_core_seed_organization', array($this, 'handle_seed_action'));

        // Auto-seed if empty on first access
        add_action('admin_init', array($this, 'maybe_auto_seed'));
    }

    /**
     * Register Custom Post Types for Organization
     */
    public function register_post_types() {
        // 1. หน่วยงาน (Org Unit: ฝ่าย / งาน)
        $unit_labels = array(
            'name'               => __('หน่วยงานและฝ่ายงาน', 'project-core'),
            'singular_name'      => __('หน่วยงาน', 'project-core'),
            'menu_name'          => __('หน่วยงาน (ฝ่าย/งาน)', 'project-core'),
            'add_new'            => __('เพิ่มหน่วยงาน', 'project-core'),
            'add_new_item'       => __('เพิ่มหน่วยงานใหม่', 'project-core'),
            'edit_item'          => __('แก้ไขหน่วยงาน', 'project-core'),
            'new_item'           => __('หน่วยงานใหม่', 'project-core'),
            'view_item'          => __('ดูหน่วยงาน', 'project-core'),
            'all_items'          => __('หน่วยงานทั้งหมด', 'project-core'),
            'search_items'       => __('ค้นหาหน่วยงาน', 'project-core'),
            'not_found'          => __('ไม่พบหน่วยงาน', 'project-core'),
            'not_found_in_trash' => __('ไม่พบหน่วยงานในถังขยะ', 'project-core'),
        );

        register_post_type('org_unit', array(
            'labels'             => $unit_labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'project-core-organization',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array('title', 'editor', 'page-attributes'),
            'show_in_rest'       => false,
        ));

        // 2. ตำแหน่งในโครงสร้าง (Org Position)
        $pos_labels = array(
            'name'               => __('ตำแหน่งในโครงสร้าง', 'project-core'),
            'singular_name'      => __('ตำแหน่งโครงสร้าง', 'project-core'),
            'menu_name'          => __('ตำแหน่งในโครงสร้าง', 'project-core'),
            'add_new'            => __('เพิ่มตำแหน่ง', 'project-core'),
            'add_new_item'       => __('เพิ่มตำแหน่งใหม่', 'project-core'),
            'edit_item'          => __('แก้ไขตำแหน่ง', 'project-core'),
            'new_item'           => __('ตำแหน่งใหม่', 'project-core'),
            'view_item'          => __('ดูตำแหน่ง', 'project-core'),
            'all_items'          => __('ตำแหน่งทั้งหมด', 'project-core'),
            'search_items'       => __('ค้นหาตำแหน่ง', 'project-core'),
            'not_found'          => __('ไม่พบตำแหน่ง', 'project-core'),
            'not_found_in_trash' => __('ไม่พบตำแหน่งในถังขยะ', 'project-core'),
        );

        register_post_type('org_position', array(
            'labels'             => $pos_labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'project-core-organization',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array('title', 'page-attributes'),
            'show_in_rest'       => false,
        ));
    }

    /**
     * Register Admin Menus
     */
    public function register_admin_menus() {
        add_menu_page(
            __('โครงสร้างการบริหาร', 'project-core'),
            __('โครงสร้างการบริหาร', 'project-core'),
            'manage_options',
            'project-core-organization',
            array($this, 'render_overview_page'),
            'dashicons-networking',
            24
        );

        add_submenu_page(
            'project-core-organization',
            __('ผังและภาพรวมโครงสร้าง', 'project-core'),
            __('ผังและภาพรวมโครงสร้าง', 'project-core'),
            'manage_options',
            'project-core-organization',
            array($this, 'render_overview_page')
        );

        add_submenu_page(
            'project-core-organization',
            __('ตำแหน่งในโครงสร้าง', 'project-core'),
            __('ตำแหน่งในโครงสร้าง', 'project-core'),
            'manage_options',
            'edit.php?post_type=org_position'
        );

        add_submenu_page(
            'project-core-organization',
            __('หน่วยงาน (ฝ่าย/งาน)', 'project-core'),
            __('หน่วยงาน (ฝ่าย/งาน)', 'project-core'),
            'manage_options',
            'edit.php?post_type=org_unit'
        );

        add_submenu_page(
            'project-core-organization',
            __('ตั้งค่าหน้าโครงสร้าง', 'project-core'),
            __('ตั้งค่าหน้าโครงสร้าง', 'project-core'),
            'manage_options',
            'project-core-org-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        // Meta Box for Org Unit
        add_meta_box(
            'project_core_org_unit_details',
            __('ข้อมูลหน่วยงานและสังกัด / Department Details', 'project-core'),
            array($this, 'render_org_unit_meta_box'),
            'org_unit',
            'normal',
            'high'
        );

        // Meta Box for Org Position
        add_meta_box(
            'project_core_org_position_details',
            __('ข้อมูลตำแหน่งและผู้ดำรงตำแหน่ง / Position & Personnel', 'project-core'),
            array($this, 'render_org_position_meta_box'),
            'org_position',
            'normal',
            'high'
        );
    }

    /**
     * Render Org Unit Meta Box
     */
    public function render_org_unit_meta_box($post) {
        wp_nonce_field('project_core_org_unit_nonce', 'project_core_org_unit_nonce_field');

        $unit_code  = get_post_meta($post->ID, '_org_unit_code', true);
        $unit_type  = get_post_meta($post->ID, '_org_unit_type', true) ?: 'job';
        $parent_id  = get_post_meta($post->ID, '_org_unit_parent', true);
        $unit_order = get_post_meta($post->ID, '_org_unit_order', true) ?: 0;
        $unit_color = get_post_meta($post->ID, '_org_unit_color', true) ?: '#278bb4';
        $unit_desc  = get_post_meta($post->ID, '_org_unit_desc', true);

        // Get all divisions for parent dropdown
        $divisions = get_posts(array(
            'post_type'      => 'org_unit',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_key'       => '_org_unit_type',
            'meta_value'     => 'division',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="org_unit_type"><?php _e('ประเภทหน่วยงาน', 'project-core'); ?></label></th>
                <td>
                    <select name="org_unit_type" id="org_unit_type" class="regular-text" style="max-width:300px;">
                        <option value="division" <?php selected($unit_type, 'division'); ?>><?php _e('ฝ่าย (Division - ผู้บริหารระดับฝ่าย)', 'project-core'); ?></option>
                        <option value="job" <?php selected($unit_type, 'job'); ?>><?php _e('งานในสังกัด (Department / Job Unit)', 'project-core'); ?></option>
                    </select>
                    <p class="description"><?php _e('ฝ่าย = 4 ฝ่ายหลักของวิทยาลัย, งาน = งานย่อยที่สังกัดอยู่ในแต่ละฝ่าย', 'project-core'); ?></p>
                </td>
            </tr>

            <tr id="parent_unit_row" style="<?php echo ($unit_type === 'division') ? 'display:none;' : ''; ?>">
                <th scope="row"><label for="org_unit_parent"><?php _e('สังกัดฝ่าย (หน่วยงานแม่)', 'project-core'); ?></label></th>
                <td>
                    <select name="org_unit_parent" id="org_unit_parent" class="regular-text" style="max-width:400px;">
                        <option value="0"><?php _e('-- เลือกฝ่ายที่สังกัด --', 'project-core'); ?></option>
                        <?php foreach ($divisions as $div): ?>
                            <?php if ($div->ID !== $post->ID): ?>
                                <option value="<?php echo esc_attr($div->ID); ?>" <?php selected($parent_id, $div->ID); ?>>
                                    <?php echo esc_html($div->post_title); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_unit_code"><?php _e('รหัสหน่วยงาน (Code/Slug)', 'project-core'); ?></label></th>
                <td>
                    <input type="text" name="org_unit_code" id="org_unit_code" value="<?php echo esc_attr($unit_code); ?>" class="regular-text" placeholder="e.g. academic, student-affairs">
                    <p class="description"><?php _e('รหัสอ้างอิงภาษาอังกฤษที่ไม่เปลี่ยนตามชื่อภาษาไทย', 'project-core'); ?></p>
                </td>
            </tr>

            <tr id="color_row" style="<?php echo ($unit_type === 'job') ? 'display:none;' : ''; ?>">
                <th scope="row"><label for="org_unit_color"><?php _e('สีประจำฝ่าย (Accent Color)', 'project-core'); ?></label></th>
                <td>
                    <input type="color" name="org_unit_color" id="org_unit_color" value="<?php echo esc_attr($unit_color); ?>" style="width:60px; height:34px; padding:2px; vertical-align:middle;">
                    <input type="text" name="org_unit_color_text" id="org_unit_color_text" value="<?php echo esc_attr($unit_color); ?>" class="small-text" style="font-family:monospace; margin-left:8px;">
                    <p class="description"><?php _e('สีใช้สำหรับเน้นหัวข้อ แถบสี และปุ่มประจำฝ่ายในหน้าเว็บไซต์', 'project-core'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_unit_order"><?php _e('ลำดับการแสดงผล', 'project-core'); ?></label></th>
                <td>
                    <input type="number" name="org_unit_order" id="org_unit_order" value="<?php echo esc_attr($unit_order); ?>" class="small-text" min="0">
                    <p class="description"><?php _e('ตัวเลขน้อยจะแสดงก่อน เช่น 1, 2, 3...', 'project-core'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_unit_desc"><?php _e('หน้าที่ความรับผิดชอบโดยย่อ', 'project-core'); ?></label></th>
                <td>
                    <textarea name="org_unit_desc" id="org_unit_desc" rows="3" class="large-text" placeholder="ระบุภารกิจหรือหน้าที่โดยสังเขป (ถ้ามี)"><?php echo esc_textarea($unit_desc); ?></textarea>
                </td>
            </tr>
        </table>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var typeSelect = document.getElementById('org_unit_type');
            var parentRow = document.getElementById('parent_unit_row');
            var colorRow = document.getElementById('color_row');
            var colorPicker = document.getElementById('org_unit_color');
            var colorText = document.getElementById('org_unit_color_text');

            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    if (this.value === 'division') {
                        parentRow.style.display = 'none';
                        colorRow.style.display = '';
                    } else {
                        parentRow.style.display = '';
                        colorRow.style.display = 'none';
                    }
                });
            }

            if (colorPicker && colorText) {
                colorPicker.addEventListener('input', function() { colorText.value = this.value; });
                colorText.addEventListener('input', function() { colorPicker.value = this.value; });
            }
        });
        </script>
        <?php
    }

    /**
     * Render Org Position Meta Box
     */
    public function render_org_position_meta_box($post) {
        wp_nonce_field('project_core_org_position_nonce', 'project_core_org_position_nonce_field');

        $pos_type   = get_post_meta($post->ID, '_org_position_type', true) ?: 'job_head';
        $person_id  = get_post_meta($post->ID, '_org_personnel_id', true);
        $unit_id    = get_post_meta($post->ID, '_org_unit_id', true);
        $parent_pos = get_post_meta($post->ID, '_org_parent_position_id', true);
        $pos_order  = get_post_meta($post->ID, '_org_position_order', true) ?: 0;
        $pos_status = get_post_meta($post->ID, '_org_position_status', true) ?: 'active';
        $pos_remark = get_post_meta($post->ID, '_org_position_remark', true);
        $pos_sub    = get_post_meta($post->ID, '_org_position_sub', true);

        // Fetch all personnel
        $personnel_list = get_posts(array(
            'post_type'      => 'personnel',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        // Fetch all units
        $units_list = get_posts(array(
            'post_type'      => 'org_unit',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ));

        // Fetch all other positions (for supervisor dropdown, excluding self)
        $positions_list = get_posts(array(
            'post_type'      => 'org_position',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'exclude'        => array($post->ID),
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="org_position_type"><?php _e('ประเภทตำแหน่ง', 'project-core'); ?></label></th>
                <td>
                    <select name="org_position_type" id="org_position_type" class="regular-text">
                        <option value="executive" <?php selected($pos_type, 'executive'); ?>><?php _e('ผู้บริหารระดับนโยบาย (ผู้รับใบอนุญาต / ผู้จัดการ / ผู้อำนวยการ)', 'project-core'); ?></option>
                        <option value="committee" <?php selected($pos_type, 'committee'); ?>><?php _e('คณะกรรมการสถานศึกษา (วางด้านข้างผู้อำนวยการ)', 'project-core'); ?></option>
                        <option value="division_head" <?php selected($pos_type, 'division_head'); ?>><?php _e('ผู้บริหารระดับฝ่าย (ผู้บริหาร 4 ฝ่าย)', 'project-core'); ?></option>
                        <option value="job_head" <?php selected($pos_type, 'job_head'); ?>><?php _e('ผู้รับผิดชอบงานในสังกัด (หัวหน้างาน)', 'project-core'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_personnel_id"><?php _e('ผู้ดำรงตำแหน่ง (บุคลากร)', 'project-core'); ?></label></th>
                <td>
                    <select name="org_personnel_id" id="org_personnel_id" class="regular-text" style="max-width:450px;">
                        <option value="0"><?php _e('-- ยังไม่มีผู้ดำรงตำแหน่ง / ตำแหน่งว่าง (Vacant) --', 'project-core'); ?></option>
                        <?php foreach ($personnel_list as $person): ?>
                            <option value="<?php echo esc_attr($person->ID); ?>" <?php selected($person_id, $person->ID); ?>>
                                <?php echo esc_html($person->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('อ้างอิงข้อมูลบุคลากรจากฐานข้อมูล ทำให้คนเดียวสามารถรับผิดชอบหลายตำแหน่งได้โดยไม่ต้องสร้างข้อมูลซ้ำ', 'project-core'); ?>
                        | <a href="<?php echo admin_url('post-new.php?post_type=personnel'); ?>" target="_blank"><?php _e('+ เพิ่มบุคลากรใหม่', 'project-core'); ?></a>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_unit_id"><?php _e('หน่วยงานที่สังกัด (ถ้ามี)', 'project-core'); ?></label></th>
                <td>
                    <select name="org_unit_id" id="org_unit_id" class="regular-text" style="max-width:450px;">
                        <option value="0"><?php _e('-- ไม่ระบุ / ระดับวิทยาลัย --', 'project-core'); ?></option>
                        <?php foreach ($units_list as $unit): ?>
                            <?php 
                            $u_type = get_post_meta($unit->ID, '_org_unit_type', true);
                            $type_label = ($u_type === 'division') ? '【ฝ่าย】' : '• งาน:';
                            ?>
                            <option value="<?php echo esc_attr($unit->ID); ?>" <?php selected($unit_id, $unit->ID); ?>>
                                <?php echo esc_html($type_label . ' ' . $unit->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_parent_position_id"><?php _e('ตำแหน่งผู้บังคับบัญชา', 'project-core'); ?></label></th>
                <td>
                    <select name="org_parent_position_id" id="org_parent_position_id" class="regular-text" style="max-width:450px;">
                        <option value="0"><?php _e('-- ไม่มีผู้บังคับบัญชา / สูงสุดในสายงาน --', 'project-core'); ?></option>
                        <?php foreach ($positions_list as $p_item): ?>
                            <option value="<?php echo esc_attr($p_item->ID); ?>" <?php selected($parent_pos, $p_item->ID); ?>>
                                <?php echo esc_html($p_item->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_position_sub"><?php _e('คำบรรยายกำกับย่อย', 'project-core'); ?></label></th>
                <td>
                    <input type="text" name="org_position_sub" id="org_position_sub" value="<?php echo esc_attr($pos_sub); ?>" class="regular-text" placeholder="e.g. ผู้บริหารวิทยาลัย, ผู้บริหารฝ่าย, ผู้รับผิดชอบงาน">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_position_remark"><?php _e('ข้อความกำกับสถานะพิเศษ', 'project-core'); ?></label></th>
                <td>
                    <input type="text" name="org_position_remark" id="org_position_remark" value="<?php echo esc_attr($pos_remark); ?>" class="regular-text" placeholder="e.g. รักษาการ, ปฏิบัติหน้าที่แทน (ถ้ามี)">
                </td>
            </tr>

            <tr>
                <th scope="row"><label><?php _e('สถานะตำแหน่ง', 'project-core'); ?></label></th>
                <td>
                    <label style="margin-right:20px;">
                        <input type="radio" name="org_position_status" value="active" <?php checked($pos_status, 'active'); ?>>
                        <span style="color:#0073aa; font-weight:600;"><?php _e('มีผู้ดำรงตำแหน่ง (Active)', 'project-core'); ?></span>
                    </label>
                    <label>
                        <input type="radio" name="org_position_status" value="vacant" <?php checked($pos_status, 'vacant'); ?>>
                        <span style="color:#d63638; font-weight:600;"><?php _e('ตำแหน่งว่าง (Vacant)', 'project-core'); ?></span>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="org_position_order"><?php _e('ลำดับการแสดงผล', 'project-core'); ?></label></th>
                <td>
                    <input type="number" name="org_position_order" id="org_position_order" value="<?php echo esc_attr($pos_order); ?>" class="small-text" min="0">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save Org Unit Meta
     */
    public function save_org_unit_meta($post_id, $post) {
        if (!isset($_POST['project_core_org_unit_nonce_field']) || !wp_verify_nonce($_POST['project_core_org_unit_nonce_field'], 'project_core_org_unit_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $type  = isset($_POST['org_unit_type']) ? sanitize_text_field($_POST['org_unit_type']) : 'job';
        $code  = isset($_POST['org_unit_code']) ? sanitize_title($_POST['org_unit_code']) : '';
        $parent= isset($_POST['org_unit_parent']) ? absint($_POST['org_unit_parent']) : 0;
        $order = isset($_POST['org_unit_order']) ? absint($_POST['org_unit_order']) : 0;
        $color = isset($_POST['org_unit_color_text']) ? sanitize_hex_color($_POST['org_unit_color_text']) : '#278bb4';
        $desc  = isset($_POST['org_unit_desc']) ? sanitize_textarea_field($_POST['org_unit_desc']) : '';

        // If division, cannot have parent
        if ($type === 'division') {
            $parent = 0;
        }

        update_post_meta($post_id, '_org_unit_type', $type);
        update_post_meta($post_id, '_org_unit_code', $code);
        update_post_meta($post_id, '_org_unit_parent', $parent);
        update_post_meta($post_id, '_org_unit_order', $order);
        update_post_meta($post_id, '_org_unit_color', $color);
        update_post_meta($post_id, '_org_unit_desc', $desc);

        // Sync WordPress menu_order
        wp_update_post(array(
            'ID'         => $post_id,
            'menu_order' => $order,
        ));
    }

    /**
     * Save Org Position Meta
     */
    public function save_org_position_meta($post_id, $post) {
        if (!isset($_POST['project_core_org_position_nonce_field']) || !wp_verify_nonce($_POST['project_core_org_position_nonce_field'], 'project_core_org_position_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $type       = isset($_POST['org_position_type']) ? sanitize_text_field($_POST['org_position_type']) : 'job_head';
        $person_id  = isset($_POST['org_personnel_id']) ? absint($_POST['org_personnel_id']) : 0;
        $unit_id    = isset($_POST['org_unit_id']) ? absint($_POST['org_unit_id']) : 0;
        $parent_pos = isset($_POST['org_parent_position_id']) ? absint($_POST['org_parent_position_id']) : 0;
        $order      = isset($_POST['org_position_order']) ? absint($_POST['org_position_order']) : 0;
        $status     = isset($_POST['org_position_status']) ? sanitize_text_field($_POST['org_position_status']) : 'active';
        $remark     = isset($_POST['org_position_remark']) ? sanitize_text_field($_POST['org_position_remark']) : '';
        $sub        = isset($_POST['org_position_sub']) ? sanitize_text_field($_POST['org_position_sub']) : '';

        // Prevent selecting self as parent
        if ($parent_pos === $post_id) {
            $parent_pos = 0;
        }

        // Auto vacant if person_id is 0
        if ($person_id === 0) {
            $status = 'vacant';
        }

        update_post_meta($post_id, '_org_position_type', $type);
        update_post_meta($post_id, '_org_personnel_id', $person_id);
        update_post_meta($post_id, '_org_unit_id', $unit_id);
        update_post_meta($post_id, '_org_parent_position_id', $parent_pos);
        update_post_meta($post_id, '_org_position_order', $order);
        update_post_meta($post_id, '_org_position_status', $status);
        update_post_meta($post_id, '_org_position_remark', $remark);
        update_post_meta($post_id, '_org_position_sub', $sub);

        wp_update_post(array(
            'ID'         => $post_id,
            'menu_order' => $order,
        ));
    }

    /**
     * Admin Columns: Org Unit
     */
    public function set_org_unit_columns($columns) {
        $new = array();
        $new['cb']         = $columns['cb'];
        $new['title']      = __('ชื่อหน่วยงาน', 'project-core');
        $new['unit_type']  = __('ประเภท', 'project-core');
        $new['parent_div'] = __('สังกัดฝ่าย', 'project-core');
        $new['unit_code']  = __('รหัส/Slug', 'project-core');
        $new['unit_order'] = __('ลำดับ', 'project-core');
        $new['date']       = $columns['date'];
        return $new;
    }

    public function render_org_unit_columns($column, $post_id) {
        switch ($column) {
            case 'unit_type':
                $type = get_post_meta($post_id, '_org_unit_type', true);
                if ($type === 'division') {
                    $color = get_post_meta($post_id, '_org_unit_color', true) ?: '#278bb4';
                    echo '<span style="display:inline-block; padding:3px 8px; border-radius:4px; font-weight:bold; color:#fff; background:' . esc_attr($color) . ';">' . __('ฝ่าย (Division)', 'project-core') . '</span>';
                } else {
                    echo '<span style="color:#64748b;">' . __('งานในสังกัด (Job)', 'project-core') . '</span>';
                }
                break;
            case 'parent_div':
                $pid = get_post_meta($post_id, '_org_unit_parent', true);
                if ($pid) {
                    $p = get_post($pid);
                    echo $p ? '<strong>' . esc_html($p->post_title) . '</strong>' : '—';
                } else {
                    echo '—';
                }
                break;
            case 'unit_code':
                $code = get_post_meta($post_id, '_org_unit_code', true);
                echo $code ? '<code>' . esc_html($code) . '</code>' : '—';
                break;
            case 'unit_order':
                echo intval(get_post_meta($post_id, '_org_unit_order', true));
                break;
        }
    }

    public function admin_org_unit_sort_order($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'org_unit') {
            return;
        }
        $query->set('meta_key', '_org_unit_order');
        $query->set('orderby', array('meta_value_num' => 'ASC', 'date' => 'DESC'));
    }

    /**
     * Admin Columns: Org Position
     */
    public function set_org_position_columns($columns) {
        $new = array();
        $new['cb']        = $columns['cb'];
        $new['title']     = __('ชื่อตำแหน่ง', 'project-core');
        $new['pos_type']  = __('ประเภท', 'project-core');
        $new['appointee'] = __('ผู้ดำรงตำแหน่ง', 'project-core');
        $new['unit']      = __('หน่วยงานที่สังกัด', 'project-core');
        $new['status']    = __('สถานะ', 'project-core');
        $new['pos_order'] = __('ลำดับ', 'project-core');
        $new['date']      = $columns['date'];
        return $new;
    }

    public function render_org_position_columns($column, $post_id) {
        switch ($column) {
            case 'pos_type':
                $type = get_post_meta($post_id, '_org_position_type', true);
                $types = array(
                    'executive'     => '<span style="color:#0047BA; font-weight:bold;">ผู้บริหารระดับนโยบาย</span>',
                    'committee'     => '<span style="color:#d97706; font-weight:bold;">คณะกรรมการสถานศึกษา</span>',
                    'division_head' => '<span style="color:#7c3aed; font-weight:bold;">ผู้บริหารฝ่าย</span>',
                    'job_head'      => '<span style="color:#059669;">ผู้รับผิดชอบงาน</span>',
                );
                echo isset($types[$type]) ? $types[$type] : esc_html($type);
                break;
            case 'appointee':
                $person_id = get_post_meta($post_id, '_org_personnel_id', true);
                if ($person_id && ($person = get_post($person_id))) {
                    $thumb = get_the_post_thumbnail($person_id, array(36, 42), array('style' => 'border-radius:4px; vertical-align:middle; margin-right:6px; object-fit:cover;'));
                    echo $thumb . '<strong>' . esc_html($person->post_title) . '</strong>';
                    $remark = get_post_meta($post_id, '_org_position_remark', true);
                    if ($remark) {
                        echo ' <span style="font-size:11px; background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:3px;">(' . esc_html($remark) . ')</span>';
                    }
                } else {
                    echo '<span style="color:#94a3b8; font-style:italic;">-- ตำแหน่งว่าง --</span>';
                }
                break;
            case 'unit':
                $uid = get_post_meta($post_id, '_org_unit_id', true);
                if ($uid && ($unit = get_post($uid))) {
                    echo esc_html($unit->post_title);
                } else {
                    echo '<span style="color:#94a3b8;">ระดับวิทยาลัย</span>';
                }
                break;
            case 'status':
                $st = get_post_meta($post_id, '_org_position_status', true);
                if ($st === 'vacant') {
                    echo '<span style="color:#ef4444; font-weight:600;">ว่าง</span>';
                } else {
                    echo '<span style="color:#10b981; font-weight:600;">มีผู้ดำรงตำแหน่ง</span>';
                }
                break;
            case 'pos_order':
                echo intval(get_post_meta($post_id, '_org_position_order', true));
                break;
        }
    }

    public function admin_org_position_sort_order($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'org_position') {
            return;
        }
        $query->set('meta_key', '_org_position_order');
        $query->set('orderby', array('meta_value_num' => 'ASC', 'date' => 'DESC'));
    }

    /**
     * Get Settings
     */
    public static function get_settings() {
        $defaults = array(
            'page_title'       => 'โครงสร้างการบริหาร',
            'page_description' => 'รู้จักผู้บริหารและหน่วยงานที่ร่วมขับเคลื่อนวิทยาลัยเทคโนโลยีศรีราชา',
            'academic_year'    => '2569',
            'updated_at'       => gmdate('Y-m-d'),
            'pdf_url'          => '',
            'committee_title'  => 'คณะกรรมการสถานศึกษา',
            'committee_desc'   => 'ร่วมกำกับ ดูแล และส่งเสริมมาตรฐานคุณภาพการจัดการศึกษา',
            'committee_link'   => '',
        );

        $saved = get_option(self::SETTINGS_OPTION, array());
        return wp_parse_args($saved, $defaults);
    }

    /**
     * Render Settings Page
     */
    public function render_settings_page() {
        if (isset($_POST['project_core_org_settings_nonce'])) {
            if (wp_verify_nonce($_POST['project_core_org_settings_nonce'], 'project_core_org_save_settings')) {
                $settings = array(
                    'page_title'       => sanitize_text_field($_POST['page_title']),
                    'page_description' => sanitize_textarea_field($_POST['page_description']),
                    'academic_year'    => sanitize_text_field($_POST['academic_year']),
                    'updated_at'       => sanitize_text_field($_POST['updated_at']),
                    'pdf_url'          => esc_url_raw($_POST['pdf_url']),
                    'committee_title'  => sanitize_text_field($_POST['committee_title']),
                    'committee_desc'   => sanitize_textarea_field($_POST['committee_desc']),
                    'committee_link'   => esc_url_raw($_POST['committee_link']),
                );
                update_option(self::SETTINGS_OPTION, $settings);
                echo '<div class="notice notice-success is-dismissible"><p>' . __('บันทึกการตั้งค่าเรียบร้อยแล้ว', 'project-core') . '</p></div>';
            }
        }

        $settings = self::get_settings();
        wp_enqueue_media();
        ?>
        <div class="wrap">
            <h1><?php _e('ตั้งค่าหน้าโครงสร้างการบริหาร', 'project-core'); ?></h1>
            <p class="description"><?php _e('กำหนดข้อมูลทั่วไป ปีการศึกษา และเอกสารดาวน์โหลดสำหรับหน้าโครงสร้างการบริหารบนเว็บไซต์', 'project-core'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('project_core_org_save_settings', 'project_core_org_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="page_title"><?php _e('ชื่อหน้า', 'project-core'); ?></label></th>
                        <td><input type="text" name="page_title" id="page_title" value="<?php echo esc_attr($settings['page_title']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="page_description"><?php _e('คำอธิบายหน้า', 'project-core'); ?></label></th>
                        <td><textarea name="page_description" id="page_description" rows="2" class="large-text"><?php echo esc_textarea($settings['page_description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="academic_year"><?php _e('ปีการศึกษา', 'project-core'); ?></label></th>
                        <td><input type="text" name="academic_year" id="academic_year" value="<?php echo esc_attr($settings['academic_year']); ?>" class="regular-text" style="max-width:150px;"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="updated_at"><?php _e('วันที่ปรับปรุงข้อมูล', 'project-core'); ?></label></th>
                        <td><input type="date" name="updated_at" id="updated_at" value="<?php echo esc_attr($settings['updated_at']); ?>" class="regular-text" style="max-width:200px;"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pdf_url"><?php _e('ไฟล์ PDF ผังฉบับอนุมัติ (ถ้ามี)', 'project-core'); ?></label></th>
                        <td>
                            <input type="text" name="pdf_url" id="pdf_url" value="<?php echo esc_attr($settings['pdf_url']); ?>" class="large-text" placeholder="https://...">
                            <button type="button" class="button" id="upload_pdf_btn"><?php _e('เลือกไฟล์จากคลังสื่อ', 'project-core'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="committee_title"><?php _e('หัวข้อคณะกรรมการสถานศึกษา', 'project-core'); ?></label></th>
                        <td><input type="text" name="committee_title" id="committee_title" value="<?php echo esc_attr($settings['committee_title']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="committee_desc"><?php _e('รายละเอียดคณะกรรมการสถานศึกษา', 'project-core'); ?></label></th>
                        <td><textarea name="committee_desc" id="committee_desc" rows="2" class="large-text"><?php echo esc_textarea($settings['committee_desc']); ?></textarea></td>
                    </tr>
                </table>

                <?php submit_button(__('บันทึกการตั้งค่า', 'project-core')); ?>
            </form>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('upload_pdf_btn');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var frame = wp.media({
                        title: 'เลือกไฟล์ PDF ผังโครงสร้าง',
                        button: { text: 'ใช้ไฟล์นี้' },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        document.getElementById('pdf_url').value = attachment.url;
                    });
                    frame.open();
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render Overview Page
     */
    public function render_overview_page() {
        $data = $this->get_structure_data();
        ?>
        <div class="wrap">
            <h1 style="display:flex; align-items:center; justify-content:space-between;">
                <span><?php _e('ผังและภาพรวมโครงสร้างการบริหาร', 'project-core'); ?></span>
                <div>
                    <a href="<?php echo admin_url('post-new.php?post_type=org_position'); ?>" class="page-title-action"><?php _e('+ เพิ่มตำแหน่งใหม่', 'project-core'); ?></a>
                    <a href="<?php echo admin_url('post-new.php?post_type=org_unit'); ?>" class="page-title-action"><?php _e('+ เพิ่มหน่วยงานใหม่', 'project-core'); ?></a>
                </div>
            </h1>

            <div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:20px; margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                    <div>
                        <h2 style="margin:0 0 5px;"><?php echo esc_html($data['settings']['page_title']); ?> (ปีการศึกษา <?php echo esc_html($data['settings']['academic_year']); ?>)</h2>
                        <p style="margin:0; color:#64748b;"><?php echo esc_html($data['settings']['page_description']); ?></p>
                    </div>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('ต้องการรีเซ็ต/ติดตั้งข้อมูลโครงสร้างเริ่มต้น 4 ฝ่ายและ 23 งานหรือไม่?');">
                        <?php wp_nonce_field('project_core_seed_org', 'seed_nonce'); ?>
                        <input type="hidden" name="action" value="project_core_seed_organization">
                        <button type="submit" class="button button-secondary">
                            ⚡ <?php _e('ติดตั้ง/รีเซ็ตข้อมูลเริ่มต้น (4 ฝ่าย 23 งาน)', 'project-core'); ?>
                        </button>
                    </form>
                </div>

                <!-- Leaders overview -->
                <h3 style="color:#0047BA; margin-top:0;">1. ผู้บริหารระดับนโยบาย</h3>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:15px; margin-bottom:25px;">
                    <?php foreach ($data['leaders'] as $leader): ?>
                        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:15px; background:#f8fafc; display:flex; gap:12px; align-items:center;">
                            <?php if (!empty($leader['personnel']['photo'])): ?>
                                <img src="<?php echo esc_url($leader['personnel']['photo']); ?>" style="width:50px; height:60px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <div style="width:50px; height:60px; background:#cbd5e1; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:11px;">ไม่มีรูป</div>
                            <?php endif; ?>
                            <div>
                                <div style="font-size:11px; font-weight:bold; color:#0047BA;"><?php echo esc_html($leader['position']); ?></div>
                                <div style="font-size:14px; font-weight:bold;"><?php echo esc_html($leader['personnel']['name']); ?></div>
                                <div style="font-size:11px; color:#64748b;"><?php echo esc_html($leader['personnel']['sub'] ?: 'ผู้บริหารวิทยาลัย'); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Divisions overview -->
                <h3 style="color:#0047BA;">2. ผู้บริหารและงานในสังกัด 4 ฝ่าย</h3>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                    <?php foreach ($data['divisions'] as $div): ?>
                        <div style="border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:#fff;">
                            <div style="background:<?php echo esc_attr($div['color']); ?>; color:#fff; padding:12px 15px; font-weight:bold; font-size:14px;">
                                <?php echo esc_html($div['title']); ?>
                            </div>
                            <div style="padding:15px;">
                                <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
                                    <?php if (!empty($div['head']['personnel']['photo'])): ?>
                                        <img src="<?php echo esc_url($div['head']['personnel']['photo']); ?>" style="width:40px; height:48px; object-fit:cover; border-radius:4px;">
                                    <?php else: ?>
                                        <div style="width:40px; height:48px; background:#e2e8f0; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:10px;">รูปแทน</div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-size:10px; color:#64748b;"><?php echo esc_html($div['head']['position'] ?: 'ผู้บริหารฝ่าย'); ?></div>
                                        <div style="font-size:13px; font-weight:bold;"><?php echo esc_html($div['head']['personnel']['name'] ?: 'ชื่อ–นามสกุล'); ?></div>
                                    </div>
                                </div>
                                
                                <div style="font-size:11px; font-weight:bold; color:#475569; margin-bottom:6px;">
                                    งานในสังกัด (<?php echo count($div['jobs']); ?> งาน):
                                </div>
                                <ul style="margin:0; padding-left:18px; font-size:12px; color:#64748b; line-height:1.6;">
                                    <?php foreach ($div['jobs'] as $job): ?>
                                        <li>
                                            <strong><?php echo esc_html($job['title']); ?></strong>
                                            <?php if (!empty($job['personnel']['name']) && $job['personnel']['name'] !== 'ชื่อ–นามสกุล'): ?>
                                                — <span style="color:#0f172a;"><?php echo esc_html($job['personnel']['name']); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get Complete Structure Data for REST & Admin
     */
    public function get_structure_data() {
        $settings = self::get_settings();

        // 1. Fetch Policy Leaders & Committee
        $leader_posts = get_posts(array(
            'post_type'      => 'org_position',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_org_position_type',
                    'value'   => array('executive', 'committee'),
                    'compare' => 'IN',
                ),
            ),
            'meta_key'       => '_org_position_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $leaders = array();
        $committee = null;

        foreach ($leader_posts as $p) {
            $type       = get_post_meta($p->ID, '_org_position_type', true);
            $person_id  = get_post_meta($p->ID, '_org_personnel_id', true);
            $sub        = get_post_meta($p->ID, '_org_position_sub', true);
            $remark     = get_post_meta($p->ID, '_org_position_remark', true);
            $status     = get_post_meta($p->ID, '_org_position_status', true);

            $person_data = $this->format_personnel_data($person_id);

            $item = array(
                'id'        => $p->ID,
                'position'  => $p->post_title,
                'type'      => $type,
                'sub'       => $sub ?: ($type === 'committee' ? 'คณะกรรมการ' : 'ผู้บริหารวิทยาลัย'),
                'remark'    => $remark,
                'status'    => $status,
                'personnel' => $person_data,
            );

            if ($type === 'committee') {
                $committee = $item;
            } else {
                $leaders[] = $item;
            }
        }

        // If no leaders found in DB, provide fallback matching user requirements
        if (empty($leaders)) {
            $leaders = array(
                array(
                    'id'        => 'mock_1',
                    'position'  => 'ผู้รับใบอนุญาต',
                    'type'      => 'executive',
                    'sub'       => 'ผู้บริหารวิทยาลัย',
                    'remark'    => '',
                    'status'    => 'active',
                    'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => ''),
                ),
                array(
                    'id'        => 'mock_2',
                    'position'  => 'ผู้จัดการ',
                    'type'      => 'executive',
                    'sub'       => 'ผู้บริหารวิทยาลัย',
                    'remark'    => '',
                    'status'    => 'active',
                    'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => ''),
                ),
                array(
                    'id'        => 'mock_3',
                    'position'  => 'ผู้อำนวยการวิทยาลัย',
                    'type'      => 'executive',
                    'sub'       => 'ผู้อำนวยการ',
                    'remark'    => '',
                    'status'    => 'active',
                    'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => ''),
                ),
            );
        }

        if (!$committee) {
            $committee = array(
                'id'        => 'mock_comm',
                'position'  => $settings['committee_title'],
                'type'      => 'committee',
                'sub'       => $settings['committee_desc'],
                'remark'    => '',
                'status'    => 'active',
                'personnel' => array('name' => 'คณะกรรมการสถานศึกษา', 'photo' => ''),
            );
        }

        // 2. Fetch Divisions
        $division_posts = get_posts(array(
            'post_type'      => 'org_unit',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_org_unit_type',
                    'value'   => 'division',
                ),
            ),
            'meta_key'       => '_org_unit_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $divisions = array();

        foreach ($division_posts as $div) {
            $div_id    = $div->ID;
            $code      = get_post_meta($div_id, '_org_unit_code', true);
            $color     = get_post_meta($div_id, '_org_unit_color', true) ?: '#278bb4';
            $order     = intval(get_post_meta($div_id, '_org_unit_order', true));
            $desc      = get_post_meta($div_id, '_org_unit_desc', true);

            // Fetch head position for this division
            $head_post = get_posts(array(
                'post_type'      => 'org_position',
                'posts_per_page' => 1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => '_org_unit_id',
                        'value' => $div_id,
                    ),
                    array(
                        'key'   => '_org_position_type',
                        'value' => 'division_head',
                    ),
                ),
            ));

            $head_data = array(
                'position'  => $div->post_title,
                'sub'       => 'ผู้บริหารฝ่าย',
                'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => ''),
            );

            if (!empty($head_post)) {
                $hp = $head_post[0];
                $p_id = get_post_meta($hp->ID, '_org_personnel_id', true);
                $head_data = array(
                    'id'        => $hp->ID,
                    'position'  => $hp->post_title,
                    'sub'       => get_post_meta($hp->ID, '_org_position_sub', true) ?: 'ผู้บริหารฝ่าย',
                    'remark'    => get_post_meta($hp->ID, '_org_position_remark', true),
                    'personnel' => $this->format_personnel_data($p_id),
                );
            }

            // Fetch jobs under this division
            $job_unit_posts = get_posts(array(
                'post_type'      => 'org_unit',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => '_org_unit_type',
                        'value' => 'job',
                    ),
                    array(
                        'key'   => '_org_unit_parent',
                        'value' => $div_id,
                    ),
                ),
                'meta_key'       => '_org_unit_order',
                'orderby'        => 'meta_value_num',
                'order'          => 'ASC',
            ));

            $jobs = array();
            foreach ($job_unit_posts as $ju) {
                // Find responsible position
                $job_pos = get_posts(array(
                    'post_type'      => 'org_position',
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                    'meta_query'     => array(
                        array(
                            'key'   => '_org_unit_id',
                            'value' => $ju->ID,
                        ),
                    ),
                ));

                $job_person_data = array('name' => 'ชื่อ–นามสกุล', 'photo' => '');
                $job_remark = '';
                $job_pos_title = 'ผู้รับผิดชอบงาน';

                if (!empty($job_pos)) {
                    $jp = $job_pos[0];
                    $jp_pid = get_post_meta($jp->ID, '_org_personnel_id', true);
                    $job_person_data = $this->format_personnel_data($jp_pid);
                    $job_remark = get_post_meta($jp->ID, '_org_position_remark', true);
                    $job_pos_title = $jp->post_title;
                }

                $jobs[] = array(
                    'id'             => $ju->ID,
                    'title'          => $ju->post_title,
                    'code'           => get_post_meta($ju->ID, '_org_unit_code', true),
                    'order'          => intval(get_post_meta($ju->ID, '_org_unit_order', true)),
                    'position_title' => $job_pos_title,
                    'remark'         => $job_remark,
                    'personnel'      => $job_person_data,
                );
            }

            $divisions[] = array(
                'id'          => $div_id,
                'slug'        => $code ?: sanitize_title($div->post_title),
                'title'       => $div->post_title,
                'color'       => $color,
                'order'       => $order,
                'description' => $desc,
                'head'        => $head_data,
                'jobs'        => $jobs,
            );
        }

        // Default mock divisions if empty
        if (empty($divisions)) {
            $divisions = $this->get_default_mock_divisions();
        }

        return array(
            'settings'  => $settings,
            'leaders'   => $leaders,
            'committee' => $committee,
            'divisions' => $divisions,
        );
    }

    /**
     * Helper to format personnel data
     */
    private function format_personnel_data($personnel_id) {
        if (!$personnel_id || !($person = get_post($personnel_id))) {
            return array(
                'name'     => 'ชื่อ–นามสกุล',
                'photo'    => '',
                'position' => '',
            );
        }

        $photo_url = get_the_post_thumbnail_url($personnel_id, 'large') ?: '';
        $pos = get_post_meta($personnel_id, 'personnel_position', true) ?: '';

        return array(
            'id'       => $person->ID,
            'name'     => $person->post_title,
            'photo'    => $photo_url,
            'position' => $pos,
        );
    }

    /**
     * Default Mock Divisions matching specification
     */
    private function get_default_mock_divisions() {
        return array(
            array(
                'id'    => 'div_1',
                'slug'  => 'resource-management',
                'title' => 'ฝ่ายบริหารทรัพยากร',
                'color' => '#278bb4',
                'order' => 1,
                'head'  => array('position' => 'ฝ่ายบริหารทรัพยากร', 'sub' => 'ผู้บริหารฝ่าย', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                'jobs'  => array(
                    array('id' => 'j_1_1', 'title' => 'งานบริหารงานทั่วไป', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_1_2', 'title' => 'งานบริหารและพัฒนาทรัพยากรบุคคล', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_1_3', 'title' => 'งานการเงิน งานบัญชี และพัสดุ', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_1_4', 'title' => 'งานอาคารสถานที่', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_1_5', 'title' => 'งานทะเบียน', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                ),
            ),
            array(
                'id'    => 'div_2',
                'slug'  => 'strategy-planning',
                'title' => 'ฝ่ายยุทธศาสตร์และแผนงาน',
                'color' => '#7372b3',
                'order' => 2,
                'head'  => array('position' => 'ฝ่ายยุทธศาสตร์และแผนงาน', 'sub' => 'ผู้บริหารฝ่าย', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                'jobs'  => array(
                    array('id' => 'j_2_1', 'title' => 'งานพัฒนายุทธศาสตร์ แผนงานและงบประมาณ', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_2_2', 'title' => 'งานมาตรฐานและการประกันคุณภาพการศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_2_3', 'title' => 'งานศูนย์สารสนเทศและสื่อสารองค์กร', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_2_4', 'title' => 'งานส่งเสริมการวิจัย นวัตกรรม และสิ่งประดิษฐ์', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_2_5', 'title' => 'งานส่งเสริมธุรกิจและการเป็นผู้ประกอบการ', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_2_6', 'title' => 'งานติดตามและประเมินผลการจัดการศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                ),
            ),
            array(
                'id'    => 'div_3',
                'slug'  => 'student-affairs',
                'title' => 'ฝ่ายกิจการนักเรียน นักศึกษา',
                'color' => '#348a7a',
                'order' => 3,
                'head'  => array('position' => 'ฝ่ายกิจการนักเรียน นักศึกษา', 'sub' => 'ผู้บริหารฝ่าย', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                'jobs'  => array(
                    array('id' => 'j_3_1', 'title' => 'งานกิจกรรมนักเรียน นักศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_3_2', 'title' => 'งานครูที่ปรึกษาและการแนะแนว', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_3_3', 'title' => 'งานปกครองและความปลอดภัยนักเรียน นักศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_3_4', 'title' => 'งานสวัสดิการนักเรียน นักศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_3_5', 'title' => 'งานโครงการพิเศษและการบริการ', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                ),
            ),
            array(
                'id'    => 'div_4',
                'slug'  => 'academic-affairs',
                'title' => 'ฝ่ายวิชาการ',
                'color' => '#bd9052',
                'order' => 4,
                'head'  => array('position' => 'ฝ่ายวิชาการ', 'sub' => 'ผู้บริหารฝ่าย', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                'jobs'  => array(
                    array('id' => 'j_4_1', 'title' => 'แผนกวิชา / สาขาวิชา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_4_2', 'title' => 'งานพัฒนาหลักสูตรและการจัดการเรียนรู้', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_4_3', 'title' => 'งานวัดผลและประเมินผล', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_4_4', 'title' => 'งานอาชีวศึกษาระบบทวิภาคีและความร่วมมือ', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_4_5', 'title' => 'งานวิทยบริการและเทคโนโลยีการศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                    array('id' => 'j_4_6', 'title' => 'งานการจัดการศึกษาและทดสอบทางการศึกษา', 'personnel' => array('name' => 'ชื่อ–นามสกุล', 'photo' => '')),
                ),
            ),
        );
    }

    /**
     * Register REST API Routes
     */
    public function register_rest_routes() {
        // Main project-core route
        register_rest_route('project-core/v1', '/organization', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'rest_get_organization'),
            'permission_callback' => '__return_true',
        ));

        // Alias college/v1 route
        register_rest_route('college/v1', '/organization', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'rest_get_organization'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST API Callback
     */
    public function rest_get_organization($request) {
        $data = $this->get_structure_data();
        return rest_ensure_response($data);
    }

    /**
     * Handle Admin Seed Action
     */
    public function handle_seed_action() {
        if (!current_user_can('manage_options') || !check_admin_referer('project_core_seed_org', 'seed_nonce')) {
            wp_die('Unauthorized');
        }

        $this->seed_default_structure();
        wp_safe_redirect(admin_url('admin.php?page=project-core-organization&seeded=1'));
        exit;
    }

    /**
     * Maybe auto seed if table is completely empty
     */
    public function maybe_auto_seed() {
        if (!is_admin()) return;
        $done = get_option('project_core_org_initial_seed_done');
        if (!$done) {
            $existing_units = wp_count_posts('org_unit');
            if (empty($existing_units->publish)) {
                $this->seed_default_structure();
            }
            update_option('project_core_org_initial_seed_done', 1);
        }
    }

    /**
     * Seed Default Structure: 3 Leaders, 1 Committee, 4 Divisions, 23 Jobs
     */
    public function seed_default_structure() {
        // 1. Create or Find Leaders
        $leaders_def = array(
            array('title' => 'ผู้รับใบอนุญาต', 'type' => 'executive', 'sub' => 'ผู้บริหารวิทยาลัย', 'order' => 1),
            array('title' => 'ผู้จัดการ', 'type' => 'executive', 'sub' => 'ผู้บริหารวิทยาลัย', 'order' => 2),
            array('title' => 'ผู้อำนวยการวิทยาลัย', 'type' => 'executive', 'sub' => 'ผู้อำนวยการ', 'order' => 3),
            array('title' => 'คณะกรรมการสถานศึกษา', 'type' => 'committee', 'sub' => 'คณะกรรมการสถานศึกษา', 'order' => 4),
        );

        $director_id = 0;
        foreach ($leaders_def as $ldef) {
            $existing = get_posts(array(
                'post_type'   => 'org_position',
                'title'       => $ldef['title'],
                'post_status' => 'any',
                'numberposts' => 1,
            ));

            if (empty($existing)) {
                $pos_id = wp_insert_post(array(
                    'post_title'  => $ldef['title'],
                    'post_type'   => 'org_position',
                    'post_status' => 'publish',
                    'menu_order'  => $ldef['order'],
                ));
            } else {
                $pos_id = $existing[0]->ID;
            }

            update_post_meta($pos_id, '_org_position_type', $ldef['type']);
            update_post_meta($pos_id, '_org_position_sub', $ldef['sub']);
            update_post_meta($pos_id, '_org_position_order', $ldef['order']);
            update_post_meta($pos_id, '_org_position_status', 'active');

            if ($ldef['title'] === 'ผู้อำนวยการวิทยาลัย') {
                $director_id = $pos_id;
            }
        }

        // 2. Divisions & Jobs Definition
        $divisions_def = array(
            array(
                'title' => 'ฝ่ายบริหารทรัพยากร',
                'code'  => 'resource-management',
                'color' => '#278bb4',
                'order' => 1,
                'jobs'  => array(
                    'งานบริหารงานทั่วไป',
                    'งานบริหารและพัฒนาทรัพยากรบุคคล',
                    'งานการเงิน งานบัญชี และพัสดุ',
                    'งานอาคารสถานที่',
                    'งานทะเบียน',
                ),
            ),
            array(
                'title' => 'ฝ่ายยุทธศาสตร์และแผนงาน',
                'code'  => 'strategy-planning',
                'color' => '#7372b3',
                'order' => 2,
                'jobs'  => array(
                    'งานพัฒนายุทธศาสตร์ แผนงานและงบประมาณ',
                    'งานมาตรฐานและการประกันคุณภาพการศึกษา',
                    'งานศูนย์สารสนเทศและสื่อสารองค์กร',
                    'งานส่งเสริมการวิจัย นวัตกรรม และสิ่งประดิษฐ์',
                    'งานส่งเสริมธุรกิจและการเป็นผู้ประกอบการ',
                    'งานติดตามและประเมินผลการจัดการศึกษา',
                ),
            ),
            array(
                'title' => 'ฝ่ายกิจการนักเรียน นักศึกษา',
                'code'  => 'student-affairs',
                'color' => '#348a7a',
                'order' => 3,
                'jobs'  => array(
                    'งานกิจกรรมนักเรียน นักศึกษา',
                    'งานครูที่ปรึกษาและการแนะแนว',
                    'งานปกครองและความปลอดภัยนักเรียน นักศึกษา',
                    'งานสวัสดิการนักเรียน นักศึกษา',
                    'งานโครงการพิเศษและการบริการ',
                ),
            ),
            array(
                'title' => 'ฝ่ายวิชาการ',
                'code'  => 'academic-affairs',
                'color' => '#bd9052',
                'order' => 4,
                'jobs'  => array(
                    'แผนกวิชา / สาขาวิชา',
                    'งานพัฒนาหลักสูตรและการจัดการเรียนรู้',
                    'งานวัดผลและประเมินผล',
                    'งานอาชีวศึกษาระบบทวิภาคีและความร่วมมือ',
                    'งานวิทยบริการและเทคโนโลยีการศึกษา',
                    'งานการจัดการศึกษาและทดสอบทางการศึกษา',
                ),
            ),
        );

        foreach ($divisions_def as $d_data) {
            // Check or create division unit
            $div_post = get_posts(array(
                'post_type'   => 'org_unit',
                'title'       => $d_data['title'],
                'post_status' => 'any',
                'numberposts' => 1,
            ));

            if (empty($div_post)) {
                $div_id = wp_insert_post(array(
                    'post_title'  => $d_data['title'],
                    'post_type'   => 'org_unit',
                    'post_status' => 'publish',
                    'menu_order'  => $d_data['order'],
                ));
            } else {
                $div_id = $div_post[0]->ID;
            }

            update_post_meta($div_id, '_org_unit_type', 'division');
            update_post_meta($div_id, '_org_unit_code', $d_data['code']);
            update_post_meta($div_id, '_org_unit_color', $d_data['color']);
            update_post_meta($div_id, '_org_unit_order', $d_data['order']);

            // Create division head position
            $head_pos = get_posts(array(
                'post_type'   => 'org_position',
                'title'       => $d_data['title'],
                'post_status' => 'any',
                'numberposts' => 1,
            ));

            if (empty($head_pos)) {
                $head_pos_id = wp_insert_post(array(
                    'post_title'  => $d_data['title'],
                    'post_type'   => 'org_position',
                    'post_status' => 'publish',
                    'menu_order'  => $d_data['order'],
                ));
            } else {
                $head_pos_id = $head_pos[0]->ID;
            }

            update_post_meta($head_pos_id, '_org_position_type', 'division_head');
            update_post_meta($head_pos_id, '_org_unit_id', $div_id);
            update_post_meta($head_pos_id, '_org_position_sub', 'ผู้บริหารฝ่าย');
            update_post_meta($head_pos_id, '_org_parent_position_id', $director_id);
            update_post_meta($head_pos_id, '_org_position_order', $d_data['order']);
            update_post_meta($head_pos_id, '_org_position_status', 'active');

            // Create jobs under division
            $job_order = 1;
            foreach ($d_data['jobs'] as $job_title) {
                $job_post = get_posts(array(
                    'post_type'   => 'org_unit',
                    'title'       => $job_title,
                    'post_status' => 'any',
                    'numberposts' => 1,
                ));

                if (empty($job_post)) {
                    $job_id = wp_insert_post(array(
                        'post_title'  => $job_title,
                        'post_type'   => 'org_unit',
                        'post_status' => 'publish',
                        'menu_order'  => $job_order,
                    ));
                } else {
                    $job_id = $job_post[0]->ID;
                }

                update_post_meta($job_id, '_org_unit_type', 'job');
                update_post_meta($job_id, '_org_unit_parent', $div_id);
                update_post_meta($job_id, '_org_unit_order', $job_order);

                // Create job position
                $job_pos_post = get_posts(array(
                    'post_type'   => 'org_position',
                    'title'       => 'หัวหน้า' . $job_title,
                    'post_status' => 'any',
                    'numberposts' => 1,
                ));

                if (empty($job_pos_post)) {
                    $jp_id = wp_insert_post(array(
                        'post_title'  => 'หัวหน้า' . $job_title,
                        'post_type'   => 'org_position',
                        'post_status' => 'publish',
                        'menu_order'  => $job_order,
                    ));
                } else {
                    $jp_id = $job_pos_post[0]->ID;
                }

                update_post_meta($jp_id, '_org_position_type', 'job_head');
                update_post_meta($jp_id, '_org_unit_id', $job_id);
                update_post_meta($jp_id, '_org_parent_position_id', $head_pos_id);
                update_post_meta($jp_id, '_org_position_sub', 'ผู้รับผิดชอบงาน');
                update_post_meta($jp_id, '_org_position_order', $job_order);
                update_post_meta($jp_id, '_org_position_status', 'active');

                $job_order++;
            }
        }
    }
}
