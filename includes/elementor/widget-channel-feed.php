<?php
namespace DFXTgFeed\Elementor;

if (!defined('ABSPATH')) exit;

class Widget_Channel_Feed extends \Elementor\Widget_Base {

    public function get_name() {
        return 'dfxtgfeed_channel_feed';
    }

    public function get_title() {
        return __('Telegram Channel Feed', 'dfxtgfeed');
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return ['dfxtgfeed'];
    }

    public function get_keywords() {
        return ['telegram', 'channel', 'feed', 'messages'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'dfxtgfeed'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'channel',
            [
                'label' => __('Channel Username', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => '@yourchannel',
                'description' => __('Enter the Telegram channel username with @ or channel ID', 'dfxtgfeed'),
                'default' => '',
            ]
        );

        $this->add_control(
            'count',
            [
                'label' => __('Number of Messages', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 10,
            ]
        );

        $this->end_controls_section();
        
        // Block Container Styles
        $this->start_controls_section(
            'block_style_section',
            [
                'label' => __('Block Container', 'dfxtgfeed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'block_background',
            [
                'label' => __('Background Color', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'scheme' => [
                    'type' => \Elementor\Core\Schemes\Color::get_type(),
                    'value' => \Elementor\Core\Schemes\Color::COLOR_1,
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'block_border',
                'selector' => '{{WRAPPER}} .dfxtgfeed-layout',
            ]
        );
        
        $this->add_control(
            'block_border_radius',
            [
                'label' => __('Border Radius', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-layout' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'block_padding',
            [
                'label' => __('Padding', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-layout' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'block_margin',
            [
                'label' => __('Margin', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-layout' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'block_box_shadow',
                'selector' => '{{WRAPPER}} .dfxtgfeed-layout',
            ]
        );
        
        $this->end_controls_section();
        
        // Message Styles
        $this->start_controls_section(
            'message_style_section',
            [
                'label' => __('Message Container', 'dfxtgfeed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'message_background',
            [
                'label' => __('Background Color', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'scheme' => [
                    'type' => \Elementor\Core\Schemes\Color::get_type(),
                    'value' => \Elementor\Core\Schemes\Color::COLOR_1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-message' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'message_border',
                'selector' => '{{WRAPPER}} .dfxtgfeed-message',
            ]
        );
        
        $this->add_control(
            'message_border_radius',
            [
                'label' => __('Border Radius', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'message_padding',
            [
                'label' => __('Padding', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'message_margin',
            [
                'label' => __('Margin', 'dfxtgfeed'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .dfxtgfeed-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'message_box_shadow',
                'selector' => '{{WRAPPER}} .dfxtgfeed-message',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography - Date
        $this->start_controls_section(
            'date_typography_section',
            [
                'label' => __('Date Typography', 'dfxtgfeed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'date_typography',
                'selector' => '{{WRAPPER}} .dfxtgfeed-date',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography - Author
        $this->start_controls_section(
            'author_typography_section',
            [
                'label' => __('Author Typography', 'dfxtgfeed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'author_typography',
                'selector' => '{{WRAPPER}} .dfxtgfeed-author',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography - Message Text
        $this->start_controls_section(
            'text_typography_section',
            [
                'label' => __('Message Text Typography', 'dfxtgfeed'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .dfxtgfeed-text',
            ]
        );
        
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['channel'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="padding: 20px; border: 1px dashed #ccc; text-align: center;">';
                echo __('Please enter a channel username in the widget settings.', 'dfxtgfeed');
                echo '</div>';
            }
            return;
        }

        // Add inline style for block background if set
        $wrapper_style = '';
        if (!empty($settings['block_background'])) {
            // Sanitize color value - only allow safe CSS color values
            $color = sanitize_text_field($settings['block_background']);
            // Validate it's a safe CSS color (hex, rgb, rgba, or named color)
            if ($this->is_valid_css_color($color)) {
                $wrapper_style = 'style="background-color: ' . esc_attr($color) . ';"';
            }
        }
        
        echo '<div ' . $wrapper_style . '>';
        $shortcode = \DFXTgFeed\Shortcodes::instance();
        echo $shortcode->shortcode_channel_feed([
            'channel' => $settings['channel'],
            'count' => $settings['count']
        ]);
        echo '</div>';
    }
    
    /**
     * Validate if a string is a safe CSS color value
     */
    private function is_valid_css_color($color) {
        // Allow hex colors (#RGB, #RRGGBB, #RRGGBBAA)
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color)) {
            return true;
        }
        
        // Allow rgb/rgba
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)$/', $color)) {
            return true;
        }
        
        // Allow hsl/hsla
        if (preg_match('/^hsla?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*(,\s*[\d.]+\s*)?\)$/', $color)) {
            return true;
        }
        
        // Allow CSS named colors and special keywords
        $safe_keywords = ['transparent', 'currentcolor', 'inherit', 'initial', 'unset'];
        if (in_array(strtolower($color), $safe_keywords)) {
            return true;
        }
        
        return false;
    }
}
