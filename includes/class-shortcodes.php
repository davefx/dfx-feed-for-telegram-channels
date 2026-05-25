<?php
namespace DFXFFTC;
if (!defined('ABSPATH')) exit;

class Shortcodes {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function register() {
        add_shortcode('dfxfftc_channel_feed',    [$this, 'shortcode_channel_feed']);
        add_shortcode('dfxfftc_channel_browser', [$this, 'shortcode_channel_browser']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    public function enqueue_styles() {
        wp_enqueue_style(
            'dfxfftc',
            DFXFFTC_URL . 'assets/css/style.css',
            [],
            DFXFFTC_VER
        );
        
        // Enqueue Lottie library for TGS stickers
        wp_enqueue_script(
            'lottie-player',
            DFXFFTC_URL . 'assets/js/lib/lottie.min.js',
            [],
            '5.12.2',
            true
        );
        
        // Enqueue our sticker initialization script
        wp_enqueue_script(
            'dfxfftc-stickers',
            DFXFFTC_URL . 'assets/js/stickers.js',
            ['lottie-player'],
            DFXFFTC_VER,
            true
        );
        
        // Localize script with AJAX URL and nonce for sticker proxy
        wp_localize_script('dfxfftc-stickers', 'dfxfftcStickers', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dfxfftc_sticker_proxy')
        ]);
        
        // Enqueue lightbox script for image viewing
        wp_enqueue_script(
            'dfxfftc-lightbox',
            DFXFFTC_URL . 'assets/js/lightbox.js',
            [],
            DFXFFTC_VER,
            true
        );
    }
    
    /**
     * Load a template file with variables
     */
    private function load_template($template_name, $variables = []) {
        extract($variables);
        $template_path = DFXFFTC_PATH . 'templates/' . $template_name . '.php';
        
        // Allow themes to override templates
        $theme_template = locate_template('dfxfftc/' . $template_name . '.php');
        if ($theme_template) {
            $template_path = $theme_template;
        }
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '';
    }

    // [dfxfftc_channel_feed channel="tele_channel" count="5"]
    public function shortcode_channel_feed($atts) {
        $a = shortcode_atts([
            'channel' => '',
            'count'   => get_option('dfxfftc_default_count', 10),
        ], $atts );

        $channel = sanitize_text_field($a['channel']);
        $limit = intval($a['count']);
        
        // Check if channel is specified
        if (!$channel) {
            return $this->render_error(__('No channel specified. Please configure the channel in block settings.', 'dfx-feed-for-telegram-channels'));
        }
        
        // Check if bot token is configured
        $bot_token = get_option('dfxfftc_bot_token');
        if (!$bot_token) {
            return $this->render_error(__('Telegram Bot Token not configured. Please configure it in Settings → DFX Telegram Feed.', 'dfx-feed-for-telegram-channels'));
        }
        
        // Smart on-demand refresh: Check if last update is > 10 minutes old
        $channel_safe = sanitize_key($channel);
        $last_sync = get_transient("dfxfftc_last_sync_{$channel_safe}");
        if (!$last_sync || (time() - $last_sync) > 600) { // 600 seconds = 10 minutes
            // Check if not already refreshing (lock)
            if (!get_transient("dfxfftc_refreshing_{$channel_safe}")) {
                set_transient("dfxfftc_refreshing_{$channel_safe}", true, 30); // 30-second lock
                PostType::instance()->refresh_messages($channel, 100);
                set_transient("dfxfftc_last_sync_{$channel_safe}", time(), 3600);
                delete_transient("dfxfftc_refreshing_{$channel_safe}");
            }
        }

        $messages = PostType::instance()->get_messages($channel, $limit);
        if (empty($messages)) {
            $result = PostType::instance()->refresh_messages($channel, $limit);
            $messages = $result['messages'];
        }
        
        // Check if messages were retrieved
        if (empty($messages)) {
            return $this->render_info(sprintf(
                __('No messages found for channel %s. Make sure your bot is added as an admin to the channel and has received messages since being added.', 'dfx-feed-for-telegram-channels'),
                '<strong>' . esc_html($channel) . '</strong>'
            ));
        }

        // Get additional wrapper classes from filter (for blocks)
        $wrapper_class = apply_filters('dfxfftc_wrapper_class', '');

        return $this->load_template('feed', [
            'messages' => $messages,
            'channel' => $channel,
            'wrapper_class' => $wrapper_class,
        ]);
    }
    
    // [dfxfftc_channel_browser channel="tele_channel"]
    public function shortcode_channel_browser($atts) {
        $a = shortcode_atts([
            'channel' => '',
        ], $atts );
        $channel = sanitize_text_field($a['channel']);
        
        // Check if channel is specified
        if (!$channel) {
            return $this->render_error(__('No channel specified. Please configure the channel in block settings.', 'dfx-feed-for-telegram-channels'));
        }
        
        // Check if bot token is configured
        $bot_token = get_option('dfxfftc_bot_token');
        if (!$bot_token) {
            return $this->render_error(__('Telegram Bot Token not configured. Please configure it in Settings → DFX Telegram Feed.', 'dfx-feed-for-telegram-channels'));
        }
        
        // For browsing you might want to paginate, but for now show all
        $limit = 200;
        $messages = PostType::instance()->get_messages($channel, $limit);
        if (empty($messages)) {
            $result = PostType::instance()->refresh_messages($channel, $limit);
            $messages = $result['messages'];
        }
        
        // Check if messages were retrieved
        if (empty($messages)) {
            return $this->render_info(sprintf(
                __('No messages found for channel %s. Make sure your bot is added as an admin to the channel and has received messages since being added.', 'dfx-feed-for-telegram-channels'),
                '<strong>' . esc_html($channel) . '</strong>'
            ));
        }
        
        // Get additional wrapper classes from filter (for blocks)
        $wrapper_class = apply_filters('dfxfftc_wrapper_class', '');
        
        return $this->load_template('browser', [
            'messages' => $messages,
            'channel' => $channel,
            'wrapper_class' => $wrapper_class,
        ]);
    }
    
    /**
     * Render an error message
     */
    private function render_error($message) {
        return sprintf(
            '<div class="dfxfftc-error" style="padding: 15px; background: #f8d7da; border: 1px solid #f5c2c7; border-radius: 4px; color: #842029;">
                <strong>%s</strong> %s
            </div>',
            __('Error:', 'dfx-feed-for-telegram-channels'),
            $message
        );
    }
    
    /**
     * Render an info message
     */
    private function render_info($message) {
        return sprintf(
            '<div class="dfxfftc-info" style="padding: 15px; background: #cfe2ff; border: 1px solid #b6d4fe; border-radius: 4px; color: #084298;">
                <strong>%s</strong> %s
            </div>',
            __('Info:', 'dfx-feed-for-telegram-channels'),
            $message
        );
    }
}