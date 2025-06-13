<?php

/**
 * Plugin Name: WP Notion Sync
 * Plugin URI: https://andornagy.com/
 * Description: Create posts on Notion and sync them with WordPress
 * Version: 0.0.1
 * Author: Andor Nagy
 * Author URI: https://andornagy.com/
 */

namespace WP_Notion_Sync;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('WP_NOTION_SYNC_VERSION', '0.0.1');
define('WP_NOTION_SYNC_PATH', plugin_dir_path(__FILE__));
define('WP_NOTION_SYNC_URL', plugin_dir_url(__FILE__));
define('WP_NOTION_SYNC_LOG_PATH', plugin_dir_path(__FILE__) . 'logs/debug.log');

require_once WP_NOTION_SYNC_PATH . '/autoloader.php';
require_once WP_NOTION_SYNC_PATH . '/inc/functions.php';

use WP_Notion_Sync\Logger;
use WP_Notion_Sync\Admin_Options; // <-- NEW: Import your AdminOptions class

class WPNotionSync
{
    /**
     * @var NotionAPI $notion_api Instance of the NotionAPI client, if needed globally by this class.
     * @var Logger $logger Instance of the Logger, if needed globally by this class.
     */
    private $logger;
    private $admin_options; // <-- NEW: Property for AdminOptions

    // Private constructor to ensure singleton pattern
    private function __construct()
    {
        // Initialize AdminOptions early if its hooks need to be set up globally.
        // It will set up its own hooks for admin_menu and admin_init internally.
        $this->admin_options = new Admin_Options(); // <-- NEW: Instantiate AdminOptions

        // Now define the hooks for the plugin's main functionality
        $this->define_hooks();

        // Example: If a particular feature run by WPNotionSync itself needs NotionAPI
        // $notionApiToken = get_field('notion_api_key', 'option');
        // if (!empty($notionApiToken)) {
        //     $this->notion_api = new NotionAPI($notionApiToken);
        // } else {
        //     $this->logger->log_message('Notion API Key not found during main plugin init.', 'warning');
        // }
    }

    /**
     * Define all WordPress hooks for the plugin.
     */
    private function define_hooks()
    {
        // Example hook: Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Add admin menu items, register custom post types, etc.
        // add_action('admin_menu', [$this, 'add_admin_pages']);

        // Log plugin initialization (using the logger instantiated in constructor)
        Logger::log('WP Notion Sync plugin hooks defined.');
    }

    public function enqueue_assets()
    {
        if (is_page('build')) {
            wp_enqueue_script('coffee-blender-js', WP_NOTION_SYNC_URL . 'assets/js/blender-form.js', ['jquery'], '1.0', true);
            wp_enqueue_style('coffee-blender-css', WP_NOTION_SYNC_URL . 'assets/css/blender-form.css');
        }
    }

    /**
     * Initializes the plugin.
     *
     * @return WPNotionSync The single instance of the Plugin class.
     */
    public static function init()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new self();
        }
        return $instance;
    }
}

// Finally, initialize the plugin's main class instance
add_action('plugins_loaded', '\WP_Notion_Sync\WPNotionSync::init');
