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
                    ],
                    // Block container styles
                    'blockBackground' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderWidth' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderStyle' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderColor' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderRadius' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockPadding' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    'blockMargin' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    // Message styles
                    'messageBackground' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderWidth' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderStyle' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderColor' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderRadius' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messagePadding' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    'messageMargin' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    // Typography
                    'dateFontFamily' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'dateFontSize' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'authorFontFamily' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'authorFontSize' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'textFontFamily' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'textFontSize' => [
                        'type' => 'string',
                        'default' => ''
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
                    ],
                    // Block container styles
                    'blockBackground' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderWidth' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderStyle' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderColor' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockBorderRadius' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'blockPadding' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    'blockMargin' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    // Message styles
                    'messageBackground' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderWidth' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderStyle' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderColor' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messageBorderRadius' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'messagePadding' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    'messageMargin' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    // Typography
                    'dateFontFamily' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'dateFontSize' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'authorFontFamily' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'authorFontSize' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'textFontFamily' => [
                        'type' => 'string',
                        'default' => ''
                    ],
                    'textFontSize' => [
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
        
        // Generate and enqueue inline styles
        $this->enqueue_block_styles($attributes, 'feed');
        
        return Shortcodes::instance()->shortcode_channel_feed($attributes);
    }
    
    public function render_channel_browser($attributes) {
        // Ensure frontend assets are enqueued
        $this->enqueue_frontend_assets();
        
        // Generate and enqueue inline styles
        $this->enqueue_block_styles($attributes, 'browser');
        
        return Shortcodes::instance()->shortcode_channel_browser($attributes);
    }
    
    /**
     * Generate and enqueue inline styles for a block
     */
    private function enqueue_block_styles($attributes, $block_type) {
        static $block_counter = 0;
        $block_counter++;
        $block_id = 'dfx-tg-feed-block-' . $block_counter;
        
        $styles = $this->generate_block_styles($attributes, $block_id);
        
        if (!empty($styles)) {
            wp_add_inline_style('dfx-tg-feed', $styles);
        }
        
        // Add the block ID to the wrapper via filter
        add_filter('dfx_tg_feed_wrapper_class', function($classes) use ($block_id) {
            $classes .= ' ' . $block_id;
            return $classes;
        });
        
        return $block_id;
    }
    
    /**
     * Generate CSS styles from block attributes
     */
    private function generate_block_styles($attributes, $block_id) {
        $css = '';
        
        // Block container styles
        $block_styles = [];
        
        if (!empty($attributes['blockBackground'])) {
            $block_styles[] = 'background: ' . esc_attr($attributes['blockBackground']) . ';';
        }
        
        if (!empty($attributes['blockBorderStyle'])) {
            $block_styles[] = 'border-style: ' . esc_attr($attributes['blockBorderStyle']) . ';';
            
            if (!empty($attributes['blockBorderWidth'])) {
                $block_styles[] = 'border-width: ' . esc_attr($attributes['blockBorderWidth']) . ';';
            }
            
            if (!empty($attributes['blockBorderColor'])) {
                $block_styles[] = 'border-color: ' . esc_attr($attributes['blockBorderColor']) . ';';
            }
            
            if (!empty($attributes['blockBorderRadius'])) {
                $block_styles[] = 'border-radius: ' . esc_attr($attributes['blockBorderRadius']) . ';';
            }
        }
        
        if (!empty($attributes['blockPadding']) && is_array($attributes['blockPadding'])) {
            $padding = $this->format_box_values($attributes['blockPadding']);
            if ($padding) {
                $block_styles[] = 'padding: ' . $padding . ';';
            }
        }
        
        if (!empty($attributes['blockMargin']) && is_array($attributes['blockMargin'])) {
            $margin = $this->format_box_values($attributes['blockMargin']);
            if ($margin) {
                $block_styles[] = 'margin: ' . $margin . ';';
            }
        }
        
        if (!empty($block_styles)) {
            $css .= '.' . $block_id . ' { ' . implode(' ', $block_styles) . ' }' . "\n";
        }
        
        // Message container styles
        $message_styles = [];
        
        if (!empty($attributes['messageBackground'])) {
            $message_styles[] = 'background: ' . esc_attr($attributes['messageBackground']) . ';';
        }
        
        if (!empty($attributes['messageBorderStyle'])) {
            $message_styles[] = 'border-style: ' . esc_attr($attributes['messageBorderStyle']) . ';';
            
            if (!empty($attributes['messageBorderWidth'])) {
                $message_styles[] = 'border-width: ' . esc_attr($attributes['messageBorderWidth']) . ';';
            }
            
            if (!empty($attributes['messageBorderColor'])) {
                $message_styles[] = 'border-color: ' . esc_attr($attributes['messageBorderColor']) . ';';
            }
            
            if (!empty($attributes['messageBorderRadius'])) {
                $message_styles[] = 'border-radius: ' . esc_attr($attributes['messageBorderRadius']) . ';';
            }
        }
        
        if (!empty($attributes['messagePadding']) && is_array($attributes['messagePadding'])) {
            $padding = $this->format_box_values($attributes['messagePadding']);
            if ($padding) {
                $message_styles[] = 'padding: ' . $padding . ';';
            }
        }
        
        if (!empty($attributes['messageMargin']) && is_array($attributes['messageMargin'])) {
            $margin = $this->format_box_values($attributes['messageMargin']);
            if ($margin) {
                $message_styles[] = 'margin: ' . $margin . ';';
            }
        }
        
        if (!empty($message_styles)) {
            $css .= '.' . $block_id . ' .dfx-tg-feed-message { ' . implode(' ', $message_styles) . ' }' . "\n";
        }
        
        // Typography styles
        if (!empty($attributes['dateFontFamily']) || !empty($attributes['dateFontSize'])) {
            $date_styles = [];
            if (!empty($attributes['dateFontFamily'])) {
                $date_styles[] = 'font-family: ' . esc_attr($attributes['dateFontFamily']) . ';';
            }
            if (!empty($attributes['dateFontSize'])) {
                $date_styles[] = 'font-size: ' . esc_attr($attributes['dateFontSize']) . ';';
            }
            if (!empty($date_styles)) {
                $css .= '.' . $block_id . ' .dfx-tg-feed-date { ' . implode(' ', $date_styles) . ' }' . "\n";
            }
        }
        
        if (!empty($attributes['authorFontFamily']) || !empty($attributes['authorFontSize'])) {
            $author_styles = [];
            if (!empty($attributes['authorFontFamily'])) {
                $author_styles[] = 'font-family: ' . esc_attr($attributes['authorFontFamily']) . ';';
            }
            if (!empty($attributes['authorFontSize'])) {
                $author_styles[] = 'font-size: ' . esc_attr($attributes['authorFontSize']) . ';';
            }
            if (!empty($author_styles)) {
                $css .= '.' . $block_id . ' .dfx-tg-feed-author { ' . implode(' ', $author_styles) . ' }' . "\n";
            }
        }
        
        if (!empty($attributes['textFontFamily']) || !empty($attributes['textFontSize'])) {
            $text_styles = [];
            if (!empty($attributes['textFontFamily'])) {
                $text_styles[] = 'font-family: ' . esc_attr($attributes['textFontFamily']) . ';';
            }
            if (!empty($attributes['textFontSize'])) {
                $text_styles[] = 'font-size: ' . esc_attr($attributes['textFontSize']) . ';';
            }
            if (!empty($text_styles)) {
                $css .= '.' . $block_id . ' .dfx-tg-feed-text { ' . implode(' ', $text_styles) . ' }' . "\n";
            }
        }
        
        return $css;
    }
    
    /**
     * Format box control values (padding/margin) into CSS
     */
    private function format_box_values($values) {
        if (empty($values)) {
            return '';
        }
        
        $top = isset($values['top']) ? $values['top'] : '';
        $right = isset($values['right']) ? $values['right'] : '';
        $bottom = isset($values['bottom']) ? $values['bottom'] : '';
        $left = isset($values['left']) ? $values['left'] : '';
        
        // If all values are empty, return empty string
        if (empty($top) && empty($right) && empty($bottom) && empty($left)) {
            return '';
        }
        
        // If all values are the same, use shorthand
        if ($top === $right && $top === $bottom && $top === $left && !empty($top)) {
            return esc_attr($top);
        }
        
        // Otherwise return full format
        return esc_attr($top ?: '0') . ' ' . esc_attr($right ?: '0') . ' ' . esc_attr($bottom ?: '0') . ' ' . esc_attr($left ?: '0');
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
