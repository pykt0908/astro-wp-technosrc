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
            'posts_per_page' => 20,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => 'banner_is_active',
                    'value'   => '1',
                    'compare' => '=',
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
                $mobile_image_url  = get_post_meta($id, 'banner_mobile_image_url', true);
                if (empty($mobile_image_url)) {
                    $mobile_image_url = $desktop_image_url;
                }

                $banners[] = array(
                    'id'                => $id,
                    'title'             => get_the_title(),
                    'desktop_image_url' => $desktop_image_url ?: '',
                    'mobile_image_url'  => $mobile_image_url ?: '',
                    'target_url'        => get_post_meta($id, 'banner_target_url', true) ?: '',
                    'menu_order'        => (int) get_post_field('menu_order', $id),
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response($banners);
    }
}
