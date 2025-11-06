<?php
namespace DFX\TelegramChannelFeed;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Plugin {

    /** @var Plugin */
    private static $instance = null;

    /** Return the singleton instance */
    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function init() {
        // Autoload or require class files
        spl_autoload_register(function($class) {
            if (strpos($class, __NAMESPACE__) === 0) {
                $name = strtolower(str_replace('\\', '-', str_replace(__NAMESPACE__ . '\\', '', $class)));
                $file = __DIR__ . '/class-' . $name . '.php';
                if (file_exists($file)) { require $file; }
            }
        });

        add_action('init',          [$this, 'register_shortcodes']);
        add_action('init',          [$this, 'register_blocks']);
        add_action('init',          [$this, 'register_post_type']);
        add_action('init',          [$this, 'init_elementor']);
        add_action('admin_menu',    [$this, 'settings_page']);
        add_action('admin_init',    [$this, 'register_settings']);
        add_action('wp_ajax_dfx_tg_feed_test', [Settings::instance(), 'ajax_test_bot_channel']);
        add_action('wp_ajax_dfx_tg_feed_reload', [Settings::instance(), 'ajax_reload_messages']);
        add_action('wp_ajax_dfx_tg_feed_refresh', [PostType::instance(), 'ajax_refresh_messages']);
        add_action('wp_ajax_dfx_tg_proxy_sticker', [$this, 'ajax_proxy_sticker']);
        add_action('wp_ajax_nopriv_dfx_tg_proxy_sticker', [$this, 'ajax_proxy_sticker']);
    }

    public function register_shortcodes() {
        Shortcodes::instance()->register();
    }

    public function register_blocks() {
        Blocks::instance()->register();
    }

    public function register_post_type() {
        PostType::instance()->register();
    }

    public function init_elementor() {
        Elementor::instance()->init();
    }

    public function settings_page() {
        // Add top-level menu
        add_menu_page(
            __('Telegram Messages', 'dfx-tg-feed'),
            __('Telegram Messages', 'dfx-tg-feed'),
            'manage_options',
            'dfx-telegram-messages',
            '', // No callback for the main page - it will redirect to the first submenu
            'dashicons-email-alt',
            25
        );
        
        // Add settings submenu under the top-level menu
        add_submenu_page(
            'dfx-telegram-messages',
            __('Settings', 'dfx-tg-feed'),
            __('Settings', 'dfx-tg-feed'),
            'manage_options',
            'dfx-tg-feed',
            [Settings::instance(), 'render_page']
        );
    }

    public function register_settings() {
        Settings::instance()->register();
    }

    /**
     * AJAX handler to proxy TGS sticker files (bypasses CORS)
     */
    public function ajax_proxy_sticker() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'dfx_tg_sticker_proxy')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        // Get sticker URL and file_id
        $sticker_url = isset($_GET['url']) ? urldecode($_GET['url']) : '';
        $file_id = isset($_GET['file_id']) ? sanitize_text_field($_GET['file_id']) : '';

        if (empty($sticker_url) || empty($file_id)) {
            wp_send_json_error(['message' => 'Missing parameters'], 400);
        }

        // Validate URL is from Telegram
        if (strpos($sticker_url, 'api.telegram.org') === false) {
            wp_send_json_error(['message' => 'Invalid sticker URL'], 400);
        }

        // Check cache first
        $cache_key = 'dfx_tg_sticker_' . md5($file_id);
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            header('Content-Type: application/json');
            echo $cached_data;
            exit;
        }

        // Fetch sticker data from Telegram
        $response = wp_remote_get($sticker_url, [
            'timeout' => 15,
            'sslverify' => true
        ]);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DFX TG Feed: Failed to fetch sticker: ' . $response->get_error_message());
            }
            wp_send_json_error(['message' => 'Failed to fetch sticker from Telegram'], 500);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            wp_send_json_error(['message' => 'Telegram returned status ' . $status_code], 500);
        }

        $sticker_data = wp_remote_retrieve_body($response);

        // TGS files are gzipped JSON
        // Try to decompress if it's gzipped
        if (function_exists('gzdecode')) {
            $decompressed = @gzdecode($sticker_data);
            if ($decompressed !== false) {
                $sticker_data = $decompressed;
            }
        }

        // Validate JSON
        $json_data = json_decode($sticker_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid TGS data (not valid JSON)'], 500);
        }

        // Cache for 24 hours
        set_transient($cache_key, $sticker_data, DAY_IN_SECONDS);

        // Return with proper headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo $sticker_data;
        exit;
    }
}