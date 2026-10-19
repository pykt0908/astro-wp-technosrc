<?php
if (!defined('ABSPATH')) {
    exit;
}

class Techno_MF_Taxonomy {

    const TAXONOMY = 'media_folder';

    public static function init() {
        add_action('init', array(__CLASS__, 'register_taxonomy'));
        add_action('restrict_manage_posts', array(__CLASS__, 'add_list_view_filter'));
        add_filter('pre_get_posts', array(__CLASS__, 'filter_list_view_query'));
        add_filter('ajax_query_attachments_args', array(__CLASS__, 'filter_grid_view_query'));
        add_filter('manage_media_columns', array(__CLASS__, 'add_media_columns'));
        add_action('manage_media_custom_column', array(__CLASS__, 'render_media_column'), 10, 2);
    }

    public static function register_taxonomy() {
        $labels = array(
            'name'              => __('โฟลเดอร์สื่อ', 'techno-media-folders'),
            'singular_name'     => __('โฟลเดอร์', 'techno-media-folders'),
            'search_items'      => __('ค้นหาโฟลเดอร์', 'techno-media-folders'),
            'all_items'         => __('โฟลเดอร์ทั้งหมด', 'techno-media-folders'),
            'parent_item'       => __('โฟลเดอร์หลัก', 'techno-media-folders'),
            'parent_item_colon' => __('โฟลเดอร์หลัก:', 'techno-media-folders'),
            'edit_item'         => __('แก้ไขโฟลเดอร์', 'techno-media-folders'),
            'update_item'       => __('อัปเดตโฟลเดอร์', 'techno-media-folders'),
            'add_new_item'      => __('เพิ่มโฟลเดอร์ใหม่', 'techno-media-folders'),
            'new_item_name'     => __('ชื่อโฟลเดอร์ใหม่', 'techno-media-folders'),
            'menu_name'         => __('โฟลเดอร์สื่อ', 'techno-media-folders'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => false, // We render our custom column for better styling
            'show_in_menu'      => 'upload.php',
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'update_count_callback' => '_update_generic_term_count',
        );

        register_taxonomy(self::TAXONOMY, array('attachment'), $args);
    }

    /**
     * Add folder filter dropdown in Media List view (upload.php?mode=list)
     */
    public static function add_list_view_filter($post_type) {
        if ($post_type !== 'attachment') {
            return;
        }

        $current_folder = isset($_GET['techno_folder']) ? sanitize_text_field($_GET['techno_folder']) : '';

        echo '<select name="techno_folder" id="filter-by-techno-folder" style="max-width:200px;">';
        echo '<option value="">' . __('— ทุกโฟลเดอร์ —', 'techno-media-folders') . '</option>';
        echo '<option value="uncategorized"' . selected($current_folder, 'uncategorized', false) . '>' . __('⚠️ ยังไม่มีโฟลเดอร์', 'techno-media-folders') . '</option>';

        $terms = get_terms(array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        if (!empty($terms) && !is_wp_error($terms)) {
            self::render_dropdown_options($terms, 0, 0, $current_folder);
        }

        echo '</select>';
    }

    private static function render_dropdown_options($terms, $parent = 0, $level = 0, $selected = '') {
        foreach ($terms as $term) {
            if ($term->parent == $parent) {
                $prefix = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                $icon = $level > 0 ? '↳ 📁 ' : '📁 ';
                $is_selected = ($selected === (string)$term->term_id || $selected === $term->slug) ? ' selected="selected"' : '';
                echo '<option value="' . esc_attr($term->term_id) . '"' . $is_selected . '>' . $prefix . $icon . esc_html($term->name) . ' (' . (int)$term->count . ')</option>';
                self::render_dropdown_options($terms, $term->term_id, $level + 1, $selected);
            }
        }
    }

    /**
     * Filter query in List view (upload.php)
     */
    public static function filter_list_view_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'upload' && !empty($_GET['techno_folder'])) {
            $folder = sanitize_text_field($_GET['techno_folder']);
            self::apply_folder_tax_query($query, $folder);
        }
    }

    /**
     * Filter query in Grid view (AJAX query-attachments)
     */
    public static function filter_grid_view_query($args) {
        if (!empty($_REQUEST['query']['techno_folder'])) {
            $folder = sanitize_text_field($_REQUEST['query']['techno_folder']);

            if ($folder === 'uncategorized') {
                $tax_query = isset($args['tax_query']) && is_array($args['tax_query']) ? $args['tax_query'] : array();
                $tax_query[] = array(
                    'taxonomy' => self::TAXONOMY,
                    'operator' => 'NOT EXISTS',
                );
                $args['tax_query'] = $tax_query;
            } elseif ($folder !== 'all' && is_numeric($folder) && (int)$folder > 0) {
                $tax_query = isset($args['tax_query']) && is_array($args['tax_query']) ? $args['tax_query'] : array();
                $tax_query[] = array(
                    'taxonomy'         => self::TAXONOMY,
                    'field'            => 'term_id',
                    'terms'            => array((int)$folder),
                    'include_children' => true,
                );
                $args['tax_query'] = $tax_query;
            }
        }

        return $args;
    }

    private static function apply_folder_tax_query($query, $folder) {
        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = array();
        }

        if ($folder === 'uncategorized') {
            $tax_query[] = array(
                'taxonomy' => self::TAXONOMY,
                'operator' => 'NOT EXISTS',
            );
        } elseif ($folder !== 'all' && is_numeric($folder) && (int)$folder > 0) {
            $tax_query[] = array(
                'taxonomy'         => self::TAXONOMY,
                'field'            => 'term_id',
                'terms'            => array((int)$folder),
                'include_children' => true,
            );
        }

        $query->set('tax_query', $tax_query);
    }

    /**
     * Media columns in List view
     */
    public static function add_media_columns($columns) {
        $new_cols = array();
        foreach ($columns as $key => $title) {
            $new_cols[$key] = $title;
            if ($key === 'title') {
                $new_cols['techno_media_folder'] = __('📁 โฟลเดอร์', 'techno-media-folders');
            }
        }
        return $new_cols;
    }

    public static function render_media_column($column_name, $post_id) {
        if ($column_name === 'techno_media_folder') {
            $terms = get_the_terms($post_id, self::TAXONOMY);
            if (!empty($terms) && !is_wp_error($terms)) {
                $links = array();
                foreach ($terms as $term) {
                    $url = add_query_arg(array('techno_folder' => $term->term_id), admin_url('upload.php?mode=list'));
                    $links[] = '<a href="' . esc_url($url) . '" style="text-decoration:none;"><span class="dashicons dashicons-category" style="font-size:14px;width:14px;height:14px;vertical-align:-1px;color:#2271b1;"></span> ' . esc_html($term->name) . '</a>';
                }
                echo implode(', ', $links);
            } else {
                echo '<span style="color:#a7aaad;font-size:12px;">' . __('— ยังไม่มีโฟลเดอร์ —', 'techno-media-folders') . '</span>';
            }
        }
    }

    /**
     * Count attachments without any folder
     */
    public static function get_uncategorized_count() {
        global $wpdb;
        $sql = "SELECT COUNT(ID) FROM {$wpdb->posts}
                WHERE post_type = 'attachment'
                AND post_status != 'trash'
                AND ID NOT IN (
                    SELECT object_id FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tt.taxonomy = %s
                )";
        return (int) $wpdb->get_var($wpdb->prepare($sql, self::TAXONOMY));
    }

    /**
     * Count total attachments
     */
    public static function get_total_media_count() {
        global $wpdb;
        $sql = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status != 'trash'";
        return (int) $wpdb->get_var($sql);
    }
}
