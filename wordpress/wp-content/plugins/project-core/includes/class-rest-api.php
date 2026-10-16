<?php
/**
 * Custom REST API Endpoints for Headless Frontend
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_REST_API {

    const NAMESPACE = 'project-core/v1';

    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register endpoints
     */
    public function register_routes() {
        // Health check endpoint
        register_rest_route(self::NAMESPACE, '/health', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_health'),
            'permission_callback' => '__return_true',
        ));

        // Public site settings endpoint
        register_rest_route(self::NAMESPACE, '/site-settings', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_site_settings'),
            'permission_callback' => '__return_true',
        ));

        // Active banners endpoint
        register_rest_route(self::NAMESPACE, '/banners', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_banners'),
            'permission_callback' => '__return_true',
        ));

        // Formatted posts endpoint with category filtering, gallery and views
        register_rest_route(self::NAMESPACE, '/posts', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_posts'),
            'permission_callback' => '__return_true',
        ));

        // Categories endpoint for filtering
        register_rest_route(self::NAMESPACE, '/categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_categories'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Health check response
     */
    public function get_health(WP_REST_Request $request) {
        return rest_ensure_response(array(
            'status'     => 'ok',
            'timestamp'  => time(),
            'datetime'   => gmdate('c'),
            'wp_version' => get_bloginfo('version'),
            'headless'   => true,
        ));
    }

    /**
     * Site Settings response (Safe public data only)
     */
    public function get_site_settings(WP_REST_Request $request) {
        $raw = Project_Core_Site_Settings::get_settings();

        // Never expose webhook URL or secret in public REST API!
        $safe_settings = array(
            'site_name'        => $raw['site_name'],
            'site_tagline'     => $raw['site_tagline'],
            'logo_url'         => $raw['logo_url'],
            'favicon_url'      => $raw['favicon_url'],
            'contact'          => array(
                'email'        => $raw['contact_email'],
                'phone'        => $raw['contact_phone'],
                'address'      => $raw['contact_address'],
                'office_hours' => $raw['office_hours'],
            ),
            'social'           => array(
                'facebook'     => $raw['social_facebook'],
                'line'         => $raw['social_line'],
                'linkedin'     => $raw['social_linkedin'],
                'github'       => $raw['social_github'],
            ),
            'footer_copyright' => $raw['footer_copyright'],
        );

        return rest_ensure_response($safe_settings);
    }

    /**
     * Get active banners formatted for frontend carousel/hero
     */
    public function get_banners(WP_REST_Request $request) {
        $position = sanitize_text_field($request->get_param('position'));

        $query_args = array(
            'post_type'      => 'banner',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'banner_is_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'banner_is_active',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        if (!empty($position)) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'banner_position',
                    'field'    => 'slug',
                    'terms'    => $position,
                ),
            );
        }

        $query = new WP_Query($query_args);
        $banners = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();

                $desktop_image_url = get_the_post_thumbnail_url($id, 'full');
                if (empty($desktop_image_url)) {
                    $desktop_image_url = get_post_meta($id, 'banner_desktop_image_url', true);
                }
                $mobile_image_url  = get_post_meta($id, 'banner_mobile_image_url', true);
                if (empty($mobile_image_url)) {
                    $mobile_image_url = $desktop_image_url;
                }

                $target_url = get_post_meta($id, 'banner_target_url', true) ?: '';
                $menu_order = (int) get_post_field('menu_order', $id);

                $banners[] = array(
                    'id'                => $id,
                    'title'             => get_the_title(),
                    'desktop_image_url' => $desktop_image_url ?: '',
                    'desktopImageUrl'   => $desktop_image_url ?: '',
                    'mobile_image_url'  => $mobile_image_url ?: '',
                    'mobileImageUrl'    => $mobile_image_url ?: '',
                    'target_url'        => $target_url,
                    'targetUrl'         => $target_url,
                    'menu_order'        => $menu_order,
                    'menuOrder'         => $menu_order,
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response($banners);
    }

    /**
     * Get posts formatted for frontend with category filtering, gallery, and view count
     */
    public function get_posts(WP_REST_Request $request) {
        $category = sanitize_text_field($request->get_param('category'));
        $per_page = absint($request->get_param('per_page')) ?: 20;
        $page     = absint($request->get_param('page')) ?: 1;

        $query_args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if (!empty($category) && $category !== 'all') {
            if (is_numeric($category)) {
                $query_args['cat'] = (int) $category;
            } else {
                $query_args['category_name'] = $category;
            }
        }

        $query = new WP_Query($query_args);
        $posts = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();

                $cover_url   = get_the_post_thumbnail_url($id, 'large');
                $cover_thumb = get_the_post_thumbnail_url($id, 'medium');

                if (empty($cover_url)) {
                    $cover_url = get_post_meta($id, 'post_cover_url', true);
                }
                if (empty($cover_url)) {
                    $cover_url = get_post_meta($id, 'seo_og_image_url', true);
                }
                if (empty($cover_url) && !empty($gallery_data) && isset($gallery_data[0]['url'])) {
                    $cover_url = $gallery_data[0]['url'];
                }
                if (empty($cover_thumb)) {
                    $cover_thumb = $cover_url;
                }

                // Gallery
                $gallery_data = get_post_meta($id, 'post_gallery_data', true);
                if (!is_array($gallery_data) || empty($gallery_data)) {
                    $gallery_ids_str = get_post_meta($id, 'post_gallery_ids', true);
                    $gallery_data = array();
                    if (!empty($gallery_ids_str)) {
                        $ids = array_filter(array_map('absint', explode(',', $gallery_ids_str)));
                        foreach ($ids as $img_id) {
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
                    }
                }

                $gallery_count = (int) get_post_meta($id, 'post_gallery_count', true);
                if ($gallery_count <= 0) {
                    $gallery_count = count($gallery_data);
                }

                // Views
                $views = get_post_meta($id, 'post_views_count', true);
                $views_count = $views !== '' ? (int) $views : 12;

                // Categories
                $cat_terms = get_the_category($id);
                $categories_list = array();
                $category_names  = array();
                $category_ids    = array();
                if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
                    foreach ($cat_terms as $ct) {
                        $categories_list[] = array(
                            'id'   => $ct->term_id,
                            'name' => $ct->name,
                            'slug' => $ct->slug,
                        );
                        $category_names[] = $ct->name;
                        $category_ids[]   = $ct->term_id;
                    }
                }

                $author_name = get_the_author();
                if (empty($author_name)) $author_name = 'Admin';

                $posts[] = array(
                    'id'            => $id,
                    'slug'          => get_post_field('post_name', $id),
                    'titleHtml'     => get_the_title(),
                    'titlePlain'    => wp_strip_all_tags(get_the_title()),
                    'excerptHtml'   => get_the_excerpt(),
                    'excerptPlain'  => wp_strip_all_tags(get_the_excerpt()),
                    'contentHtml'   => apply_filters('the_content', get_the_content()),
                    'coverUrl'      => $cover_url ?: '',
                    'coverThumb'    => $cover_thumb ?: ($cover_url ?: ''),
                    'coverAlt'      => wp_strip_all_tags(get_the_title()),
                    'publishedAt'   => get_the_date('c'),
                    'modifiedAt'    => get_the_modified_date('c'),
                    'categories'    => $categories_list,
                    'categoryNames' => $category_names,
                    'categoryIds'   => $category_ids,
                    'views'         => $views_count,
                    'gallery'       => $gallery_data,
                    'galleryCount'  => $gallery_count,
                    'authorName'    => $author_name,
                    'authorRole'    => 'ผู้ดูแลระบบ',
                    'authorAvatar'  => get_avatar_url(get_the_author_meta('ID'), array('size' => 64)),
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response(array(
            'posts'      => $posts,
            'totalPosts' => (int) $query->found_posts,
            'totalPages' => (int) $query->max_num_pages,
            'page'       => $page,
        ));
    }

    /**
     * Get categories list for filtering
     */
    public function get_categories(WP_REST_Request $request) {
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ));

        $result = array();
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                // Skip default Uncategorized if empty
                if ($cat->slug === 'uncategorized' && $cat->count === 0) {
                    continue;
                }
                $result[] = array(
                    'id'    => $cat->term_id,
                    'name'  => $cat->name,
                    'slug'  => $cat->slug,
                    'count' => (int) $cat->count,
                );
            }
        }

        return rest_ensure_response($result);
    }
}
