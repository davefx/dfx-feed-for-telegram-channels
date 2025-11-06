<?php
namespace DFX\TelegramChannelFeed;

if (!defined('ABSPATH')) exit;

class Elementor {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function init() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        
        // Register widget categories
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);
    }

    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'dfx-telegram',
            [
                'title' => __('Telegram Feed', 'dfx-tg-feed'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        require_once DFX_TG_FEED_PATH . 'includes/elementor/widget-channel-feed.php';
        require_once DFX_TG_FEED_PATH . 'includes/elementor/widget-channel-browser.php';

        $widgets_manager->register(new Elementor\Widget_Channel_Feed());
        $widgets_manager->register(new Elementor\Widget_Channel_Browser());
    }
}
