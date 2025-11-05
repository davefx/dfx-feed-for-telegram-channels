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
                $name = strtolower(str_replace('\', '-', str_replace(__NAMESPACE__ . '\', '', $class)));
                $file = __DIR__ . '/class-' . $name . '.php';
                if (file_exists($file)) { require $file; }
            }
        });

        add_action('init',          [$this, 'register_shortcodes']);
        add_action('init',          [$this, 'register_blocks']);
        add_action('admin_menu',    [$this, 'settings_page']);
        add_action('admin_init',    [$this, 'register_settings']);
        add_action('wp_ajax_dfx_tg_feed_test', [Settings::instance(), 'ajax_test_bot_channel']);
        add_action('wp_ajax_dfx_tg_feed_refresh', [Cache::instance(), 'ajax_refresh_cache']);
    }

    public function register_shortcodes() {
        Shortcodes::instance()->register();
    }

    public function register_blocks() {
        Blocks::instance()->register();
    }

    public function settings_page() {
        add_options_page(
            __('DFX Telegram Channel Feed', 'dfx-tg-feed'),
            __('DFX Telegram Feed', 'dfx-tg-feed'),
            'manage_options',
            'dfx-tg-feed',
            [Settings::instance(), 'render_page']
        );
    }

    public function register_settings() {
        Settings::instance()->register();
    }
}