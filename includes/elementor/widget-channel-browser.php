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
        
        // Block Container Styles
        $this->start_controls_section(
            'block_style_section',
            [
                'label' => __('Block Container', 'dfx-tg-feed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'block_background',
            [
                'label' => __('Background Color', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::COLOR,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'block_border',
                'selector' => '{{WRAPPER}} .dfx-tg-feed-layout',
            ]
        );
        
        $this->add_control(
            'block_border_radius',
            [
                'label' => __('Border Radius', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-layout' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'block_padding',
            [
                'label' => __('Padding', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-layout' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'block_margin',
            [
                'label' => __('Margin', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-layout' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Message Styles
        $this->start_controls_section(
            'message_style_section',
            [
                'label' => __('Message Container', 'dfx-tg-feed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'message_background',
            [
                'label' => __('Background Color', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-message' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'message_border',
                'selector' => '{{WRAPPER}} .dfx-tg-feed-message',
            ]
        );
        
        $this->add_control(
            'message_border_radius',
            [
                'label' => __('Border Radius', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'message_padding',
            [
                'label' => __('Padding', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'message_margin',
            [
                'label' => __('Margin', 'dfx-tg-feed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfx-tg-feed-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Typography - Date
        $this->start_controls_section(
            'date_typography_section',
            [
                'label' => __('Date Typography', 'dfx-tg-feed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'date_typography',
                'selector' => '{{WRAPPER}} .dfx-tg-feed-date',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography - Author
        $this->start_controls_section(
            'author_typography_section',
            [
                'label' => __('Author Typography', 'dfx-tg-feed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'author_typography',
                'selector' => '{{WRAPPER}} .dfx-tg-feed-author',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography - Message Text
        $this->start_controls_section(
            'text_typography_section',
            [
                'label' => __('Message Text Typography', 'dfx-tg-feed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .dfx-tg-feed-text',
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

        // Add inline style for block background if set
        $wrapper_style = '';
        if (!empty($settings['block_background'])) {
            $wrapper_style = 'style="background-color: ' . esc_attr($settings['block_background']) . ';"';
        }
        
        echo '<div ' . $wrapper_style . '>';
        $shortcode = \DFX\TelegramChannelFeed\Shortcodes::instance();
        echo $shortcode->shortcode_channel_browser([
            'channel' => $settings['channel']
        ]);
        echo '</div>';
    }
}
