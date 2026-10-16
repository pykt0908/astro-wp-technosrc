<?php
/**
 * Plugin Name: Project Core (Technosrc Headless Engine)
 * Plugin URI:  https://technosrc.com
 * Description: Core functionality for Technosrc headless WordPress: Custom Post Types, Custom Fields, REST API extensions, Site Settings, and Automated Build Webhooks.
 * Version:     1.0.0
 * Author:      Technosrc Engineering Team
 * Author URI:  https://technosrc.com
 * License:     GPL-2.0+
 * Text Domain: project-core
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('PROJECT_CORE_VERSION', '1.0.0');
define('PROJECT_CORE_FILE', __FILE__);
define('PROJECT_CORE_PATH', plugin_dir_path(__FILE__));
define('PROJECT_CORE_URL', plugin_dir_url(__FILE__));

// Load Main Plugin Class
require_once PROJECT_CORE_PATH . 'includes/class-project-core.php';

// Initialize Plugin
function project_core_init() {
    return Project_Core::instance();
}
add_action('plugins_loaded', 'project_core_init');
