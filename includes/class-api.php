<?php
namespace DFX\TelegramChannelFeed;

if ( ! defined( 'ABSPATH' ) ) exit;

class API {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Fetch recent messages for a channel using the Telegram Bot API.
     * NOTE: Only messages since the bot was added will be returned.
     */
    public function fetch_channel_messages($channel_username, $limit = 10) {
        $bot_token = get_option('dfx_tg_feed_bot_token');
        if (!$bot_token) return [];
        // Clean the @ if present
        if (strpos($channel_username, '@') === 0) $channel_username = substr($channel_username, 1);

        // Bot API limitation: Only receives messages after being added to the channel
        $api_url = "https://api.telegram.org/bot" . $bot_token . "/getUpdates";
        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            return [];
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $messages = [];
        if (isset($body['ok']) && $body['ok']) {
            foreach (array_reverse($body['result']) as $update) {
                if (!empty($update['message']) && isset($update['message']['chat']['username'])) {
                    $msg = $update['message'];
                    if (strtolower($msg['chat']['username']) == strtolower($channel_username)) {
                        $messages[] = [
                            'id'      => $msg['message_id'],
                            'date'    => $msg['date'],
                            'text'    => $msg['text'] ?? '',
                            'media'   => isset($msg['photo']) ? $this->get_attachment_url($bot_token, $msg['photo']) : null,
                            'deleted' => false
                        ];
                        if (count($messages) >= $limit) break;
                    }
                }
            }
        }
        return $messages;
    }

    private function get_attachment_url($bot_token, $photo) {
        // 'photo' may be an array of photo sizes, get the last element (highest res)
        $file_id = end($photo)['file_id'] ?? null;
        if (!$file_id) return null;
        // Get file path
        $resp = wp_remote_get("https://api.telegram.org/bot{$bot_token}/getFile?file_id=" . $file_id);
        if (is_wp_error($resp)) return null;
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if ($body['ok'] ?? false) {
            $file_path = $body['result']['file_path'];
            return "https://api.telegram.org/file/bot{$bot_token}/{$file_path}";
        }
        return null;
    }
}