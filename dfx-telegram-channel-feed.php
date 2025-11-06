<?php
/*
Plugin Name: DFX Telegram Channel Feed
Description: Display messages from a Telegram channel on your WordPress site using your Telegram Bot.
Version: 1.0.0
Author: David Marín
Author URI: http://www.davefx.com
Text Domain: dfx-tg-feed
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DFX_TG_FEED_PATH', plugin_dir_path( __FILE__ ) );
define( 'DFX_TG_FEED_URL', plugin_dir_url( __FILE__ ) );
define( 'DFX_TG_FEED_VER', '1.0.0' );

require_once DFX_TG_FEED_PATH . 'includes/class-dfx-tg-feed-plugin.php';

add_action( 'plugins_loaded', function() {
    \DFX\TelegramChannelFeed\Plugin::instance();
} );

// Flush rewrite rules on plugin activation
register_activation_hook( __FILE__, function() {
    \DFX\TelegramChannelFeed\Plugin::instance();
    flush_rewrite_rules();
} );

// Flush rewrite rules on plugin deactivation
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );
