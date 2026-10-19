<?php
/**
 * Plugin Name: Techno Media Folders
 * Plugin URI: https://technosriracha.ac.th
 * Description: ระบบจัดการโฟลเดอร์และหมวดหมู่ไฟล์/ภาพใน WordPress สไตล์ต้นไม้ ลากวาง (Drag & Drop) จัดการง่าย ใช้งานสะดวกทั้งในคลังสื่อและหน้าเลือกรูป
 * Version: 1.0.0
 * Author: Technosriracha
 * Author URI: https://technosriracha.ac.th
 * Text Domain: techno-media-folders
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TECHNO_MF_VERSION', '1.0.6');
define('TECHNO_MF_DIR', plugin_dir_path(__FILE__));
define('TECHNO_MF_URL', plugin_dir_url(__FILE__));

// Require classes
require_once TECHNO_MF_DIR . 'includes/class-folders-taxonomy.php';
require_once TECHNO_MF_DIR . 'includes/class-ajax-handler.php';
require_once TECHNO_MF_DIR . 'includes/class-media-integration.php';

/**
 * Main Plugin Class
 */
class Techno_Media_Folders {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        Techno_MF_Taxonomy::init();
        Techno_MF_Ajax_Handler::init();
        Techno_MF_Media_Integration::init();
    }
}

// Bootstrap
add_action('plugins_loaded', array('Techno_Media_Folders', 'instance'));

// Activation Hook
register_activation_hook(__FILE__, function() {
    Techno_MF_Taxonomy::register_taxonomy();
});
