<?php
namespace DFX\TelegramChannelFeed;

if (!defined('ABSPATH')) exit;

class Cache {
    private static $instance = null;
    private function __construct() {}

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function get_transient_key($channel) {
        return 'dfx_tg_feed_' . md5($channel);
    }

    public function get_cached_messages($channel, $limit) {
        $key = $this->get_transient_key($channel);
        $cache = get_transient($key);
        if ($cache !== false) {
            // Remove messages above $limit
            return array_slice($cache, 0, $limit);
        }
        return false;
    }

    public function set_cached_messages($channel, $messages, $ttl) {
        $key = $this->get_transient_key($channel);
        set_transient($key, $messages, $ttl);
    }

    public function refresh_cache($channel, $limit, $ttl) {
        $messages = API::instance()->fetch_channel_messages($channel, $limit);

        // Detect deleted messages by comparing with cache
        $old = $this->get_cached_messages($channel, 200); 
        $deleted_ids = [];
        if ($old !== false) {
            $old_ids = array_column($old, 'id');
            $new_ids = array_column($messages, 'id');
            $deleted_ids = array_diff($old_ids, $new_ids);
        }

        $this->set_cached_messages($channel, $messages, $ttl);

        return ['messages' => $messages, 'deleted_ids' => $deleted_ids];
    }

    // Ajax for admin panel refresh
    public function ajax_refresh_cache() {
        check_ajax_referer('dfx_tg_feed_refresh');
        if (!current_user_can('manage_options')) wp_send_json_error('No permission.');
        $channel = sanitize_text_field($_POST['channel'] ?? '');
        $limit = intval(get_option('dfx_tg_feed_default_count', 10));
        $ttl = intval(get_option('dfx_tg_feed_ttl', 300));
        $out = $this->refresh_cache($channel, $limit, $ttl);
        wp_send_json_success($out);
    }
}