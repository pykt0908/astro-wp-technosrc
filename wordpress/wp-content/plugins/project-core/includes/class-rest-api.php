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

        // College events endpoint
        register_rest_route(self::NAMESPACE, '/events', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_events'),
            'permission_callback' => '__return_true',
        ));

        // Event categories endpoint
        register_rest_route(self::NAMESPACE, '/event-categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_event_categories'),
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

    /**
     * Get college events formatted for frontend
     */
    public function get_events(WP_REST_Request $request) {
        $category      = sanitize_text_field($request->get_param('category'));
        $academic_year = sanitize_text_field($request->get_param('academic_year'));
        $per_page      = absint($request->get_param('per_page')) ?: 50;
        $page          = absint($request->get_param('page')) ?: 1;

        $query_args = array(
            'post_type'      => 'college_event',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'meta_key'       => 'event_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        );

        if (!empty($category) && $category !== 'all') {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_category',
                    'field'    => is_numeric($category) ? 'term_id' : 'slug',
                    'terms'    => $category,
                ),
            );
        }

        if (!empty($academic_year) && $academic_year !== 'all') {
            $query_args['meta_query'] = array(
                array(
                    'key'     => 'academic_year',
                    'value'   => $academic_year,
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query($query_args);
        $events = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();

                $terms = get_the_terms($id, 'event_category');
                $cats = array();
                $primary_cat_color = 'blue';

                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $index => $t) {
                        $cat_color = get_term_meta($t->term_id, 'category_color', true) ?: 'blue';
                        if ($index === 0) {
                            $primary_cat_color = $cat_color;
                        }
                        $cats[] = array(
                            'id'    => $t->term_id,
                            'name'  => $t->name,
                            'slug'  => $t->slug,
                            'color' => $cat_color,
                        );
                    }
                }

                $event_color_meta = get_post_meta($id, 'event_color', true);
                $final_color = (!empty($event_color_meta) && $event_color_meta !== 'auto') ? $event_color_meta : $primary_cat_color;

                $thumb_url = get_the_post_thumbnail_url($id, 'large');
                if (!$thumb_url) {
                    $thumb_url = null;
                }

                $ay = (string) get_post_meta($id, 'academic_year', true);
                if (empty($ay)) {
                    $s_date = (string) get_post_meta($id, 'event_start_date', true);
                    if (!empty($s_date)) {
                        $p = explode('-', $s_date);
                        $y = (int) $p[0];
                        $m = isset($p[1]) ? (int) $p[1] : 6;
                        $ay = (string) ((($m < 5) ? ($y - 1) : $y) + 543);
                    } else {
                        $ay = '2569';
                    }
                }

                $events[] = array(
                    'id'             => $id,
                    'title'          => html_entity_decode(get_the_title()),
                    'slug'           => get_post_field('post_name', $id),
                    'content'        => apply_filters('the_content', get_the_content()),
                    'excerpt'        => wp_strip_all_tags(get_the_excerpt()),
                    'academic_year'  => $ay,
                    'start_date'     => (string) get_post_meta($id, 'event_start_date', true),
                    'end_date'       => (string) get_post_meta($id, 'event_end_date', true),
                    'time'           => (string) get_post_meta($id, 'event_time', true),
                    'location'       => (string) get_post_meta($id, 'event_location', true),
                    'organizer'      => (string) get_post_meta($id, 'event_organizer', true),
                    'link'           => (string) get_post_meta($id, 'event_link', true),
                    'attachment_url' => (string) get_post_meta($id, 'event_attachment_url', true),
                    'color'          => $final_color,
                    'is_featured'    => (bool) get_post_meta($id, 'is_featured', true),
                    'thumbnail'      => $thumb_url,
                    'categories'     => $cats,
                );
            }
            wp_reset_postdata();
        }

        global $wpdb;
        $available_years = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'academic_year' AND meta_value != '' ORDER BY meta_value DESC");
        if (empty($available_years)) {
            $available_years = array('2569');
        }

        return rest_ensure_response(array(
            'events'         => $events,
            'academicYears'  => $available_years,
            'currentYear'    => !empty($available_years) ? $available_years[0] : '2569',
            'totalEvents'    => (int) $query->found_posts,
            'totalPages'     => (int) $query->max_num_pages,
            'page'           => $page,
        ));
    }

    /**
     * Get event categories list
     */
    public function get_event_categories(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'event_category',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        $result = array();
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $cat_color = get_term_meta($term->term_id, 'category_color', true) ?: 'blue';
                $result[] = array(
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => (int) $term->count,
                    'color' => $cat_color,
                );
            }
        }

        return rest_ensure_response($result);
    }
}
