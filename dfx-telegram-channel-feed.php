<?php
/*
Plugin Name: DFX Telegram Channel Feed
Description: Display messages from a Telegram channel on your WordPress site using your Telegram Bot.
Version: 1.0.0
Author: David Marín
Author URI: http://www.davefx.com
Text Domain: dfxtgfeed
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DFXTGFEED_PATH', plugin_dir_path( __FILE__ ) );
define( 'DFXTGFEED_URL', plugin_dir_url( __FILE__ ) );
define( 'DFXTGFEED_VER', '1.0.0' );

require_once DFXTGFEED_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', function() {
    \DFXTgFeed\Plugin::instance();
} );

// Flush rewrite rules on plugin activation
register_activation_hook( __FILE__, function() {
    \DFXTgFeed\Plugin::instance();
    flush_rewrite_rules();
} );

// Plugin deactivation: clear scheduled cron event and flush rewrite rules.
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( \DFXTgFeed\Plugin::CRON_HOOK );
    flush_rewrite_rules();
} );
