<?php
namespace DFX\TelegramChannelFeed;
if (!defined('ABSPATH')) exit;

class Blocks {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function register() {
        // PHP side: provide server-rendered block for editors without JS
        // (You’d add JS code for custom block in /blocks/ later)
        register_block_type('dfx-tg-feed/channel-feed', [
            'render_callback' => [Shortcodes::instance(), 'shortcode_channel_feed']
        ]);
        register_block_type('dfx-tg-feed/channel-browser', [
            'render_callback' => [Shortcodes::instance(), 'shortcode_channel_browser']
        ]);
    }
}