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
        // Register block scripts
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
        
        // Register blocks with PHP only (server-side rendering)
        register_block_type('dfx-tg-feed/channel-feed', [
            'render_callback' => [$this, 'render_channel_feed'],
            'attributes' => [
                'channel' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'count' => [
                    'type' => 'number',
                    'default' => 10
                ]
            ]
        ]);
        
        register_block_type('dfx-tg-feed/channel-browser', [
            'render_callback' => [$this, 'render_channel_browser'],
            'attributes' => [
                'channel' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ]
        ]);
    }
    
    public function enqueue_block_editor_assets() {
        // Enqueue the channel feed block script
        wp_enqueue_script(
            'dfx-tg-feed-channel-feed-block',
            DFX_TG_FEED_URL . 'blocks/channel-feed/index.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'],
            DFX_TG_FEED_VER,
            true
        );
        
        // Enqueue the channel browser block script
        wp_enqueue_script(
            'dfx-tg-feed-channel-browser-block',
            DFX_TG_FEED_URL . 'blocks/channel-browser/index.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'],
            DFX_TG_FEED_VER,
            true
        );
    }
    
    public function render_channel_feed($attributes) {
        return Shortcodes::instance()->shortcode_channel_feed($attributes);
    }
    
    public function render_channel_browser($attributes) {
        return Shortcodes::instance()->shortcode_channel_browser($attributes);
    }
}
