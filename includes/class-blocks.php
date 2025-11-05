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
        // Register blocks
        add_action('init', [$this, 'register_blocks']);
    }
    
    public function register_blocks() {
        // Register Channel Feed block
        $channel_feed_asset_file = DFX_TG_FEED_PATH . 'build/channel-feed/index.asset.php';
        if (file_exists($channel_feed_asset_file)) {
            $channel_feed_asset = include $channel_feed_asset_file;
            
            wp_register_script(
                'dfx-tg-feed-channel-feed-block',
                DFX_TG_FEED_URL . 'build/channel-feed/index.js',
                $channel_feed_asset['dependencies'],
                $channel_feed_asset['version']
            );
            
            register_block_type('dfx-tg-feed/channel-feed', [
                'editor_script' => 'dfx-tg-feed-channel-feed-block',
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
        }
        
        // Register Channel Browser block
        $channel_browser_asset_file = DFX_TG_FEED_PATH . 'build/channel-browser/index.asset.php';
        if (file_exists($channel_browser_asset_file)) {
            $channel_browser_asset = include $channel_browser_asset_file;
            
            wp_register_script(
                'dfx-tg-feed-channel-browser-block',
                DFX_TG_FEED_URL . 'build/channel-browser/index.js',
                $channel_browser_asset['dependencies'],
                $channel_browser_asset['version']
            );
            
            register_block_type('dfx-tg-feed/channel-browser', [
                'editor_script' => 'dfx-tg-feed-channel-browser-block',
                'render_callback' => [$this, 'render_channel_browser'],
                'attributes' => [
                    'channel' => [
                        'type' => 'string',
                        'default' => ''
                    ]
                ]
            ]);
        }
    }
    
    public function render_channel_feed($attributes) {
        return Shortcodes::instance()->shortcode_channel_feed($attributes);
    }
    
    public function render_channel_browser($attributes) {
        return Shortcodes::instance()->shortcode_channel_browser($attributes);
    }
}
