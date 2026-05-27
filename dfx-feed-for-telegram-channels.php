<?php
/*
Plugin Name:       DFX Feed for Telegram Channels
Plugin URI:        https://github.com/davefx/dfx-feed-for-telegram-channels
Description:       Display messages from one or more Telegram channels on your WordPress site, with full media support, via shortcode, Gutenberg block, or Elementor widget.
Version:           2.0.3
Requires at least: 5.0
Requires PHP:      7.4
Author:            David Marín
Author URI:        http://www.davefx.com
License:           GPLv3 or later
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:       dfx-feed-for-telegram-channels
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DFXFFTC_PATH', plugin_dir_path( __FILE__ ) );
define( 'DFXFFTC_URL', plugin_dir_url( __FILE__ ) );
define( 'DFXFFTC_VER', '2.0.3' );

require_once DFXFFTC_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', function() {
    \DFXFFTC\Plugin::instance();
} );

// Flush rewrite rules on plugin activation
register_activation_hook( __FILE__, function() {
    \DFXFFTC\Plugin::instance();
    flush_rewrite_rules();
} );

// Plugin deactivation: clear scheduled cron event and flush rewrite rules.
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( \DFXFFTC\Plugin::CRON_HOOK );
    flush_rewrite_rules();
} );
