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
        // Register blocks from block.json files
        register_block_type(DFX_TG_FEED_PATH . 'blocks/channel-feed', [
            'render_callback' => [Shortcodes::instance(), 'shortcode_channel_feed']
        ]);
        register_block_type(DFX_TG_FEED_PATH . 'blocks/channel-browser', [
            'render_callback' => [Shortcodes::instance(), 'shortcode_channel_browser']
        ]);
    }
}
