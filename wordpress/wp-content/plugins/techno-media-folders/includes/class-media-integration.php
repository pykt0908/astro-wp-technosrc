<?php
if (!defined('ABSPATH')) {
    exit;
}

class Techno_MF_Media_Integration {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_filter('attachment_fields_to_edit', array(__CLASS__, 'add_attachment_folder_field'), 10, 2);
        add_filter('attachment_fields_to_save', array(__CLASS__, 'save_attachment_folder_field'), 10, 2);
    }

    public static function enqueue_assets($hook) {
        // Enqueue on upload.php, post.php, post-new.php, or whenever media is used
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_upload = ($hook === 'upload.php' || ($screen && $screen->id === 'upload'));
        $is_post = in_array($hook, array('post.php', 'post-new.php'), true);

        // Always enqueue on upload.php, or if wp_enqueue_media is called
        wp_enqueue_style(
            'techno-media-folders-css',
            TECHNO_MF_URL . 'assets/css/media-folders.css',
            array('dashicons'),
            TECHNO_MF_VERSION
        );

        wp_enqueue_script(
            'techno-media-folders-js',
            TECHNO_MF_URL . 'assets/js/media-folders.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            TECHNO_MF_VERSION,
            true
        );

        wp_localize_script('techno-media-folders-js', 'technoMFData', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce(Techno_MF_Ajax_Handler::NONCE_ACTION),
            'isUpload'  => $is_upload,
            'screenId'  => $screen ? $screen->id : '',
            'i18n'      => array(
                'folders'          => __('โฟลเดอร์สื่อ', 'techno-media-folders'),
                'allFiles'         => __('ไฟล์ทั้งหมด', 'techno-media-folders'),
                'uncategorized'    => __('ยังไม่มีโฟลเดอร์', 'techno-media-folders'),
                'newFolder'        => __('+ โฟลเดอร์ใหม่', 'techno-media-folders'),
                'newSubfolder'     => __('➕ โฟลเดอร์ย่อย', 'techno-media-folders'),
                'rename'           => __('✏️ เปลี่ยนชื่อ', 'techno-media-folders'),
                'delete'           => __('🗑️ ลบโฟลเดอร์', 'techno-media-folders'),
                'confirmDelete'    => __('คุณต้องการลบโฟลเดอร์นี้ใช่หรือไม่? (ไฟล์ในโฟลเดอร์จะไม่ถูกลบ แต่จะกลับไปอยู่ที่ "ยังไม่มีโฟลเดอร์")', 'techno-media-folders'),
                'searchFolders'    => __('ค้นหาโฟลเดอร์...', 'techno-media-folders'),
                'enterFolderName'  => __('กรุณาระบุชื่อโฟลเดอร์:', 'techno-media-folders'),
                'enterNewName'     => __('กรุณาระบุชื่อโฟลเดอร์ใหม่:', 'techno-media-folders'),
                'moveSuccess'      => __('✓ ย้ายไฟล์เรียบร้อยแล้ว', 'techno-media-folders'),
                'dropToMove'       => __('ปล่อยเพื่อย้ายไฟล์มาที่โฟลเดอร์นี้', 'techno-media-folders'),
                'emptyFolder'      => __('ไม่มีไฟล์ในโฟลเดอร์นี้', 'techno-media-folders'),
            ),
        ));
    }

    /**
     * Add Folder selection to Attachment Details pane
     */
    public static function add_attachment_folder_field($form_fields, $post) {
        $terms = get_terms(array(
            'taxonomy'   => Techno_MF_Taxonomy::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        $current_terms = wp_get_object_terms($post->ID, Techno_MF_Taxonomy::TAXONOMY, array('fields' => 'ids'));
        $current_id = (!empty($current_terms) && !is_wp_error($current_terms)) ? (int)$current_terms[0] : 0;

        $html = '<select name="attachments[' . (int)$post->ID . '][techno_media_folder]" id="attachments-' . (int)$post->ID . '-techno-folder" class="techno-attachment-folder-select" data-attachment-id="' . (int)$post->ID . '">';
        $html .= '<option value="0">' . __('— ยังไม่มีโฟลเดอร์ —', 'techno-media-folders') . '</option>';

        if (!empty($terms) && !is_wp_error($terms)) {
            $html .= self::build_options_tree($terms, 0, 0, $current_id);
        }

        $html .= '</select>';

        $form_fields['techno_media_folder'] = array(
            'label' => __('📁 โฟลเดอร์สื่อ', 'techno-media-folders'),
            'input' => 'html',
            'html'  => $html,
            'helps' => __('เลือกโฟลเดอร์สำหรับจัดเก็บไฟล์นี้', 'techno-media-folders'),
        );

        return $form_fields;
    }

    private static function build_options_tree($terms, $parent = 0, $level = 0, $selected_id = 0) {
        $output = '';
        foreach ($terms as $term) {
            if ($term->parent == $parent) {
                $prefix = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                $icon = $level > 0 ? '↳ 📁 ' : '📁 ';
                $is_sel = ($selected_id === (int)$term->term_id) ? ' selected="selected"' : '';
                $output .= '<option value="' . (int)$term->term_id . '"' . $is_sel . '>' . $prefix . $icon . esc_html($term->name) . '</option>';
                $output .= self::build_options_tree($terms, $term->term_id, $level + 1, $selected_id);
            }
        }
        return $output;
    }

    /**
     * Save Folder selection when attachment is saved
     */
    public static function save_attachment_folder_field($post, $attachment) {
        if (isset($attachment['techno_media_folder'])) {
            $folder_id = (int) $attachment['techno_media_folder'];
            if ($folder_id > 0) {
                wp_set_object_terms($post['ID'], array($folder_id), Techno_MF_Taxonomy::TAXONOMY, false);
            } else {
                wp_set_object_terms($post['ID'], array(), Techno_MF_Taxonomy::TAXONOMY, false);
            }
        }
        return $post;
    }
}
