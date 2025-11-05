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

        ob_start();
        ?>
        <div class="dfx-tg-feed">
            <?php foreach ($messages as $msg): ?>
                <div class="dfx-tg-feed-message" data-id="<?php echo esc_attr($msg['id']); ?>">
                  <div class="dfx-tg-feed-date"><?php echo date('Y-m-d H:i:s', $msg['date']); ?></div>
                  <div class="dfx-tg-feed-text"><?php echo esc_html($msg['text']); ?></div>
                  <?php if (!empty($msg['media'])): ?>
                      <img src="<?php echo esc_url($msg['media']); ?>" />
                  <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
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
        
        ob_start(); ?>
        <div class="dfx-tg-feed-browser">
            <?php foreach ($messages as $msg): ?>
                <div class="dfx-tg-feed-message" data-id="<?php echo esc_attr($msg['id']); ?>">
                  <div class="dfx-tg-feed-date"><?php echo date('Y-m-d H:i:s', $msg['date']); ?></div>
                  <div class="dfx-tg-feed-text"><?php echo esc_html($msg['text']); ?></div>
                  <?php if (!empty($msg['media'])): ?>
                      <img src="<?php echo esc_url($msg['media']); ?>" />
                  <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
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