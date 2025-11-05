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
        if (!$channel) return "No channel specified";
        $ttl = intval(get_option('dfx_tg_feed_ttl', 300));

        $cache = Cache::instance()->get_cached_messages($channel, $limit);
        if ($cache === false) {
            $result = Cache::instance()->refresh_cache($channel, $limit, $ttl);
            $messages = $result['messages'];
        } else {
            $messages = $cache;
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
}