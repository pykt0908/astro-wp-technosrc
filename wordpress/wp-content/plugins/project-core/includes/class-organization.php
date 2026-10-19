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

        // Admin Save All Action
        add_action('admin_post_project_core_save_organization_all', array($this, 'handle_save_organization_all'));

        // Admin Seed Action
        add_action('admin_post_project_core_seed_organization', array($this, 'handle_seed_action'));

        // Auto-seed if empty on first access
        add_action('admin_init', array($this, 'maybe_auto_seed'));

        // Sync legacy meta keys
        add_action('admin_init', array($this, 'sync_legacy_meta_keys'));
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
            'show_in_menu'       => false,
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
            'show_in_menu'       => false,
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
     * Sync legacy meta keys to ensure all existing posts are recognized
     */
    public function sync_legacy_meta_keys() {
        if (get_option('project_core_org_meta_synced_v3')) {
            return;
        }

        $units = get_posts(array(
            'post_type'      => 'org_unit',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ));
        foreach ($units as $u) {
            $utype = get_post_meta($u->ID, '_org_unit_type', true) ?: get_post_meta($u->ID, '_unit_type', true);
            if ($utype === 'department') $utype = 'job';
            if (empty($utype)) {
                $utype = (strpos($u->post_title, 'ฝ่าย') !== false) ? 'division' : 'job';
            }
            update_post_meta($u->ID, '_org_unit_type', $utype);
            update_post_meta($u->ID, '_unit_type', $utype);

            $parent = get_post_meta($u->ID, '_org_unit_parent', true) ?: get_post_meta($u->ID, '_parent_unit', true);
            update_post_meta($u->ID, '_org_unit_parent', $parent);
            update_post_meta($u->ID, '_parent_unit', $parent);

            $order = get_post_meta($u->ID, '_org_unit_order', true) ?: (get_post_meta($u->ID, '_unit_order', true) ?: $u->menu_order);
            update_post_meta($u->ID, '_org_unit_order', $order);
            update_post_meta($u->ID, '_unit_order', $order);
        }

        $pos = get_posts(array(
            'post_type'      => 'org_position',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ));
        foreach ($pos as $p) {
            $ptype = get_post_meta($p->ID, '_org_position_type', true) ?: get_post_meta($p->ID, '_position_type', true);
            if ($ptype === 'department_head') $ptype = 'job_head';
            if (empty($ptype)) {
                if (strpos($p->post_title, 'กรรมการ') !== false) {
                    $ptype = 'committee';
                } elseif (strpos($p->post_title, 'รองผู้อำนวยการ') !== false || strpos($p->post_title, 'ผู้บริหารฝ่าย') !== false) {
                    $ptype = 'division_head';
                } elseif (strpos($p->post_title, 'หัวหน้างาน') !== false) {
                    $ptype = 'job_head';
                } else {
                    $ptype = 'executive';
                }
            }
            update_post_meta($p->ID, '_org_position_type', $ptype);
            update_post_meta($p->ID, '_position_type', $ptype);

            $person = get_post_meta($p->ID, '_org_personnel_id', true) ?: get_post_meta($p->ID, '_personnel_id', true);
            update_post_meta($p->ID, '_org_personnel_id', $person);
            update_post_meta($p->ID, '_personnel_id', $person);

            $unit = get_post_meta($p->ID, '_org_unit_id', true) ?: get_post_meta($p->ID, '_unit_id', true);
            update_post_meta($p->ID, '_org_unit_id', $unit);
            update_post_meta($p->ID, '_unit_id', $unit);

            $porder = get_post_meta($p->ID, '_org_position_order', true) ?: (get_post_meta($p->ID, '_position_order', true) ?: $p->menu_order);
            update_post_meta($p->ID, '_org_position_order', $porder);
            update_post_meta($p->ID, '_position_order', $porder);
        }

        update_option('project_core_org_meta_synced_v3', 1);
    }

    /**
     * Render Overview Page (Modular Visual Org Builder)
     */
    public function render_overview_page() {
        $this->sync_legacy_meta_keys();
        $data = $this->get_structure_data();

        // Fetch all published personnel for dropdowns
        $all_personnel = get_posts(array(
            'post_type'      => 'personnel',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        ?>
        <div class="wrap org-builder-wrap" style="max-width:1280px; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            <style>
                .org-builder-wrap .postbox { border-radius:10px; border:1px solid #dcdcde; box-shadow:0 1px 4px rgba(0,0,0,0.05); overflow:hidden; margin-bottom:24px; background:#fff; }
                .org-builder-wrap .postbox-header { padding:14px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
                .org-builder-wrap table.widefat { border:none; box-shadow:none; }
                .org-builder-wrap table.widefat th { background:#f1f5f9; color:#475569; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:0.02em; padding:10px 14px; border-bottom:1px solid #cbd5e1; }
                .org-builder-wrap table.widefat td { padding:10px 14px; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
                .org-builder-wrap table.widefat tr:hover td { background:#fafbfc; }
                .org-builder-wrap .action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; color:#475569; cursor:pointer; text-decoration:none; font-size:12px; transition:all 0.15s ease; padding:0; }
                .org-builder-wrap .action-btn:hover { background:#f1f5f9; border-color:#94a3b8; color:#0f172a; }
                .org-builder-wrap .action-btn-danger:hover { background:#fee2e2; border-color:#fca5a5; color:#dc2626; }
                .org-builder-wrap .org-div-card { border-radius:12px; border:1px solid #e2e8f0; margin-bottom:24px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.04); overflow:hidden; }
                .org-builder-wrap .org-div-header { padding:14px 20px; background:#ffffff; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
                .org-builder-wrap input[type="text"], .org-builder-wrap select { border-radius:6px; border:1px solid #cbd5e1; font-size:13px; }
                .org-builder-wrap input[type="text"]:focus, .org-builder-wrap select:focus { border-color:#0047BA; box-shadow:0 0 0 2px rgba(0,71,186,0.15); }
                .org-builder-wrap .badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; }
            </style>

            <!-- Page Header -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin:20px 0; padding-bottom:16px; border-bottom:1px solid #cbd5e1;">
                <div>
                    <h1 style="font-size:24px; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:10px;">
                        <span class="dashicons dashicons-networking" style="font-size:30px; width:30px; height:30px; color:#0047BA;"></span>
                        <?php _e('จัดการโครงสร้างการบริหาร (Organization Builder)', 'project-core'); ?>
                    </h1>
                    <p style="margin:6px 0 0; color:#64748b; font-size:13px;">
                        <?php _e('เพิ่ม ลบ แก้ไข และจัดลำดับผู้บริหารระดับนโยบาย ฝ่ายงาน และผู้รับผิดชอบงานได้ครบถ้วนในหน้าเดียว', 'project-core'); ?>
                    </p>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="http://localhost:4321/about/organization/" target="_blank" class="button button-large" style="display:flex; align-items:center; gap:6px; font-weight:600; color:#0047BA; border-color:#0047BA;">
                        <span class="dashicons dashicons-external" style="font-size:16px; width:16px; height:16px;"></span>
                        <?php _e('ดูหน้าเว็บจริง (Astro)', 'project-core'); ?>
                    </a>
                    <button type="button" onclick="addNewDivisionCard()" class="button button-secondary button-large" style="font-weight:600;">
                        + <?php _e('เพิ่มฝ่ายใหม่', 'project-core'); ?>
                    </button>
                    <button type="submit" form="org_manager_form" class="button button-primary button-large" style="font-weight:700; padding:4px 22px; background:#0047BA; border-color:#003893;">
                        💾 <?php _e('บันทึกข้อมูลทั้งหมด', 'project-core'); ?>
                    </button>
                </div>
            </div>

            <!-- Notice messages -->
            <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px; border-radius:8px;">
                    <p><strong>✓ <?php _e('บันทึกข้อมูลโครงสร้างการบริหารเรียบร้อยแล้ว ข้อมูลหน้าบ้าน Astro จะอัปเดตทันที!', 'project-core'); ?></strong></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['seeded']) && $_GET['seeded'] == '1'): ?>
                <div class="notice notice-info is-dismissible" style="margin-bottom:20px; border-radius:8px;">
                    <p><strong>✓ <?php _e('รีเซ็ตข้อมูลโครงสร้างเริ่มต้น 4 ฝ่ายและ 23 งานเรียบร้อยแล้ว', 'project-core'); ?></strong></p>
                </div>
            <?php endif; ?>

            <!-- Live Search Bar -->
            <div style="margin-bottom:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 16px; display:flex; align-items:center; gap:12px;">
                <span class="dashicons dashicons-search" style="color:#64748b;"></span>
                <input type="text" id="org_quick_search" placeholder="<?php _e('พิมพ์ค้นหาตำแหน่ง ชื่องาน หรือชื่อบุคลากรเพื่อไฮไลต์ในหน้านี้ทันที...', 'project-core'); ?>" oninput="filterOrg(this.value)" style="flex:1; border:none; background:transparent; font-size:13px; outline:none; box-shadow:none;">
                <span id="org_search_count" style="font-size:12px; color:#64748b;"></span>
            </div>

            <!-- Main Form -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="org_manager_form">
                <?php wp_nonce_field('project_core_save_org_all', 'org_all_nonce'); ?>
                <input type="hidden" name="action" value="project_core_save_organization_all">
                <input type="hidden" name="deleted_leader_ids" id="deleted_leader_ids" value="">
                <input type="hidden" name="deleted_division_ids" id="deleted_division_ids" value="">
                <input type="hidden" name="deleted_job_ids" id="deleted_job_ids" value="">

                <!-- Section 1: Policy Leaders & Committee -->
                <div class="postbox">
                    <div class="postbox-header">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#0047BA;"></span>
                            <h2 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">
                                1. <?php _e('คณะผู้บริหารระดับนโยบาย และ คณะกรรมการสถานศึกษา', 'project-core'); ?>
                            </h2>
                        </div>
                        <div>
                            <button type="button" class="button button-secondary button-small" onclick="addNewLeaderRow()">
                                + <?php _e('เพิ่มตำแหน่งผู้บริหาร/กรรมการ', 'project-core'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="inside" style="padding:0; margin:0;">
                        <table class="wp-list-table widefat fixed striped" id="leaders_table">
                            <thead>
                                <tr>
                                    <th style="width:5%; text-align:center;">#</th>
                                    <th style="width:25%;"><?php _e('ชื่อตำแหน่งในโครงสร้าง', 'project-core'); ?></th>
                                    <th style="width:16%;"><?php _e('ประเภทตำแหน่ง', 'project-core'); ?></th>
                                    <th style="width:30%;"><?php _e('ผู้ดำรงตำแหน่ง (บุคลากร)', 'project-core'); ?></th>
                                    <th style="width:16%;"><?php _e('หมายเหตุ / สถานะพิเศษ', 'project-core'); ?></th>
                                    <th style="width:8%; text-align:center;"><?php _e('จัดการ', 'project-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="leaders_tbody">
                                <?php 
                                $policy_items = $data['leaders'];
                                if ($data['committee']) {
                                    $policy_items[] = $data['committee'];
                                }
                                foreach ($policy_items as $l_idx => $leader): 
                                    $p_id = !empty($leader['personnel']['id']) ? intval($leader['personnel']['id']) : 0;
                                    $lid = esc_attr($leader['id']);
                                    $is_comm = ($leader['type'] === 'committee');
                                ?>
                                    <tr id="leader_row_<?php echo $lid; ?>" class="searchable-row">
                                        <td style="text-align:center; color:#94a3b8; font-weight:700;" class="row-num">
                                            <?php echo ($l_idx + 1); ?>
                                        </td>
                                        <td>
                                            <input type="text" name="leaders[<?php echo $lid; ?>][position]" value="<?php echo esc_attr($leader['position']); ?>" class="regular-text" style="width:96%; font-weight:700;" required>
                                        </td>
                                        <td>
                                            <select name="leaders[<?php echo $lid; ?>][type]" style="width:96%;">
                                                <option value="executive" <?php selected($leader['type'], 'executive'); ?>><?php _e('ผู้บริหารระดับนโยบาย', 'project-core'); ?></option>
                                                <option value="committee" <?php selected($leader['type'], 'committee'); ?>><?php _e('คณะกรรมการสถานศึกษา', 'project-core'); ?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="leaders[<?php echo $lid; ?>][personnel_id]" class="regular-text" style="width:96%;">
                                                <option value="0"><?php _e('-- ยังไม่มีผู้ดำรงตำแหน่ง / ว่าง --', 'project-core'); ?></option>
                                                <?php foreach ($all_personnel as $p): ?>
                                                    <?php 
                                                    $pos_meta = get_post_meta($p->ID, 'personnel_position', true);
                                                    $label = $p->post_title . ($pos_meta ? ' (' . $pos_meta . ')' : '');
                                                    ?>
                                                    <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($p_id, $p->ID); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="leaders[<?php echo $lid; ?>][remark]" value="<?php echo esc_attr($leader['remark']); ?>" class="regular-text" style="width:96%;" placeholder="<?php _e('เช่น รักษาการในตำแหน่ง', 'project-core'); ?>">
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="display:flex; justify-content:center; gap:4px;">
                                                <button type="button" class="action-btn" onclick="moveRowUp(this)" title="<?php _e('เลื่อนขึ้น', 'project-core'); ?>">▲</button>
                                                <button type="button" class="action-btn" onclick="moveRowDown(this)" title="<?php _e('เลื่อนลง', 'project-core'); ?>">▼</button>
                                                <button type="button" class="action-btn action-btn-danger" onclick="deleteLeaderRow(this, '<?php echo $lid; ?>')" title="<?php _e('ลบตำแหน่ง', 'project-core'); ?>" style="color:#dc2626;">✕</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 2: Divisions & Subordinate Jobs -->
                <div style="margin-bottom:25px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <div>
                            <h2 style="font-size:18px; font-weight:800; margin:0; color:#0f172a;">
                                2. <?php _e('ฝ่ายบริหาร และ งานในสังกัด (Divisions & Jobs)', 'project-core'); ?>
                            </h2>
                            <p style="margin:4px 0 0; color:#64748b; font-size:13px;">
                                <?php _e('สามารถเพิ่มฝ่ายใหม่ ลบฝ่าย สลับลำดับ และจัดการงานย่อยภายในแต่ละฝ่ายได้อย่างอิสระ', 'project-core'); ?>
                            </p>
                        </div>
                        <button type="button" onclick="addNewDivisionCard()" class="button button-secondary" style="font-weight:700;">
                            + <?php _e('เพิ่มฝ่ายใหม่ (Add Division)', 'project-core'); ?>
                        </button>
                    </div>

                    <div id="divisions_container">
                        <?php foreach ($data['divisions'] as $d_idx => $div): 
                            $div_id = esc_attr($div['id']);
                            $head_p_id = !empty($div['head']['personnel']['id']) ? intval($div['head']['personnel']['id']) : 0;
                            $head_pos_id = !empty($div['head']['id']) ? intval($div['head']['id']) : 0;
                            $div_color = !empty($div['color']) ? $div['color'] : '#278bb4';
                        ?>
                            <div class="org-div-card" id="div_card_<?php echo $div_id; ?>" style="border-left: 6px solid <?php echo esc_attr($div_color); ?>;">
                                <!-- Division Header -->
                                <div class="org-div-header" style="background:#fafbfc;">
                                    <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:300px;">
                                        <input type="color" name="divisions[<?php echo $div_id; ?>][color]" value="<?php echo esc_attr($div_color); ?>" title="<?php _e('เลือกสีประจำฝ่าย', 'project-core'); ?>" style="width:34px; height:34px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" onchange="this.closest('.org-div-card').style.borderLeftColor = this.value;">
                                        <div>
                                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">
                                                <?php _e('ชื่อฝ่ายงาน', 'project-core'); ?> (<span class="div-num">ฝ่ายที่ <?php echo ($d_idx + 1); ?></span>)
                                            </div>
                                            <input type="text" name="divisions[<?php echo $div_id; ?>][title]" value="<?php echo esc_attr($div['title']); ?>" style="font-size:16px; font-weight:700; color:#0f172a; width:300px; padding:4px 10px;" required>
                                        </div>
                                    </div>

                                    <!-- Division Head -->
                                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                        <div>
                                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">
                                                👤 <?php _e('ผู้บริหารฝ่าย', 'project-core'); ?>
                                            </div>
                                            <input type="hidden" name="divisions[<?php echo $div_id; ?>][head_pos_id]" value="<?php echo esc_attr($head_pos_id); ?>">
                                            <select name="divisions[<?php echo $div_id; ?>][head_personnel_id]" style="min-width:240px;">
                                                <option value="0"><?php _e('-- ยังไม่มีผู้บริหารฝ่าย --', 'project-core'); ?></option>
                                                <?php foreach ($all_personnel as $p): ?>
                                                    <?php 
                                                    $pos_meta = get_post_meta($p->ID, 'personnel_position', true);
                                                    $label = $p->post_title . ($pos_meta ? ' (' . $pos_meta . ')' : '');
                                                    ?>
                                                    <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($head_p_id, $p->ID); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">
                                                <?php _e('หมายเหตุ', 'project-core'); ?>
                                            </div>
                                            <input type="text" name="divisions[<?php echo $div_id; ?>][head_remark]" value="<?php echo esc_attr($div['head']['remark']); ?>" placeholder="<?php _e('เช่น รองผู้อำนวยการ', 'project-core'); ?>" style="width:140px;">
                                        </div>

                                        <div style="display:flex; gap:4px; margin-top:16px;">
                                            <button type="button" class="action-btn" onclick="moveDivUp(this)" title="<?php _e('เลื่อนฝ่ายขึ้น', 'project-core'); ?>">▲</button>
                                            <button type="button" class="action-btn" onclick="moveDivDown(this)" title="<?php _e('เลื่อนฝ่ายลง', 'project-core'); ?>">▼</button>
                                            <button type="button" class="action-btn action-btn-danger" onclick="deleteDivisionCard(this, '<?php echo $div_id; ?>')" title="<?php _e('ลบฝ่ายนี้', 'project-core'); ?>" style="color:#dc2626;">🗑️</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subordinate Jobs Table -->
                                <div class="inside" style="padding:0; margin:0;">
                                    <table class="wp-list-table widefat fixed striped" id="jobs_table_<?php echo $div_id; ?>">
                                        <thead>
                                            <tr>
                                                <th style="width:6%; text-align:center;">#</th>
                                                <th style="width:36%;"><?php _e('ชื่องานในสังกัด', 'project-core'); ?></th>
                                                <th style="width:34%;"><?php _e('ผู้รับผิดชอบงาน (บุคลากร)', 'project-core'); ?></th>
                                                <th style="width:16%;"><?php _e('หมายเหตุ', 'project-core'); ?></th>
                                                <th style="width:8%; text-align:center;"><?php _e('จัดการ', 'project-core'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="jobs_tbody_<?php echo $div_id; ?>">
                                            <?php foreach ($div['jobs'] as $j_idx => $job): 
                                                $jid = esc_attr($job['id']);
                                                $job_p_id = !empty($job['personnel']['id']) ? intval($job['personnel']['id']) : 0;
                                            ?>
                                                <tr id="job_row_<?php echo $jid; ?>" class="searchable-row">
                                                    <td style="text-align:center; color:#94a3b8; font-weight:700;" class="job-num">
                                                        <?php echo ($j_idx + 1); ?>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="divisions[<?php echo $div_id; ?>][jobs][<?php echo $jid; ?>][title]" value="<?php echo esc_attr($job['title']); ?>" class="regular-text" style="width:96%; font-weight:600;" required>
                                                    </td>
                                                    <td>
                                                        <select name="divisions[<?php echo $div_id; ?>][jobs][<?php echo $jid; ?>][personnel_id]" class="regular-text" style="width:96%;">
                                                            <option value="0"><?php _e('-- ยังไม่มีผู้รับผิดชอบ / ว่าง --', 'project-core'); ?></option>
                                                            <?php foreach ($all_personnel as $p): ?>
                                                                <?php 
                                                                $pos_meta = get_post_meta($p->ID, 'personnel_position', true);
                                                                $label = $p->post_title . ($pos_meta ? ' (' . $pos_meta . ')' : '');
                                                                ?>
                                                                <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($job_p_id, $p->ID); ?>>
                                                                    <?php echo esc_html($label); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="divisions[<?php echo $div_id; ?>][jobs][<?php echo $jid; ?>][remark]" value="<?php echo esc_attr($job['remark']); ?>" class="regular-text" style="width:96%;" placeholder="<?php _e('เช่น รักษาการ', 'project-core'); ?>">
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <div style="display:flex; justify-content:center; gap:4px;">
                                                            <button type="button" class="action-btn" onclick="moveRowUp(this)" title="<?php _e('เลื่อนขึ้น', 'project-core'); ?>">▲</button>
                                                            <button type="button" class="action-btn" onclick="moveRowDown(this)" title="<?php _e('เลื่อนลง', 'project-core'); ?>">▼</button>
                                                            <button type="button" class="action-btn action-btn-danger" onclick="deleteJobRow(this, '<?php echo $jid; ?>')" title="<?php _e('ลบงานนี้', 'project-core'); ?>" style="color:#dc2626;">✕</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Card Footer: Add Job Button -->
                                <div style="padding:10px 18px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                                    <button type="button" class="button button-secondary button-small" onclick="addNewJobRow('<?php echo $div_id; ?>')">
                                        + <?php _e('เพิ่มงานในฝ่ายนี้', 'project-core'); ?>
                                    </button>
                                    <span style="font-size:12px; color:#94a3b8;">
                                        <?php echo count($div['jobs']); ?> <?php _e('งานในสังกัด', 'project-core'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="text-align:center; margin-top:20px;">
                        <button type="button" onclick="addNewDivisionCard()" class="button button-secondary button-large" style="font-weight:700; padding:4px 24px;">
                            + <?php _e('เพิ่มฝ่ายใหม่ (Add Division)', 'project-core'); ?>
                        </button>
                    </div>
                </div>

                <!-- Sticky Bottom Bar -->
                <div style="position:sticky; bottom:0; background:#ffffff; border-top:2px solid #0047BA; padding:14px 24px; margin:30px -20px -20px -20px; box-shadow:0 -4px 16px rgba(0,0,0,0.08); z-index:99; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div style="font-size:13px; color:#475569; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-yes-alt" style="color:#10b981; font-size:20px; width:20px; height:20px;"></span>
                        <span>แก้ไขเสร็จแล้วอย่าลืมกดปุ่ม <strong>"บันทึกข้อมูลโครงสร้างทั้งหมด"</strong> เพื่อให้หน้าเว็บ Astro อัปเดตทันที</span>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <a href="http://localhost:4321/about/organization/" target="_blank" class="button button-large" style="font-weight:600; color:#0047BA;">
                            👁️ <?php _e('ดูหน้าเว็บจริง', 'project-core'); ?>
                        </a>
                        <button type="submit" class="button button-primary button-hero" style="font-size:15px; padding:6px 30px; height:auto; line-height:28px; font-weight:800; background:#0047BA; border-color:#003893;">
                            💾 <?php _e('บันทึกข้อมูลโครงสร้างทั้งหมด', 'project-core'); ?>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Reset / Restore Default Data Section -->
            <div style="margin-top:50px; padding:18px 22px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <strong style="color:#334155; font-size:13px;"><?php _e('ต้องการติดตั้งหรือกู้คืนโครงสร้างเริ่มต้น?', 'project-core'); ?></strong>
                    <p style="margin:2px 0 0; color:#64748b; font-size:12px;"><?php _e('ระบบจะรีเซ็ตข้อมูล 3 ผู้บริหาร, คณะกรรมการ, 4 ฝ่ายหลัก และ 23 งานมาตรฐานให้โดยอัตโนมัติ', 'project-core'); ?></p>
                </div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('คำเตือน: คุณต้องการรีเซ็ตโครงสร้างเป็นค่าเริ่มต้น 4 ฝ่ายและ 23 งานหรือไม่?');">
                    <?php wp_nonce_field('project_core_seed_org', 'seed_nonce'); ?>
                    <input type="hidden" name="action" value="project_core_seed_organization">
                    <button type="submit" class="button button-secondary">
                        ⚡ <?php _e('ติดตั้ง/รีเซ็ตข้อมูลเริ่มต้น (4 ฝ่าย 23 งาน)', 'project-core'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Dynamic JS Helpers for Adding, Moving & Deleting -->
        <script>
        (function() {
            var personnelOptionsHtml = '<option value="0"><?php echo esc_js(__('-- ยังไม่มีผู้รับผิดชอบ / ว่าง --', 'project-core')); ?></option>';
            <?php foreach ($all_personnel as $p): ?>
                <?php 
                $pos_meta = get_post_meta($p->ID, 'personnel_position', true);
                $label = $p->post_title . ($pos_meta ? ' (' . $pos_meta . ')' : '');
                ?>
                personnelOptionsHtml += '<option value="<?php echo esc_js($p->ID); ?>"><?php echo esc_js($label); ?></option>';
            <?php endforeach; ?>

            // Reindex row numbers
            function updateRowNumbers(tbody, className) {
                var rows = tbody.querySelectorAll('tr');
                rows.forEach(function(r, idx) {
                    var cell = r.querySelector('.' + className);
                    if (cell) cell.textContent = (idx + 1);
                });
            }

            function updateDivisionNumbers() {
                var cards = document.querySelectorAll('.org-div-card');
                cards.forEach(function(c, idx) {
                    var num = c.querySelector('.div-num');
                    if (num) num.textContent = 'ฝ่ายที่ ' + (idx + 1);
                });
            }

            // Move Table Row Up
            window.moveRowUp = function(btn) {
                var row = btn.closest('tr');
                var prev = row.previousElementSibling;
                if (prev) {
                    row.parentNode.insertBefore(row, prev);
                    var tbody = row.closest('tbody');
                    if (tbody.id === 'leaders_tbody') {
                        updateRowNumbers(tbody, 'row-num');
                    } else {
                        updateRowNumbers(tbody, 'job-num');
                    }
                }
            };

            // Move Table Row Down
            window.moveRowDown = function(btn) {
                var row = btn.closest('tr');
                var next = row.nextElementSibling;
                if (next) {
                    row.parentNode.insertBefore(next, row);
                    var tbody = row.closest('tbody');
                    if (tbody.id === 'leaders_tbody') {
                        updateRowNumbers(tbody, 'row-num');
                    } else {
                        updateRowNumbers(tbody, 'job-num');
                    }
                }
            };

            // Move Division Card Up
            window.moveDivUp = function(btn) {
                var card = btn.closest('.org-div-card');
                var prev = card.previousElementSibling;
                if (prev) {
                    card.parentNode.insertBefore(card, prev);
                    updateDivisionNumbers();
                }
            };

            // Move Division Card Down
            window.moveDivDown = function(btn) {
                var card = btn.closest('.org-div-card');
                var next = card.nextElementSibling;
                if (next) {
                    card.parentNode.insertBefore(next, card);
                    updateDivisionNumbers();
                }
            };

            // Add New Leader
            window.addNewLeaderRow = function() {
                var tbody = document.getElementById('leaders_tbody');
                if (!tbody) return;
                var timestamp = Date.now();
                var count = tbody.querySelectorAll('tr').length + 1;
                var tr = document.createElement('tr');
                tr.id = 'leader_row_new_' + timestamp;
                tr.className = 'searchable-row';

                tr.innerHTML = 
                    '<td style="text-align:center; color:#10b981; font-weight:700;" class="row-num">' + count + '</td>' +
                    '<td><input type="text" name="leaders[new_' + timestamp + '][position]" value="" placeholder="<?php echo esc_js(__('ระบุชื่อตำแหน่งผู้บริหาร...', 'project-core')); ?>" class="regular-text" style="width:96%; font-weight:700; border-color:#10b981;" required></td>' +
                    '<td>' +
                        '<select name="leaders[new_' + timestamp + '][type]" style="width:96%;">' +
                            '<option value="executive"><?php echo esc_js(__('ผู้บริหารระดับนโยบาย', 'project-core')); ?></option>' +
                            '<option value="committee"><?php echo esc_js(__('คณะกรรมการสถานศึกษา', 'project-core')); ?></option>' +
                        '</select>' +
                    '</td>' +
                    '<td><select name="leaders[new_' + timestamp + '][personnel_id]" class="regular-text" style="width:96%;">' + personnelOptionsHtml + '</select></td>' +
                    '<td><input type="text" name="leaders[new_' + timestamp + '][remark]" value="" class="regular-text" style="width:96%;" placeholder="<?php echo esc_js(__('เช่น รักษาการในตำแหน่ง', 'project-core')); ?>"></td>' +
                    '<td style="text-align:center;">' +
                        '<div style="display:flex; justify-content:center; gap:4px;">' +
                            '<button type="button" class="action-btn" onclick="moveRowUp(this)">▲</button>' +
                            '<button type="button" class="action-btn" onclick="moveRowDown(this)">▼</button>' +
                            '<button type="button" class="action-btn action-btn-danger" onclick="deleteLeaderRow(this, \'\')" style="color:#dc2626;">✕</button>' +
                        '</div>' +
                    '</td>';

                tbody.appendChild(tr);
                var input = tr.querySelector('input[type="text"]');
                if (input) input.focus();
            };

            // Delete Leader
            window.deleteLeaderRow = function(btn, leaderId) {
                if (!confirm('<?php echo esc_js(__('ยืนยันที่จะลบตำแหน่งนี้หรือไม่?', 'project-core')); ?>')) return;
                if (leaderId && !leaderId.toString().startsWith('new_') && !leaderId.toString().startsWith('mock_')) {
                    var delInput = document.getElementById('deleted_leader_ids');
                    if (delInput) {
                        var arr = delInput.value ? delInput.value.split(',') : [];
                        arr.push(leaderId);
                        delInput.value = arr.join(',');
                    }
                }
                var row = btn.closest('tr');
                if (row) {
                    var tbody = row.closest('tbody');
                    row.remove();
                    updateRowNumbers(tbody, 'row-num');
                }
            };

            // Add New Division Card
            window.addNewDivisionCard = function() {
                var container = document.getElementById('divisions_container');
                if (!container) return;
                var timestamp = Date.now();
                var divCount = container.querySelectorAll('.org-div-card').length + 1;
                var divId = 'new_' + timestamp;

                var card = document.createElement('div');
                card.className = 'org-div-card';
                card.id = 'div_card_' + divId;
                card.style.borderLeft = '6px solid #10b981';

                card.innerHTML = 
                    '<div class="org-div-header" style="background:#f0fdf4;">' +
                        '<div style="display:flex; align-items:center; gap:12px; flex:1; min-width:300px;">' +
                            '<input type="color" name="divisions[' + divId + '][color]" value="#10b981" title="<?php echo esc_js(__('เลือกสีประจำฝ่าย', 'project-core')); ?>" style="width:34px; height:34px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" onchange="this.closest(\'.org-div-card\').style.borderLeftColor = this.value;">' +
                            '<div>' +
                                '<div style="font-size:11px; text-transform:uppercase; color:#059669; font-weight:700;">' +
                                    '<?php echo esc_js(__('ชื่อฝ่ายงานใหม่', 'project-core')); ?> (<span class="div-num">ฝ่ายที่ ' + divCount + '</span>)' +
                                '</div>' +
                                '<input type="text" name="divisions[' + divId + '][title]" value="" placeholder="<?php echo esc_js(__('เช่น ฝ่ายวิจัยและพัฒนานวัตกรรม...', 'project-core')); ?>" style="font-size:16px; font-weight:700; color:#0f172a; width:300px; padding:4px 10px; border-color:#10b981;" required>' +
                            '</div>' +
                        '</div>' +
                        '<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">' +
                            '<div>' +
                                '<div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;">👤 <?php echo esc_js(__('ผู้บริหารฝ่าย', 'project-core')); ?></div>' +
                                '<input type="hidden" name="divisions[' + divId + '][head_pos_id]" value="0">' +
                                '<select name="divisions[' + divId + '][head_personnel_id]" style="min-width:240px;">' + personnelOptionsHtml + '</select>' +
                            '</div>' +
                            '<div>' +
                                '<div style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700;"><?php echo esc_js(__('หมายเหตุ', 'project-core')); ?></div>' +
                                '<input type="text" name="divisions[' + divId + '][head_remark]" value="" placeholder="<?php echo esc_js(__('เช่น รองผู้อำนวยการ', 'project-core')); ?>" style="width:140px;">' +
                            '</div>' +
                            '<div style="display:flex; gap:4px; margin-top:16px;">' +
                                '<button type="button" class="action-btn" onclick="moveDivUp(this)">▲</button>' +
                                '<button type="button" class="action-btn" onclick="moveDivDown(this)">▼</button>' +
                                '<button type="button" class="action-btn action-btn-danger" onclick="deleteDivisionCard(this, \'\')" style="color:#dc2626;">🗑️</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="inside" style="padding:0; margin:0;">' +
                        '<table class="wp-list-table widefat fixed striped" id="jobs_table_' + divId + '">' +
                            '<thead>' +
                                '<tr>' +
                                    '<th style="width:6%; text-align:center;">#</th>' +
                                    '<th style="width:36%;"><?php echo esc_js(__('ชื่องานในสังกัด', 'project-core')); ?></th>' +
                                    '<th style="width:34%;"><?php echo esc_js(__('ผู้รับผิดชอบงาน (บุคลากร)', 'project-core')); ?></th>' +
                                    '<th style="width:16%;"><?php echo esc_js(__('หมายเหตุ', 'project-core')); ?></th>' +
                                    '<th style="width:8%; text-align:center;"><?php echo esc_js(__('จัดการ', 'project-core')); ?></th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody id="jobs_tbody_' + divId + '"></tbody>' +
                        '</table>' +
                    '</div>' +
                    '<div style="padding:10px 18px; background:#f8fafc; border-top:1px solid #f1f5f9;">' +
                        '<button type="button" class="button button-secondary button-small" onclick="addNewJobRow(\'' + divId + '\')">' +
                            '+ <?php echo esc_js(__('เพิ่มงานในฝ่ายนี้', 'project-core')); ?>' +
                        '</button>' +
                    '</div>';

                container.appendChild(card);
                updateDivisionNumbers();
                var input = card.querySelector('input[type="text"]');
                if (input) input.focus();
            };

            // Delete Division Card
            window.deleteDivisionCard = function(btn, divId) {
                if (!confirm('<?php echo esc_js(__('คำเตือน: คุณต้องการลบฝ่ายนี้พร้อมงานทั้งหมดในสังกัดหรือไม่?', 'project-core')); ?>')) return;
                if (divId && !divId.toString().startsWith('new_') && !divId.toString().startsWith('div_')) {
                    var delInput = document.getElementById('deleted_division_ids');
                    if (delInput) {
                        var arr = delInput.value ? delInput.value.split(',') : [];
                        arr.push(divId);
                        delInput.value = arr.join(',');
                    }
                }
                var card = btn.closest('.org-div-card');
                if (card) {
                    card.remove();
                    updateDivisionNumbers();
                }
            };

            // Add New Job in Division
            window.addNewJobRow = function(divId) {
                var tbody = document.getElementById('jobs_tbody_' + divId);
                if (!tbody) return;

                var timestamp = Date.now();
                var rowCount = tbody.querySelectorAll('tr').length + 1;
                var newRow = document.createElement('tr');
                newRow.id = 'job_row_new_' + timestamp;
                newRow.className = 'searchable-row';

                newRow.innerHTML = 
                    '<td style="text-align:center; color:#10b981; font-weight:700;" class="job-num">' + rowCount + '</td>' +
                    '<td>' +
                        '<input type="text" name="divisions[' + divId + '][jobs][new_' + timestamp + '][title]" value="" placeholder="<?php echo esc_js(__('ระบุชื่องานใหม่...', 'project-core')); ?>" class="regular-text" style="width:96%; font-weight:600; border-color:#10b981;" required>' +
                    '</td>' +
                    '<td>' +
                        '<select name="divisions[' + divId + '][jobs][new_' + timestamp + '][personnel_id]" class="regular-text" style="width:96%;">' +
                            personnelOptionsHtml +
                        '</select>' +
                    '</td>' +
                    '<td>' +
                        '<input type="text" name="divisions[' + divId + '][jobs][new_' + timestamp + '][remark]" value="" class="regular-text" style="width:96%;" placeholder="<?php echo esc_js(__('เช่น รักษาการ', 'project-core')); ?>">' +
                    '</td>' +
                    '<td style="text-align:center;">' +
                        '<div style="display:flex; justify-content:center; gap:4px;">' +
                            '<button type="button" class="action-btn" onclick="moveRowUp(this)">▲</button>' +
                            '<button type="button" class="action-btn" onclick="moveRowDown(this)">▼</button>' +
                            '<button type="button" class="action-btn action-btn-danger" onclick="deleteJobRow(this, \'\')" style="color:#dc2626;">✕</button>' +
                        '</div>' +
                    '</td>';

                tbody.appendChild(newRow);
                updateRowNumbers(tbody, 'job-num');
                var input = newRow.querySelector('input[type="text"]');
                if (input) input.focus();
            };

            // Delete Job
            window.deleteJobRow = function(btn, jobId) {
                if (!confirm('<?php echo esc_js(__('ยืนยันที่จะลบงานนี้หรือไม่?', 'project-core')); ?>')) return;

                if (jobId && !jobId.toString().startsWith('new_') && !jobId.toString().startsWith('job_')) {
                    var deletedInput = document.getElementById('deleted_job_ids');
                    if (deletedInput) {
                        var current = deletedInput.value ? deletedInput.value.split(',') : [];
                        current.push(jobId);
                        deletedInput.value = current.join(',');
                    }
                }

                var row = btn.closest('tr');
                if (row) {
                    var tbody = row.closest('tbody');
                    row.remove();
                    if (tbody) updateRowNumbers(tbody, 'job-num');
                }
            };

            // Live Filter
            window.filterOrg = function(val) {
                val = val.trim().toLowerCase();
                var rows = document.querySelectorAll('.searchable-row');
                var matchCount = 0;

                rows.forEach(function(r) {
                    if (!val) {
                        r.style.display = '';
                        r.style.backgroundColor = '';
                        return;
                    }

                    var text = '';
                    var inputs = r.querySelectorAll('input[type="text"]');
                    inputs.forEach(function(i) { text += ' ' + i.value; });
                    var selects = r.querySelectorAll('select');
                    selects.forEach(function(s) {
                        var opt = s.options[s.selectedIndex];
                        if (opt) text += ' ' + opt.text;
                    });

                    if (text.toLowerCase().indexOf(val) !== -1) {
                        r.style.display = '';
                        r.style.backgroundColor = '#fef9c3';
                        matchCount++;
                    } else {
                        r.style.display = 'none';
                        r.style.backgroundColor = '';
                    }
                });

                var countLabel = document.getElementById('org_search_count');
                if (countLabel) {
                    countLabel.textContent = val ? ('พบ ' + matchCount + ' รายการ') : '';
                }
            };
        })();
        </script>
        <?php
    }

    /**
     * Handle Save All Organization Structure (Full CRUD)
     */
    public function handle_save_organization_all() {
        if (!current_user_can('manage_options') || !check_admin_referer('project_core_save_org_all', 'org_all_nonce')) {
            wp_die('Unauthorized');
        }

        // 1. Process Deleted Leaders
        if (!empty($_POST['deleted_leader_ids'])) {
            $del_leaders = array_filter(array_map('intval', explode(',', $_POST['deleted_leader_ids'])));
            foreach ($del_leaders as $del_lid) {
                if ($del_lid > 0) {
                    wp_delete_post($del_lid, true);
                }
            }
        }

        // 2. Save Policy Leaders & Committee
        if (!empty($_POST['leaders']) && is_array($_POST['leaders'])) {
            $leader_order = 1;
            foreach ($_POST['leaders'] as $leader_key => $ldata) {
                $pos_title  = isset($ldata['position']) ? sanitize_text_field($ldata['position']) : '';
                if (empty($pos_title)) continue;

                $person_id = isset($ldata['personnel_id']) ? intval($ldata['personnel_id']) : 0;
                $remark    = isset($ldata['remark']) ? sanitize_text_field($ldata['remark']) : '';
                $type      = isset($ldata['type']) && in_array($ldata['type'], array('executive', 'committee')) ? $ldata['type'] : 'executive';

                if (is_numeric($leader_key) && intval($leader_key) > 0) {
                    $leader_id = intval($leader_key);
                    wp_update_post(array(
                        'ID'         => $leader_id,
                        'post_title' => $pos_title,
                        'menu_order' => $leader_order,
                    ));
                } else {
                    $leader_id = wp_insert_post(array(
                        'post_title'  => $pos_title,
                        'post_type'   => 'org_position',
                        'post_status' => 'publish',
                        'menu_order'  => $leader_order,
                    ));
                }

                if ($leader_id && !is_wp_error($leader_id)) {
                    update_post_meta($leader_id, '_org_position_type', $type);
                    update_post_meta($leader_id, '_position_type', $type);
                    update_post_meta($leader_id, '_org_personnel_id', $person_id);
                    update_post_meta($leader_id, '_personnel_id', $person_id);
                    update_post_meta($leader_id, '_org_position_remark', $remark);
                    update_post_meta($leader_id, '_org_position_order', $leader_order);
                    update_post_meta($leader_id, '_position_order', $leader_order);
                    update_post_meta($leader_id, '_org_position_status', ($person_id > 0) ? 'active' : 'vacant');
                }
                $leader_order++;
            }
        }

        // 3. Process Deleted Divisions (and cascade to subordinate jobs)
        if (!empty($_POST['deleted_division_ids'])) {
            $del_divisions = array_filter(array_map('intval', explode(',', $_POST['deleted_division_ids'])));
            foreach ($del_divisions as $del_div_id) {
                if (!$del_div_id) continue;

                // Find subordinate jobs
                $sub_jobs = get_posts(array(
                    'post_type'      => 'org_unit',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'meta_query'     => array(
                        array('key' => '_org_unit_parent', 'value' => $del_div_id),
                    ),
                ));
                foreach ($sub_jobs as $sj) {
                    // Delete associated job positions
                    $job_positions = get_posts(array(
                        'post_type'      => 'org_position',
                        'posts_per_page' => -1,
                        'post_status'    => 'any',
                        'meta_query'     => array(
                            array('key' => '_org_unit_id', 'value' => $sj->ID),
                        ),
                    ));
                    foreach ($job_positions as $jp) {
                        wp_delete_post($jp->ID, true);
                    }
                    wp_delete_post($sj->ID, true);
                }

                // Delete division head positions
                $head_positions = get_posts(array(
                    'post_type'      => 'org_position',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'meta_query'     => array(
                        array('key' => '_org_unit_id', 'value' => $del_div_id),
                    ),
                ));
                foreach ($head_positions as $hp) {
                    wp_delete_post($hp->ID, true);
                }

                // Delete the division unit itself
                wp_delete_post($del_div_id, true);
            }
        }

        // 4. Process Deleted Jobs
        if (!empty($_POST['deleted_job_ids'])) {
            $del_jobs = array_filter(array_map('intval', explode(',', $_POST['deleted_job_ids'])));
            foreach ($del_jobs as $del_jid) {
                if (!$del_jid) continue;
                $positions = get_posts(array(
                    'post_type'      => 'org_position',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'meta_query'     => array(
                        array('key' => '_org_unit_id', 'value' => $del_jid),
                    ),
                ));
                foreach ($positions as $p) {
                    wp_delete_post($p->ID, true);
                }
                wp_delete_post($del_jid, true);
            }
        }

        // 5. Save Divisions & Jobs
        if (!empty($_POST['divisions']) && is_array($_POST['divisions'])) {
            $div_order = 1;
            foreach ($_POST['divisions'] as $div_key => $ddata) {
                $div_title = isset($ddata['title']) ? sanitize_text_field($ddata['title']) : '';
                if (empty($div_title)) continue;

                $div_color = isset($ddata['color']) ? sanitize_hex_color($ddata['color']) : '#278bb4';

                if (is_numeric($div_key) && intval($div_key) > 0) {
                    $div_id = intval($div_key);
                    wp_update_post(array(
                        'ID'         => $div_id,
                        'post_title' => $div_title,
                        'menu_order' => $div_order,
                    ));
                } else {
                    $div_id = wp_insert_post(array(
                        'post_title'  => $div_title,
                        'post_type'   => 'org_unit',
                        'post_status' => 'publish',
                        'menu_order'  => $div_order,
                    ));
                }

                if (!$div_id || is_wp_error($div_id)) continue;

                update_post_meta($div_id, '_org_unit_type', 'division');
                update_post_meta($div_id, '_unit_type', 'division');
                update_post_meta($div_id, '_org_unit_color', $div_color);
                update_post_meta($div_id, '_org_unit_order', $div_order);
                update_post_meta($div_id, '_unit_order', $div_order);

                // Division Head Position
                $head_pos_id = isset($ddata['head_pos_id']) ? intval($ddata['head_pos_id']) : 0;
                $head_person = isset($ddata['head_personnel_id']) ? intval($ddata['head_personnel_id']) : 0;
                $head_remark = isset($ddata['head_remark']) ? sanitize_text_field($ddata['head_remark']) : '';

                if ($head_pos_id > 0) {
                    wp_update_post(array(
                        'ID'         => $head_pos_id,
                        'post_title' => 'รองผู้อำนวยการ' . $div_title,
                    ));
                } else {
                    $existing_head = get_posts(array(
                        'post_type'      => 'org_position',
                        'posts_per_page' => 1,
                        'post_status'    => 'any',
                        'meta_query'     => array(
                            array('key' => '_org_unit_id', 'value' => $div_id),
                            array('key' => '_org_position_type', 'value' => 'division_head'),
                        ),
                    ));
                    if (!empty($existing_head)) {
                        $head_pos_id = $existing_head[0]->ID;
                        wp_update_post(array(
                            'ID'         => $head_pos_id,
                            'post_title' => 'รองผู้อำนวยการ' . $div_title,
                        ));
                    } else {
                        $head_pos_id = wp_insert_post(array(
                            'post_title'  => 'รองผู้อำนวยการ' . $div_title,
                            'post_type'   => 'org_position',
                            'post_status' => 'publish',
                            'menu_order'  => $div_order,
                        ));
                    }
                }

                if ($head_pos_id && !is_wp_error($head_pos_id)) {
                    update_post_meta($head_pos_id, '_org_position_type', 'division_head');
                    update_post_meta($head_pos_id, '_position_type', 'division_head');
                    update_post_meta($head_pos_id, '_org_unit_id', $div_id);
                    update_post_meta($head_pos_id, '_unit_id', $div_id);
                    update_post_meta($head_pos_id, '_org_personnel_id', $head_person);
                    update_post_meta($head_pos_id, '_personnel_id', $head_person);
                    update_post_meta($head_pos_id, '_org_position_remark', $head_remark);
                    update_post_meta($head_pos_id, '_org_position_status', ($head_person > 0) ? 'active' : 'vacant');
                }

                // Subordinate Jobs
                if (!empty($ddata['jobs']) && is_array($ddata['jobs'])) {
                    $job_order = 1;
                    foreach ($ddata['jobs'] as $job_key => $jdata) {
                        $job_title = isset($jdata['title']) ? sanitize_text_field($jdata['title']) : '';
                        if (empty($job_title)) continue;

                        $job_person = isset($jdata['personnel_id']) ? intval($jdata['personnel_id']) : 0;
                        $job_remark = isset($jdata['remark']) ? sanitize_text_field($jdata['remark']) : '';

                        if (is_numeric($job_key) && intval($job_key) > 0) {
                            $job_unit_id = intval($job_key);
                            wp_update_post(array(
                                'ID'         => $job_unit_id,
                                'post_title' => $job_title,
                                'menu_order' => $job_order,
                            ));
                        } else {
                            $job_unit_id = wp_insert_post(array(
                                'post_title'  => $job_title,
                                'post_type'   => 'org_unit',
                                'post_status' => 'publish',
                                'menu_order'  => $job_order,
                            ));
                        }

                        if ($job_unit_id && !is_wp_error($job_unit_id)) {
                            update_post_meta($job_unit_id, '_org_unit_type', 'job');
                            update_post_meta($job_unit_id, '_unit_type', 'job');
                            update_post_meta($job_unit_id, '_org_unit_parent', $div_id);
                            update_post_meta($job_unit_id, '_parent_unit', $div_id);
                            update_post_meta($job_unit_id, '_org_unit_order', $job_order);
                            update_post_meta($job_unit_id, '_unit_order', $job_order);

                            // Find or create associated Org Position
                            $existing_pos = get_posts(array(
                                'post_type'      => 'org_position',
                                'posts_per_page' => 1,
                                'post_status'    => 'any',
                                'meta_query'     => array(
                                    array('key' => '_org_unit_id', 'value' => $job_unit_id),
                                ),
                            ));

                            if (!empty($existing_pos)) {
                                $jpos_id = $existing_pos[0]->ID;
                                wp_update_post(array(
                                    'ID'         => $jpos_id,
                                    'post_title' => 'หัวหน้า' . $job_title,
                                    'menu_order' => $job_order,
                                ));
                            } else {
                                $jpos_id = wp_insert_post(array(
                                    'post_title'  => 'หัวหน้า' . $job_title,
                                    'post_type'   => 'org_position',
                                    'post_status' => 'publish',
                                    'menu_order'  => $job_order,
                                ));
                            }

                            if ($jpos_id && !is_wp_error($jpos_id)) {
                                update_post_meta($jpos_id, '_org_position_type', 'job_head');
                                update_post_meta($jpos_id, '_position_type', 'job_head');
                                update_post_meta($jpos_id, '_org_unit_id', $job_unit_id);
                                update_post_meta($jpos_id, '_unit_id', $job_unit_id);
                                update_post_meta($jpos_id, '_org_personnel_id', $job_person);
                                update_post_meta($jpos_id, '_personnel_id', $job_person);
                                update_post_meta($jpos_id, '_org_position_remark', $job_remark);
                                update_post_meta($jpos_id, '_org_position_order', $job_order);
                                update_post_meta($jpos_id, '_position_order', $job_order);
                                update_post_meta($jpos_id, '_org_position_status', ($job_person > 0) ? 'active' : 'vacant');
                            }
                        }
                        $job_order++;
                    }
                }

                $div_order++;
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=project-core-organization&saved=1'));
        exit;
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
        ));

        // Sort leaders by position order
        usort($leader_posts, function($a, $b) {
            $oa = intval(get_post_meta($a->ID, '_org_position_order', true) ?: $a->menu_order);
            $ob = intval(get_post_meta($b->ID, '_org_position_order', true) ?: $b->menu_order);
            return $oa <=> $ob;
        });

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

        // Default mock if none in DB
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
        ));

        // Sort divisions by order
        usort($division_posts, function($a, $b) {
            $oa = intval(get_post_meta($a->ID, '_org_unit_order', true) ?: $a->menu_order);
            $ob = intval(get_post_meta($b->ID, '_org_unit_order', true) ?: $b->menu_order);
            return $oa <=> $ob;
        });

        $divisions = array();

        foreach ($division_posts as $div) {
            $div_id    = $div->ID;
            $code      = get_post_meta($div_id, '_org_unit_code', true);
            $color     = get_post_meta($div_id, '_org_unit_color', true) ?: '#278bb4';
            $order     = intval(get_post_meta($div_id, '_org_unit_order', true) ?: $div->menu_order);
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
                'remark'    => '',
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
            ));

            // Sort jobs by order
            usort($job_unit_posts, function($a, $b) {
                $oa = intval(get_post_meta($a->ID, '_org_unit_order', true) ?: $a->menu_order);
                $ob = intval(get_post_meta($b->ID, '_org_unit_order', true) ?: $b->menu_order);
                return $oa <=> $ob;
            });

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
