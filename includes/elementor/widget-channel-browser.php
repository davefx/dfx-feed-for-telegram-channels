<?php
namespace DFX\TelegramChannelFeed\Elementor;

if (!defined('ABSPATH')) exit;

class Widget_Channel_Browser extends \Elementor\Widget_Base {

    public function get_name() {
        return 'dfx_tg_channel_browser';
    }

    public function get_title() {
        return __('Telegram Channel Browser', 'dfx-tg-feed');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['dfx-telegram'];
    }

    public function get_keywords() {
        return ['telegram', 'channel', 'browser', 'history', 'messages'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'dfx-tg-feed'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'channel',
            [
                'label' => __('Channel Username', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => '@yourchannel',
                'description' => __('Enter the Telegram channel username with @ or channel ID', 'dfx-tg-feed'),
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['channel'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="padding: 20px; border: 1px dashed #ccc; text-align: center;">';
                echo __('Please enter a channel username in the widget settings.', 'dfx-tg-feed');
                echo '</div>';
            }
            return;
        }

        $shortcode = \DFX\TelegramChannelFeed\Shortcodes::instance();
        echo $shortcode->shortcode_channel_browser([
            'channel' => $settings['channel']
        ]);
    }
}
