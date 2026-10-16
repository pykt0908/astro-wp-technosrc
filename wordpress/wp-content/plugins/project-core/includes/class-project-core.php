<?php
/**
 * Main Project Core Plugin Class
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core {

    private static $instance = null;

    public $post_types;
    public $custom_fields;
    public $site_settings;
    public $webhook_manager;
    public $rest_api;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
    }

    private function load_dependencies() {
        require_once PROJECT_CORE_PATH . 'includes/class-logger.php';
        require_once PROJECT_CORE_PATH . 'includes/class-post-types.php';
        require_once PROJECT_CORE_PATH . 'includes/class-custom-fields.php';
        require_once PROJECT_CORE_PATH . 'includes/class-site-settings.php';
        require_once PROJECT_CORE_PATH . 'includes/class-webhook-manager.php';
        require_once PROJECT_CORE_PATH . 'includes/class-rest-api.php';
    }

    private function init_modules() {
        $this->post_types      = new Project_Core_Post_Types();
        $this->post_types->init();

        $this->custom_fields   = new Project_Core_Custom_Fields();
        $this->custom_fields->init();

        $this->site_settings   = new Project_Core_Site_Settings();
        $this->site_settings->init();

        $this->webhook_manager = new Project_Core_Webhook_Manager();
        $this->webhook_manager->init();

        $this->rest_api        = new Project_Core_REST_API();
        $this->rest_api->init();

        // Enable CORS for local Astro dev server if needed
        add_action('rest_api_init', array($this, 'add_cors_headers'));
    }

    /**
     * Optional CORS for local dev environment
     */
    public function add_cors_headers() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Total, X-WP-TotalPages');
    }
}
