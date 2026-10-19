<?php
if (!defined('ABSPATH')) {
    exit;
}

class Techno_MF_Ajax_Handler {

    const NONCE_ACTION = 'techno_mf_nonce';

    public static function init() {
        add_action('wp_ajax_techno_mf_get_tree', array(__CLASS__, 'ajax_get_tree'));
        add_action('wp_ajax_techno_mf_create_folder', array(__CLASS__, 'ajax_create_folder'));
        add_action('wp_ajax_techno_mf_rename_folder', array(__CLASS__, 'ajax_rename_folder'));
        add_action('wp_ajax_techno_mf_delete_folder', array(__CLASS__, 'ajax_delete_folder'));
        add_action('wp_ajax_techno_mf_move_items', array(__CLASS__, 'ajax_move_items'));
        add_action('wp_ajax_techno_mf_set_attachment_folder', array(__CLASS__, 'ajax_set_attachment_folder'));
    }

    private static function check_auth() {
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('ไม่มีสิทธิ์ในการจัดการไฟล์', 'techno-media-folders')), 403);
        }
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
    }

    /**
     * Get folder tree with counts
     */
    public static function ajax_get_tree() {
        self::check_auth();

        $terms = get_terms(array(
            'taxonomy'   => Techno_MF_Taxonomy::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        if (is_wp_error($terms)) {
            wp_send_json_error(array('message' => $terms->get_error_message()));
        }

        // Build hierarchical tree
        $terms_by_id = array();
        foreach ($terms as $t) {
            $terms_by_id[$t->term_id] = array(
                'id'       => (int) $t->term_id,
                'name'     => $t->name,
                'slug'     => $t->slug,
                'parent'   => (int) $t->parent,
                'count'    => (int) $t->count,
                'children' => array(),
            );
        }

        $tree = array();
        foreach ($terms_by_id as $id => &$node) {
            if ($node['parent'] > 0 && isset($terms_by_id[$node['parent']])) {
                $terms_by_id[$node['parent']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        $total_count = Techno_MF_Taxonomy::get_total_media_count();
        $uncategorized_count = Techno_MF_Taxonomy::get_uncategorized_count();

        wp_send_json_success(array(
            'tree'                => $tree,
            'total_count'         => $total_count,
            'uncategorized_count' => $uncategorized_count,
        ));
    }

    /**
     * Create folder
     */
    public static function ajax_create_folder() {
        self::check_auth();

        $name = isset($_POST['name']) ? sanitize_text_field(trim($_POST['name'])) : '';
        $parent = isset($_POST['parent']) ? (int) $_POST['parent'] : 0;

        if (empty($name)) {
            wp_send_json_error(array('message' => __('กรุณาระบุชื่อโฟลเดอร์', 'techno-media-folders')));
        }

        $result = wp_insert_term($name, Techno_MF_Taxonomy::TAXONOMY, array(
            'parent' => $parent,
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $term = get_term($result['term_id'], Techno_MF_Taxonomy::TAXONOMY);

        wp_send_json_success(array(
            'id'     => (int) $term->term_id,
            'name'   => $term->name,
            'parent' => (int) $term->parent,
            'count'  => 0,
        ));
    }

    /**
     * Rename folder
     */
    public static function ajax_rename_folder() {
        self::check_auth();

        $term_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $new_name = isset($_POST['name']) ? sanitize_text_field(trim($_POST['name'])) : '';

        if (!$term_id || empty($new_name)) {
            wp_send_json_error(array('message' => __('ข้อมูลไม่ถูกต้อง', 'techno-media-folders')));
        }

        $result = wp_update_term($term_id, Techno_MF_Taxonomy::TAXONOMY, array(
            'name' => $new_name,
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'id'   => $term_id,
            'name' => $new_name,
        ));
    }

    /**
     * Delete folder
     */
    public static function ajax_delete_folder() {
        self::check_auth();

        $term_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$term_id) {
            wp_send_json_error(array('message' => __('ไม่พบโฟลเดอร์ที่ต้องการลบ', 'techno-media-folders')));
        }

        // Move children terms to parent of this term so subfolders aren't orphaned
        $term = get_term($term_id, Techno_MF_Taxonomy::TAXONOMY);
        $parent = $term && !is_wp_error($term) ? (int)$term->parent : 0;

        $children = get_terms(array(
            'taxonomy'   => Techno_MF_Taxonomy::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $term_id,
        ));

        if (!empty($children) && !is_wp_error($children)) {
            foreach ($children as $child) {
                wp_update_term($child->term_id, Techno_MF_Taxonomy::TAXONOMY, array(
                    'parent' => $parent,
                ));
            }
        }

        $result = wp_delete_term($term_id, Techno_MF_Taxonomy::TAXONOMY);

        if (is_wp_error($result) || !$result) {
            wp_send_json_error(array('message' => __('ไม่สามารถลบโฟลเดอร์ได้', 'techno-media-folders')));
        }

        wp_send_json_success(array('id' => $term_id));
    }

    /**
     * Move items to target folder
     */
    public static function ajax_move_items() {
        self::check_auth();

        $item_ids = isset($_POST['item_ids']) ? (array) $_POST['item_ids'] : array();
        $target_folder = isset($_POST['target_folder']) ? sanitize_text_field($_POST['target_folder']) : '0';

        $clean_ids = array_filter(array_map('intval', $item_ids));
        if (empty($clean_ids)) {
            wp_send_json_error(array('message' => __('ไม่พบไฟล์ที่เลือก', 'techno-media-folders')));
        }

        $is_uncategorized = ($target_folder === 'uncategorized' || $target_folder === '0' || $target_folder === 0);
        $term_ids = $is_uncategorized ? array() : array((int) $target_folder);

        $affected_terms = $term_ids;
        foreach ($clean_ids as $attachment_id) {
            if (get_post_type($attachment_id) === 'attachment') {
                $old_terms = wp_get_object_terms($attachment_id, Techno_MF_Taxonomy::TAXONOMY, array('fields' => 'ids'));
                if (!empty($old_terms) && !is_wp_error($old_terms)) {
                    $affected_terms = array_merge($affected_terms, $old_terms);
                }
                wp_set_object_terms($attachment_id, $term_ids, Techno_MF_Taxonomy::TAXONOMY, false);
            }
        }

        // Refresh term counts for both old and new folders
        $affected_terms = array_unique(array_filter(array_map('intval', $affected_terms)));
        if (!empty($affected_terms)) {
            wp_update_term_count_now($affected_terms, Techno_MF_Taxonomy::TAXONOMY);
        }

        $target_name = __('ยังไม่มีโฟลเดอร์', 'techno-media-folders');
        if (!$is_uncategorized && (int)$target_folder > 0) {
            $t = get_term((int)$target_folder, Techno_MF_Taxonomy::TAXONOMY);
            if ($t && !is_wp_error($t)) {
                $target_name = $t->name;
            }
        }

        wp_send_json_success(array(
            'moved_count'         => count($clean_ids),
            'target_folder'       => $target_folder,
            'target_name'         => $target_name,
            'total_count'         => Techno_MF_Taxonomy::get_total_media_count(),
            'uncategorized_count' => Techno_MF_Taxonomy::get_uncategorized_count(),
        ));
    }

    /**
     * Update folder for single attachment from media details modal
     */
    public static function ajax_set_attachment_folder() {
        self::check_auth();

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        $folder_id = isset($_POST['folder_id']) ? sanitize_text_field($_POST['folder_id']) : '';

        if (!$attachment_id) {
            wp_send_json_error(array('message' => __('ไม่พบไฟล์', 'techno-media-folders')));
        }

        if ($folder_id === '' || $folder_id === '0' || $folder_id === 'uncategorized') {
            wp_set_object_terms($attachment_id, array(), Techno_MF_Taxonomy::TAXONOMY, false);
        } else {
            wp_set_object_terms($attachment_id, array((int)$folder_id), Techno_MF_Taxonomy::TAXONOMY, false);
        }

        wp_send_json_success();
    }
}
