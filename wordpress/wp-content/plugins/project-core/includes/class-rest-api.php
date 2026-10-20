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

        // Academic years endpoint
        register_rest_route(self::NAMESPACE, '/academic-years', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_academic_years'),
            'permission_callback' => '__return_true',
        ));

        // Curriculums endpoint
        register_rest_route(self::NAMESPACE, '/curriculums', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_curriculums'),
            'permission_callback' => '__return_true',
        ));

        // Single curriculum by slug (support Thai & URL-encoded slugs)
        register_rest_route(self::NAMESPACE, '/curriculums/(?P<slug>[^/]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_curriculum_by_slug'),
            'permission_callback' => '__return_true',
        ));

        // Curriculum levels taxonomy endpoint
        register_rest_route(self::NAMESPACE, '/curriculum-levels', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_curriculum_levels'),
            'permission_callback' => '__return_true',
        ));

        // Curriculum departments taxonomy endpoint
        register_rest_route(self::NAMESPACE, '/curriculum-departments', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_curriculum_departments'),
            'permission_callback' => '__return_true',
        ));

        // Personnel list endpoint
        register_rest_route(self::NAMESPACE, '/personnel', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_personnel'),
            'permission_callback' => '__return_true',
        ));

        // Personnel categories taxonomy endpoint
        register_rest_route(self::NAMESPACE, '/personnel-categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_personnel_categories'),
            'permission_callback' => '__return_true',
        ));

        // SAR reports list endpoint
        register_rest_route(self::NAMESPACE, '/sar-reports', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_sar_reports'),
            'permission_callback' => '__return_true',
        ));

        // SAR years taxonomy endpoint
        register_rest_route(self::NAMESPACE, '/sar-years', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_sar_years'),
            'permission_callback' => '__return_true',
        ));

        // Educational MIS list endpoint
        register_rest_route(self::NAMESPACE, '/education-mis', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_education_mis'),
            'permission_callback' => '__return_true',
        ));

        // Educational MIS academic years endpoint
        register_rest_route(self::NAMESPACE, '/education-mis-years', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_education_mis_years'),
            'permission_callback' => '__return_true',
        ));

        // Downloads list endpoint
        register_rest_route(self::NAMESPACE, '/downloads', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_downloads'),
            'permission_callback' => '__return_true',
        ));

        // Download categories endpoint
        register_rest_route(self::NAMESPACE, '/download-categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_download_categories'),
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
            'vtr'              => array(
                'enabled'      => !empty($raw['vtr_enabled']),
                'youtube_url'  => (string) ($raw['vtr_youtube_url'] ?: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
                'youtube_id'   => $this->extract_youtube_id($raw['vtr_youtube_url'] ?: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
                'embed_url'    => !empty($this->extract_youtube_id($raw['vtr_youtube_url'] ?: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')) 
                    ? 'https://www.youtube-nocookie.com/embed/' . $this->extract_youtube_id($raw['vtr_youtube_url'] ?: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ') 
                    : '',
            ),
        );

        return rest_ensure_response($safe_settings);
    }

    /**
     * Extract 11-char YouTube Video ID from any YouTube URL format
     */
    private function extract_youtube_id($url) {
        if (empty($url)) {
            return '';
        }
        $url = trim($url);
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return '';
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

        $uncat = get_category_by_slug('uncategorized');
        $uncat_id = $uncat ? $uncat->term_id : 1;

        if (!empty($category) && $category !== 'all') {
            if (is_numeric($category)) {
                $query_args['cat'] = (int) $category;
            } else {
                $query_args['category_name'] = $category;
            }
        } else {
            if ($uncat_id) {
                $query_args['category__not_in'] = array($uncat_id);
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
                        if ($ct->slug === 'uncategorized' || $ct->name === 'Uncategorized' || $ct->name === 'ไม่มีหมวดหมู่') {
                            continue;
                        }
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
                // Always skip default Uncategorized
                if ($cat->slug === 'uncategorized') {
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
                        $raw_color = get_term_meta($t->term_id, 'category_color', true);
                        $cat_color = $this->resolve_color_hex($raw_color);
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
                $final_color = (!empty($event_color_meta) && $event_color_meta !== 'auto') ? $this->resolve_color_hex($event_color_meta) : $primary_cat_color;

                $thumb_url = get_the_post_thumbnail_url($id, 'large');
                if (!$thumb_url) {
                    $thumb_url = null;
                }

                $ay_terms = get_the_terms($id, 'academic_year');
                if ($ay_terms && !is_wp_error($ay_terms) && !empty($ay_terms)) {
                    $ay = $ay_terms[0]->name;
                } else {
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
                }

                $raw_content = get_the_content();
                $clean_content = trim(wp_strip_all_tags($raw_content));
                $clean_content = str_replace(array('&nbsp;', "\xc2\xa0"), '', $clean_content);
                $clean_content = trim($clean_content);

                $final_content = !empty($clean_content) ? apply_filters('the_content', $raw_content) : '';
                $final_excerpt = !empty($clean_content) ? wp_strip_all_tags(get_the_excerpt()) : '';

                $events[] = array(
                    'id'             => $id,
                    'title'          => html_entity_decode(get_the_title()),
                    'slug'           => get_post_field('post_name', $id),
                    'content'        => $final_content,
                    'excerpt'        => $final_excerpt,
                    'academic_year'  => $ay,
                    'start_date'     => (string) get_post_meta($id, 'event_start_date', true),
                    'end_date'       => (string) get_post_meta($id, 'event_end_date', true),
                    'time'           => (string) get_post_meta($id, 'event_time', true),
                    'location'       => (string) get_post_meta($id, 'event_location', true),
                    'note'           => (string) get_post_meta($id, 'event_note', true),
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

        $term_years = get_terms(array(
            'taxonomy'   => 'academic_year',
            'hide_empty' => false,
        ));
        $available_years = array();
        if (!is_wp_error($term_years) && !empty($term_years)) {
            foreach ($term_years as $ty) {
                $available_years[] = $ty->name;
            }
        }
        if (empty($available_years)) {
            $available_years = array('1/2569');
        }

        // Sort descending by year then semester (e.g. 2/2569, 1/2569, 2/2568, 1/2568)
        usort($available_years, function($a, $b) {
            $partsA = explode('/', $a);
            $partsB = explode('/', $b);
            $yearA = isset($partsA[1]) ? (int)$partsA[1] : (int)$partsA[0];
            $yearB = isset($partsB[1]) ? (int)$partsB[1] : (int)$partsB[0];
            if ($yearA !== $yearB) {
                return $yearB - $yearA;
            }
            $termA = isset($partsA[1]) ? (int)$partsA[0] : 0;
            $termB = isset($partsB[1]) ? (int)$partsB[0] : 0;
            return $termB - $termA;
        });

        return rest_ensure_response(array(
            'events'         => $events,
            'academicYears'  => $available_years,
            'currentYear'    => !empty($available_years) ? $available_years[0] : '1/2569',
            'totalEvents'    => (int) $query->found_posts,
            'totalPages'     => (int) $query->max_num_pages,
            'page'           => $page,
        ));
    }

    /**
     * Get academic years list from taxonomy
     */
    public function get_academic_years(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'academic_year',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $result[] = array(
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'slug'  => $t->slug,
                    'count' => $t->count,
                );
            }
        }

        if (empty($result)) {
            $result = array(
                array('id' => 1, 'name' => '1/2569', 'slug' => '1-2569', 'count' => 3),
            );
        }

        // Sort descending by year then semester
        usort($result, function($a, $b) {
            $partsA = explode('/', $a['name']);
            $partsB = explode('/', $b['name']);
            $yearA = isset($partsA[1]) ? (int)$partsA[1] : (int)$partsA[0];
            $yearB = isset($partsB[1]) ? (int)$partsB[1] : (int)$partsB[0];
            if ($yearA !== $yearB) {
                return $yearB - $yearA;
            }
            $termA = isset($partsA[1]) ? (int)$partsA[0] : 0;
            $termB = isset($partsB[1]) ? (int)$partsB[0] : 0;
            return $termB - $termA;
        });

        return rest_ensure_response($result);
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
                $raw_color = get_term_meta($term->term_id, 'category_color', true);
                $cat_color = $this->resolve_color_hex($raw_color);
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

    /**
     * Resolve color string or preset name into valid hex
     */
    private function resolve_color_hex($color) {
        $map = array(
            'blue'    => '#0047BA',
            'emerald' => '#059669',
            'green'   => '#059669',
            'amber'   => '#d97706',
            'yellow'  => '#d97706',
            'orange'  => '#d97706',
            'rose'    => '#e11d48',
            'red'     => '#e11d48',
            'purple'  => '#7c3aed',
        );
        if (!empty($color) && isset($map[$color])) {
            return $map[$color];
        }
        if (!empty($color) && preg_match('/^#[a-fA-F0-9]{3,6}$/', $color)) {
            return $color;
        }
        return '#0047BA';
    }

    /**
     * Get list of curriculums with filtering
     */
    public function get_curriculums(WP_REST_Request $request) {
        $level      = sanitize_text_field($request->get_param('level'));
        $department = sanitize_text_field($request->get_param('department'));
        $search     = sanitize_text_field($request->get_param('search'));
        $per_page   = (int) ($request->get_param('per_page') ?: -1);

        $args = array(
            'post_type'      => 'curriculum',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'orderby'        => array('menu_order' => 'ASC', 'title' => 'ASC'),
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $tax_query = array();
        if (!empty($level)) {
            $tax_query[] = array(
                'taxonomy' => 'curriculum_level',
                'field'    => 'slug',
                'terms'    => $level,
            );
        }

        if (!empty($department)) {
            $tax_query[] = array(
                'taxonomy' => 'curriculum_department',
                'field'    => 'slug',
                'terms'    => $department,
            );
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        $curriculums = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $curriculums[] = $this->format_curriculum_item($id);
            }
            wp_reset_postdata();
        }

        return rest_ensure_response(array(
            'curriculums' => $curriculums,
            'total'       => (int) $query->found_posts,
        ));
    }

    /**
     * Get single curriculum by slug
     */
    public function get_curriculum_by_slug(WP_REST_Request $request) {
        $raw_slug    = (string) $request->get_param('slug');
        $decoded     = urldecode($raw_slug);
        $target_slug = sanitize_title($decoded);

        $query = new WP_Query(array(
            'post_type'      => 'curriculum',
            'name'           => $target_slug,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
        ));

        if (!$query->have_posts()) {
            $query = new WP_Query(array(
                'post_type'      => 'curriculum',
                'name'           => $decoded,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
            ));
        }

        if (!$query->have_posts()) {
            $query = new WP_Query(array(
                'post_type'      => 'curriculum',
                'name'           => $raw_slug,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
            ));
        }

        if (!$query->have_posts()) {
            return new WP_Error('not_found', 'Curriculum not found', array('status' => 404));
        }

        $query->the_post();
        $item = $this->format_curriculum_item(get_the_ID());
        wp_reset_postdata();

        return rest_ensure_response($item);
    }

    /**
     * Format a single curriculum post object
     */
    private function format_curriculum_item($id) {
        $post = get_post($id);

        // Featured Image
        $thumb_id   = get_post_thumbnail_id($id);
        $thumb_full = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'full') : '';
        $thumb_med  = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '';

        // Levels (ปวช. / ปวส.)
        $level_terms = get_the_terms($id, 'curriculum_level');
        $level_data = null;
        if (!empty($level_terms) && !is_wp_error($level_terms)) {
            $first_lvl = reset($level_terms);
            $level_data = array(
                'id'   => $first_lvl->term_id,
                'name' => $first_lvl->name,
                'slug' => $first_lvl->slug,
            );
        }

        // Department
        $dept_terms = get_the_terms($id, 'curriculum_department');
        $dept_data = null;
        if (!empty($dept_terms) && !is_wp_error($dept_terms)) {
            $first_dept = reset($dept_terms);
            $dept_data = array(
                'id'   => $first_dept->term_id,
                'name' => $first_dept->name,
                'slug' => $first_dept->slug,
            );
        }

        // Highlights & Careers
        $raw_highlights = (string) get_post_meta($id, 'curriculum_highlights', true);
        $highlights = array_filter(array_map('trim', explode("\n", $raw_highlights)));

        $raw_careers = (string) get_post_meta($id, 'curriculum_careers', true);
        $careers = array_filter(array_map('trim', explode("\n", $raw_careers)));

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

        return array(
            'id'            => $id,
            'title'         => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'),
            'slug'          => urldecode($post->post_name),
            'content'       => apply_filters('the_content', $post->post_content),
            'excerpt'       => wp_strip_all_tags(get_the_excerpt($id)),
            'menuOrder'     => (int) $post->menu_order,
            'featuredImage' => $thumb_full ?: $thumb_med,
            'gallery'       => $gallery_data,
            'galleryCount'  => $gallery_count,
            'level'         => $level_data,
            'department'    => $dept_data,
            'meta'          => array(
                'degreeTh'         => (string) get_post_meta($id, 'curriculum_degree_th', true),
                'badge'            => (string) get_post_meta($id, 'curriculum_badge', true),
                'tuitionFee'       => (string) get_post_meta($id, 'curriculum_tuition_fee', true),
                'studyPlanUrl'     => (string) get_post_meta($id, 'curriculum_study_plan_url', true),
                'documentCoverUrl' => (string) get_post_meta($id, 'curriculum_document_cover_url', true),
                'highlights'       => array_values($highlights),
                'careers'      => array_values($careers),
            ),
        );
    }

    /**
     * Get curriculum levels
     */
    public function get_curriculum_levels(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'curriculum_level',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $result[] = array(
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'slug'  => $t->slug,
                    'count' => $t->count,
                );
            }
        }

        return rest_ensure_response($result);
    }

    /**
     * Get curriculum departments
     */
    public function get_curriculum_departments(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'curriculum_department',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $result[] = array(
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'slug'  => $t->slug,
                    'count' => $t->count,
                );
            }
        }

        return rest_ensure_response($result);
    }

    /**
     * Get personnel list with category filtering and ordering
     */
    public function get_personnel(WP_REST_Request $request) {
        $category_slug = $request->get_param('category');
        $search = $request->get_param('search');

        $args = array(
            'post_type'      => 'personnel',
            'post_status'    => 'publish',
            'posts_per_page' => 150,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );

        if (!empty($search)) {
            $args['s'] = sanitize_text_field($search);
        }

        if (!empty($category_slug) && $category_slug !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'personnel_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($category_slug),
                ),
            );
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        $result = array();
        foreach ($posts as $post) {
            $post_id = $post->ID;

            // Categories
            $cats = array();
            $terms = get_the_terms($post_id, 'personnel_category');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $t) {
                    $cats[] = array(
                        'id'          => $t->term_id,
                        'name'        => $t->name,
                        'slug'        => $t->slug,
                        'description' => $t->description,
                        'order'       => (int) (get_term_meta($t->term_id, 'category_order', true) ?: 99),
                    );
                }
            }

            // Thumbnail / Image
            $image = null;
            if (has_post_thumbnail($post_id)) {
                $thumb_id = get_post_thumbnail_id($post_id);
                $thumb_url = get_the_post_thumbnail_url($post_id, 'large');
                $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true) ?: $post->post_title;
                $meta = wp_get_attachment_metadata($thumb_id);
                $image = array(
                    'url'    => $thumb_url,
                    'alt'    => $alt,
                    'width'  => isset($meta['width']) ? (int)$meta['width'] : 600,
                    'height' => isset($meta['height']) ? (int)$meta['height'] : 800,
                );
            }

            $result[] = array(
                'id'         => $post_id,
                'name'       => $post->post_title,
                'slug'       => $post->post_name,
                'order'      => (int) $post->menu_order,
                'dept_order' => (int) (get_post_meta($post_id, 'personnel_dept_order', true) ?: $post->menu_order),
                'nickname'   => get_post_meta($post_id, 'personnel_nickname', true) ?: '',
                'position'   => get_post_meta($post_id, 'personnel_position', true) ?: '',
                'department' => get_post_meta($post_id, 'personnel_department', true) ?: '',
                'education'  => get_post_meta($post_id, 'personnel_education', true) ?: '',
                'email'      => get_post_meta($post_id, 'personnel_email', true) ?: '',
                'phone'      => get_post_meta($post_id, 'personnel_phone', true) ?: '',
                'bio'        => $post->post_content,
                'image'      => $image,
                'categories' => $cats,
            );
        }

        return rest_ensure_response($result);
    }

    /**
     * Get personnel categories
     */
    public function get_personnel_categories(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'personnel_category',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $custom_order = get_term_meta($t->term_id, 'category_order', true);
                $sort_val = ($custom_order !== '' && is_numeric($custom_order)) ? (int) $custom_order : 99;
                $result[] = array(
                    'id'          => $t->term_id,
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'description' => $t->description,
                    'count'       => (int) $t->count,
                    'order'       => $sort_val,
                );
            }

            usort($result, function($a, $b) {
                if ($a['order'] === $b['order']) {
                    return $a['id'] - $b['id'];
                }
                return $a['order'] - $b['order'];
            });
        }

        return rest_ensure_response($result);
    }

    /**
     * Get SAR reports formatted for frontend
     */
    public function get_sar_reports(WP_REST_Request $request) {
        $year_slug = sanitize_text_field($request->get_param('year'));
        $search    = sanitize_text_field($request->get_param('search'));
        $per_page  = absint($request->get_param('per_page')) ?: 50;
        $page      = absint($request->get_param('page')) ?: 1;

        $args = array(
            'post_type'      => 'sar_report',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($year_slug) && $year_slug !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'sar_year',
                    'field'    => 'slug',
                    'terms'    => $year_slug,
                ),
            );
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        $result = array();
        foreach ($posts as $post) {
            $post_id = $post->ID;

            // Year
            $year_data = null;
            $terms = get_the_terms($post_id, 'sar_year');
            if ($terms && !is_wp_error($terms)) {
                $t = $terms[0];
                $year_data = array(
                    'id'   => $t->term_id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                );
            }

            // Cover Image
            $cover_url = get_the_post_thumbnail_url($post_id, 'large') ?: '';

            // File Attachment
            $file_url  = get_post_meta($post_id, 'sar_file_url', true) ?: '';
            $file_type = 'pdf';
            if (!empty($file_url)) {
                $ext = strtolower(pathinfo(parse_url($file_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                if (!empty($ext)) $file_type = $ext;
            }

            $result[] = array(
                'id'          => $post_id,
                'title'       => $post->post_title,
                'slug'        => $post->post_name,
                'reportNo'    => get_post_meta($post_id, 'sar_report_no', true) ?: '',
                'description' => '',
                'content'     => apply_filters('the_content', $post->post_content),
                'year'        => $year_data,
                'fileUrl'     => $file_url,
                'fileSize'    => '',
                'fileType'    => $file_type,
                'externalUrl' => get_post_meta($post_id, 'sar_external_url', true) ?: '',
                'coverUrl'    => $cover_url,
                'order'       => (int) $post->menu_order,
                'publishedAt' => get_the_date('c', $post_id),
                'modifiedAt'  => get_the_modified_date('c', $post_id),
            );
        }

        // Sort by latest year descending, then order ASC, then date DESC
        usort($result, function($a, $b) {
            $yearA = 0;
            $yearB = 0;
            if (!empty($a['year']['name'])) {
                preg_match('/\d{4}/', $a['year']['name'], $mA);
                if (!empty($mA[0])) $yearA = (int) $mA[0];
            }
            if (!empty($b['year']['name'])) {
                preg_match('/\d{4}/', $b['year']['name'], $mB);
                if (!empty($mB[0])) $yearB = (int) $mB[0];
            }
            if ($yearB !== $yearA) {
                return $yearB - $yearA;
            }
            if ($a['order'] !== $b['order']) {
                return $a['order'] - $b['order'];
            }
            return strcmp($b['publishedAt'], $a['publishedAt']);
        });

        return rest_ensure_response(array(
            'reports'    => $result,
            'total'      => (int) $query->found_posts,
            'totalPages' => (int) $query->max_num_pages,
            'page'       => $page,
        ));
    }

    /**
     * Get SAR years list
     */
    public function get_sar_years(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'sar_year',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $result[] = array(
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'slug'  => $t->slug,
                    'count' => (int) $t->count,
                );
            }

            usort($result, function($a, $b) {
                return strcmp($b['name'], $a['name']);
            });
        }

        return rest_ensure_response($result);
    }

    /**
     * Get Educational MIS data formatted for frontend
     */
    public function get_education_mis(WP_REST_Request $request) {
        $year_slug = sanitize_text_field($request->get_param('year'));
        $search    = sanitize_text_field($request->get_param('search'));
        $per_page  = absint($request->get_param('per_page')) ?: 20;

        $args = array(
            'post_type'      => 'education_mis',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($year_slug) && $year_slug !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mis_academic_year',
                    'field'    => 'slug',
                    'terms'    => $year_slug,
                ),
            );
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        $result = array();
        foreach ($posts as $post) {
            $post_id = $post->ID;

            // Academic year taxonomy
            $year_data = null;
            $terms = get_the_terms($post_id, 'mis_academic_year');
            if ($terms && !is_wp_error($terms)) {
                $t = $terms[0];
                $year_data = array(
                    'id'   => $t->term_id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                );
            } else {
                $yt = get_post_meta($post_id, 'mis_academic_year_text', true) ?: '2568';
                $year_data = array(
                    'id'   => 0,
                    'name' => 'ปีการศึกษา ' . $yt,
                    'slug' => $yt,
                );
            }

            // Helper to decode JSON meta safely
            $decode_json = function($key) use ($post_id) {
                $val = get_post_meta($post_id, $key, true);
                if (empty($val)) return array();
                $decoded = json_decode($val, true);
                return is_array($decoded) ? $decoded : array();
            };

            // Totals & KPI figures
            $students_total   = (int) get_post_meta($post_id, 'mis_students_total', true);
            $personnel_total  = (int) get_post_meta($post_id, 'mis_personnel_total', true);
            $curr_progs_count = (int) get_post_meta($post_id, 'mis_programs_count', true);
            $emp_rate         = get_post_meta($post_id, 'mis_employment_total_rate', true) ?: '0';
            $gpax_avg         = get_post_meta($post_id, 'mis_gpax_avg', true) ?: '0.00';
            $mou_count        = (int) get_post_meta($post_id, 'mis_mou_partners_count', true);

            $result[] = array(
                'id'           => $post_id,
                'title'        => $post->post_title,
                'slug'         => $post->post_name,
                'description'  => apply_filters('the_content', $post->post_content),
                'academicYear' => $year_data,
                'pdfUrl'       => get_post_meta($post_id, 'mis_report_pdf_url', true) ?: '',
                'overview'     => array(
                    'totalStudents'   => $students_total,
                    'totalPersonnel'  => $personnel_total,
                    'programsCount'   => $curr_progs_count,
                    'employmentRate'  => $emp_rate,
                    'avgGpax'         => $gpax_avg,
                    'partnerCount'    => $mou_count,
                ),
                'students' => array(
                    'total'        => $students_total,
                    'pvchTotal'    => (int) get_post_meta($post_id, 'mis_students_pvch_total', true),
                    'pvsTotal'     => (int) get_post_meta($post_id, 'mis_students_pvs_total', true),
                    'byDepartment' => $decode_json('mis_students_by_dept'),
                ),
                'personnel' => array(
                    'total'       => $personnel_total,
                    'executives'  => (int) get_post_meta($post_id, 'mis_personnel_executives', true),
                    'teachers'    => (int) get_post_meta($post_id, 'mis_personnel_teachers', true),
                    'support'     => (int) get_post_meta($post_id, 'mis_personnel_support', true),
                    'breakdown'   => $decode_json('mis_personnel_breakdown'),
                ),
                'curriculum' => array(
                    'levelsCount'      => (int) get_post_meta($post_id, 'mis_curriculum_levels_count', true),
                    'departmentsCount' => (int) get_post_meta($post_id, 'mis_departments_count', true),
                    'programsCount'    => $curr_progs_count,
                    'list'             => $decode_json('mis_curriculum_list'),
                ),
                'admissionsGraduates' => $decode_json('mis_admissions_graduates'),
                'achievements' => array(
                    'gpaxAvg'                     => $gpax_avg,
                    'gpaxPvch'                    => get_post_meta($post_id, 'mis_gpax_pvch', true) ?: '',
                    'gpaxPvs'                     => get_post_meta($post_id, 'mis_gpax_pvs', true) ?: '',
                    'vnetPassRate'                => get_post_meta($post_id, 'mis_vnet_pass_rate', true) ?: '',
                    'vocationalStandardPassRate'  => get_post_meta($post_id, 'mis_vocational_standard_pass_rate', true) ?: '',
                    'list'                        => $decode_json('mis_achievements_list'),
                ),
                'employment' => array(
                    'totalRate'      => $emp_rate,
                    'fieldRate'      => get_post_meta($post_id, 'mis_employed_field_rate', true) ?: '',
                    'otherRate'      => get_post_meta($post_id, 'mis_employed_other_rate', true) ?: '',
                    'higherEdRate'   => get_post_meta($post_id, 'mis_higher_education_rate', true) ?: '',
                    'freelanceRate'  => get_post_meta($post_id, 'mis_freelance_business_rate', true) ?: '',
                    'unemployedRate' => get_post_meta($post_id, 'mis_unemployed_or_other_rate', true) ?: '',
                    'data'           => $decode_json('mis_employment_data'),
                ),
                'partners' => array(
                    'mouCount'             => $mou_count,
                    'dualVocationalCount'  => (int) get_post_meta($post_id, 'mis_dual_vocational_count', true),
                    'internStudentsCount'  => (int) get_post_meta($post_id, 'mis_intern_students_count', true),
                    'internAllowanceAvg'   => (int) get_post_meta($post_id, 'mis_intern_allowance_avg', true),
                    'enterprises'          => $decode_json('mis_partner_enterprises'),
                ),
                'publishedAt'  => get_the_date('c', $post_id),
                'modifiedAt'   => get_the_modified_date('c', $post_id),
            );
        }

        // Sort by year numeric descending
        usort($result, function($a, $b) {
            $yearA = 0;
            $yearB = 0;
            if (!empty($a['academicYear']['name'])) {
                preg_match('/\d{4}/', $a['academicYear']['name'], $mA);
                if (!empty($mA[0])) $yearA = (int) $mA[0];
            }
            if (!empty($b['academicYear']['name'])) {
                preg_match('/\d{4}/', $b['academicYear']['name'], $mB);
                if (!empty($mB[0])) $yearB = (int) $mB[0];
            }
            if ($yearB !== $yearA) {
                return $yearB - $yearA;
            }
            return strcmp($b['publishedAt'], $a['publishedAt']);
        });

        return rest_ensure_response(array(
            'items'      => $result,
            'total'      => (int) $query->found_posts,
            'totalPages' => (int) $query->max_num_pages,
        ));
    }

    /**
     * Get Educational MIS academic years
     */
    public function get_education_mis_years(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'mis_academic_year',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $result[] = array(
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'slug'  => $t->slug,
                    'count' => (int) $t->count,
                );
            }

            usort($result, function($a, $b) {
                return strcmp($b['name'], $a['name']);
            });
        }

        return rest_ensure_response($result);
    }

    /**
     * Get Downloads list
     */
    public function get_downloads(WP_REST_Request $request) {
        $category_slug = sanitize_text_field($request->get_param('category'));
        $search        = sanitize_text_field($request->get_param('search'));
        $per_page      = absint($request->get_param('per_page')) ?: 100;
        $page          = absint($request->get_param('page')) ?: 1;

        $args = array(
            'post_type'      => 'download_file',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($category_slug) && $category_slug !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'download_category',
                    'field'    => 'slug',
                    'terms'    => $category_slug,
                ),
            );
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        $result = array();
        foreach ($posts as $post) {
            $post_id = $post->ID;

            // Category
            $category_data = null;
            $terms = get_the_terms($post_id, 'download_category');
            if ($terms && !is_wp_error($terms)) {
                $t = $terms[0];
                $category_data = array(
                    'id'          => $t->term_id,
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'description' => $t->description,
                    'order'       => (int) get_term_meta($t->term_id, 'category_order', true),
                );
            }

            // File Details
            $source_type  = get_post_meta($post_id, 'download_source_type', true) ?: 'file';
            $file_url     = get_post_meta($post_id, 'download_file_url', true) ?: '';
            $external_url = get_post_meta($post_id, 'download_external_url', true) ?: '';
            $file_type    = get_post_meta($post_id, 'download_file_type', true) ?: 'pdf';
            $file_size    = get_post_meta($post_id, 'download_file_size', true) ?: '';
            $version      = get_post_meta($post_id, 'download_version', true) ?: '';
            $description  = get_post_meta($post_id, 'download_description', true) ?: $post->post_content;

            // Thumbnail / Cover if available
            $cover_url = get_the_post_thumbnail_url($post_id, 'large') ?: '';

            $result[] = array(
                'id'           => $post_id,
                'title'        => get_the_title($post),
                'slug'         => $post->post_name,
                'description'  => $description,
                'category'     => $category_data,
                'sourceType'   => $source_type,
                'fileUrl'      => $file_url,
                'externalUrl'  => $external_url,
                'fileType'     => strtolower($file_type),
                'fileSize'     => $file_size,
                'version'      => $version,
                'coverUrl'     => $cover_url,
                'order'        => (int) $post->menu_order,
                'publishedAt'  => get_the_date('c', $post),
            );
        }

        return rest_ensure_response($result);
    }

    /**
     * Get Download categories list
     */
    public function get_download_categories(WP_REST_Request $request) {
        $terms = get_terms(array(
            'taxonomy'   => 'download_category',
            'hide_empty' => false,
        ));

        $result = array();
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $order = (int) get_term_meta($t->term_id, 'category_order', true);
                $result[] = array(
                    'id'          => $t->term_id,
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'description' => $t->description,
                    'count'       => (int) $t->count,
                    'order'       => $order,
                );
            }

            usort($result, function($a, $b) {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] - $b['order'];
                }
                return strcmp($a['name'], $b['name']);
            });
        }

        return rest_ensure_response($result);
    }
}

