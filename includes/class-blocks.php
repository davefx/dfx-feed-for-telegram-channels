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
        // Ensure frontend assets are enqueued
        $this->enqueue_frontend_assets();
        return Shortcodes::instance()->shortcode_channel_feed($attributes);
    }
    
    public function render_channel_browser($attributes) {
        // Ensure frontend assets are enqueued
        $this->enqueue_frontend_assets();
        return Shortcodes::instance()->shortcode_channel_browser($attributes);
    }
    
    /**
     * Enqueue frontend assets (CSS and JS for stickers)
     */
    private function enqueue_frontend_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'dfx-tg-feed',
            DFX_TG_FEED_URL . 'assets/css/style.css',
            [],
            DFX_TG_FEED_VER
        );
        
        // Enqueue Lottie library for TGS stickers
        wp_enqueue_script(
            'lottie-player',
            'https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js',
            [],
            '5.12.2',
            true
        );
        
        // Enqueue our sticker initialization script
        wp_enqueue_script(
            'dfx-tg-stickers',
            DFX_TG_FEED_URL . 'assets/js/stickers.js',
            ['lottie-player'],
            DFX_TG_FEED_VER,
            true
        );
    }
}
