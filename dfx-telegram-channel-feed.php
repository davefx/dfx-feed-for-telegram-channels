<?php
/*
Plugin Name:       DFX Telegram Channel Feed
Plugin URI:        https://github.com/davefx/dfx-telegram-channel-feed
Description:       Display messages from one or more Telegram channels on your WordPress site, with full media support, via shortcode, Gutenberg block, or Elementor widget.
Version:           1.0.1
Requires at least: 5.0
Requires PHP:      7.4
Author:            David Marín
Author URI:        http://www.davefx.com
License:           GPLv3 or later
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:       dfxtgfeed
Domain Path:       /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DFXTGFEED_PATH', plugin_dir_path( __FILE__ ) );
define( 'DFXTGFEED_URL', plugin_dir_url( __FILE__ ) );
define( 'DFXTGFEED_VER', '1.0.1' );

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
