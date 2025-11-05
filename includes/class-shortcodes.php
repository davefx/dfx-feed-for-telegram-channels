<?php
namespace DFX\TelegramChannelFeed;
if (!defined('ABSPATH')) exit;

class Shortcodes {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function register() {
        add_shortcode('dfx_tg_channel_feed',    [$this, 'shortcode_channel_feed']);
        add_shortcode('dfx_tg_channel_browser', [$this, 'shortcode_channel_browser']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    public function enqueue_styles() {
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
        
        // Localize script with AJAX URL and nonce for sticker proxy
        wp_localize_script('dfx-tg-stickers', 'dfxTgFeedStickers', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dfx_tg_sticker_proxy')
        ]);
    }
    
    /**
     * Load a template file with variables
     */
    private function load_template($template_name, $variables = []) {
        extract($variables);
        $template_path = DFX_TG_FEED_PATH . 'templates/' . $template_name . '.php';
        
        // Allow themes to override templates
        $theme_template = locate_template('dfx-tg-feed/' . $template_name . '.php');
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

    // [dfx_tg_channel_feed channel="tele_channel" count="5"]
    public function shortcode_channel_feed($atts) {
        $a = shortcode_atts([
            'channel' => '',
            'count'   => get_option('dfx_tg_feed_default_count', 10),
        ], $atts );

        $channel = sanitize_text_field($a['channel']);
        $limit = intval($a['count']);
        
        // Check if channel is specified
        if (!$channel) {
            return $this->render_error(__('No channel specified. Please configure the channel in block settings.', 'dfx-tg-feed'));
        }
        
        // Check if bot token is configured
        $bot_token = get_option('dfx_tg_feed_bot_token');
        if (!$bot_token) {
            return $this->render_error(__('Telegram Bot Token not configured. Please configure it in Settings → DFX Telegram Feed.', 'dfx-tg-feed'));
        }
        
        $ttl = intval(get_option('dfx_tg_feed_ttl', 300));
        
        // Smart on-demand refresh: Check if last update is > 10 minutes old
        $channel_safe = sanitize_key($channel);
        $last_sync = get_transient("dfx_tg_last_sync_{$channel_safe}");
        if (!$last_sync || (time() - $last_sync) > 600) { // 600 seconds = 10 minutes
            // Check if not already refreshing (lock)
            if (!get_transient("dfx_tg_refreshing_{$channel_safe}")) {
                set_transient("dfx_tg_refreshing_{$channel_safe}", true, 30); // 30-second lock
                $result = Cache::instance()->refresh_cache($channel, 100, $ttl);
                set_transient("dfx_tg_last_sync_{$channel_safe}", time(), 3600);
                delete_transient("dfx_tg_refreshing_{$channel_safe}");
            }
        }

        $cache = Cache::instance()->get_cached_messages($channel, $limit);
        if ($cache === false) {
            $result = Cache::instance()->refresh_cache($channel, $limit, $ttl);
            $messages = $result['messages'];
        } else {
            $messages = $cache;
        }
        
        // Check if messages were retrieved
        if (empty($messages)) {
            return $this->render_info(sprintf(
                __('No messages found for channel %s. Make sure your bot is added as an admin to the channel and has received messages since being added.', 'dfx-tg-feed'),
                '<strong>' . esc_html($channel) . '</strong>'
            ));
        }

        return $this->load_template('feed', [
            'messages' => $messages,
            'channel' => $channel,
        ]);
    }
    
    // [dfx_tg_channel_browser channel="tele_channel"]
    public function shortcode_channel_browser($atts) {
        $a = shortcode_atts([
            'channel' => '',
        ], $atts );
        $channel = sanitize_text_field($a['channel']);
        
        // Check if channel is specified
        if (!$channel) {
            return $this->render_error(__('No channel specified. Please configure the channel in block settings.', 'dfx-tg-feed'));
        }
        
        // Check if bot token is configured
        $bot_token = get_option('dfx_tg_feed_bot_token');
        if (!$bot_token) {
            return $this->render_error(__('Telegram Bot Token not configured. Please configure it in Settings → DFX Telegram Feed.', 'dfx-tg-feed'));
        }
        
        $ttl = intval(get_option('dfx_tg_feed_ttl', 300));
        // For browsing you might want to paginate, but for now show all
        $limit = 200;
        $cache = Cache::instance()->get_cached_messages($channel, $limit);
        if ($cache === false) {
            $result = Cache::instance()->refresh_cache($channel, $limit, $ttl);
            $messages = $result['messages'];
        } else {
            $messages = $cache;
        }
        
        // Check if messages were retrieved
        if (empty($messages)) {
            return $this->render_info(sprintf(
                __('No messages found for channel %s. Make sure your bot is added as an admin to the channel and has received messages since being added.', 'dfx-tg-feed'),
                '<strong>' . esc_html($channel) . '</strong>'
            ));
        }
        
        return $this->load_template('browser', [
            'messages' => $messages,
            'channel' => $channel,
        ]);
    }
    
    /**
     * Render an error message
     */
    private function render_error($message) {
        return sprintf(
            '<div class="dfx-tg-feed-error" style="padding: 15px; background: #f8d7da; border: 1px solid #f5c2c7; border-radius: 4px; color: #842029;">
                <strong>%s</strong> %s
            </div>',
            __('Error:', 'dfx-tg-feed'),
            $message
        );
    }
    
    /**
     * Render an info message
     */
    private function render_info($message) {
        return sprintf(
            '<div class="dfx-tg-feed-info" style="padding: 15px; background: #cfe2ff; border: 1px solid #b6d4fe; border-radius: 4px; color: #084298;">
                <strong>%s</strong> %s
            </div>',
            __('Info:', 'dfx-tg-feed'),
            $message
        );
    }
}