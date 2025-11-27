<?php
namespace DFX\TelegramChannelFeed;

if (!defined('ABSPATH')) exit;

/**
 * Webhook handler for receiving Telegram updates.
 * 
 * Telegram webhooks must respond with HTTP 200 in less than 2 seconds,
 * so this handler is optimized for speed.
 */
class Webhook {
    private static $instance = null;
    
    /** @var string REST API namespace */
    const REST_NAMESPACE = 'dfx-tg-feed/v1';
    
    /** @var string Webhook endpoint route */
    const WEBHOOK_ROUTE = 'webhook';
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the webhook handler
     */
    public function init() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Register REST API routes for the webhook
     */
    public function register_rest_routes() {
        register_rest_route(self::REST_NAMESPACE, '/' . self::WEBHOOK_ROUTE, [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'verify_webhook'],
        ]);
    }
    
    /**
     * Verify the webhook request authenticity
     * 
     * @param \WP_REST_Request $request The request object
     * @return bool Whether the request is valid
     */
    public function verify_webhook($request) {
        // Get the secret token from settings
        $secret_token = get_option('dfx_tg_feed_webhook_secret', '');
        
        // If no secret token is set, allow all requests (but log a warning)
        if (empty($secret_token)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DFX Telegram Feed: Webhook received without secret token verification');
            }
            return true;
        }
        
        // Check the X-Telegram-Bot-Api-Secret-Token header
        $provided_token = $request->get_header('X-Telegram-Bot-Api-Secret-Token');
        
        if (empty($provided_token)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DFX Telegram Feed: Webhook missing secret token header');
            }
            return false;
        }
        
        // Use hash_equals for timing-safe comparison
        return hash_equals($secret_token, $provided_token);
    }
    
    /**
     * Handle incoming webhook from Telegram
     * 
     * IMPORTANT: This must respond quickly (< 2 seconds) to satisfy Telegram's requirements.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response
     */
    public function handle_webhook($request) {
        // Get JSON body
        $update = $request->get_json_params();
        
        if (empty($update)) {
            return new \WP_REST_Response(['ok' => false, 'error' => 'Empty request'], 200);
        }
        
        // Process the update synchronously but quickly
        $this->process_update($update);
        
        // Always return 200 OK to Telegram immediately
        return new \WP_REST_Response(['ok' => true], 200);
    }
    
    /**
     * Process a Telegram update
     * 
     * @param array $update The update data from Telegram
     */
    private function process_update($update) {
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        if (empty($bot_token)) {
            return;
        }
        
        // Note: Message deletion notifications are only available for Telegram Business accounts
        // (deleted_business_messages). Standard bots do not receive deletion updates.
        // The plugin handles deletions via manual sync/refresh functionality instead.
        
        $msg = null;
        $channel_identifier = null;
        $is_edited = false;
        
        // Check for channel_post (channel messages)
        if (!empty($update['channel_post'])) {
            $msg = $update['channel_post'];
            $channel_identifier = $msg['chat']['username'] ?? $msg['chat']['id'] ?? null;
        }
        // Check for edited_channel_post (edited channel messages)
        elseif (!empty($update['edited_channel_post'])) {
            $msg = $update['edited_channel_post'];
            $channel_identifier = $msg['chat']['username'] ?? $msg['chat']['id'] ?? null;
            $is_edited = true;
        }
        // Check for regular message (if bot receives direct messages)
        elseif (!empty($update['message']) && isset($update['message']['chat'])) {
            $msg = $update['message'];
            $channel_identifier = $msg['chat']['username'] ?? $msg['chat']['id'] ?? null;
        }
        // Check for edited_message
        elseif (!empty($update['edited_message']) && isset($update['edited_message']['chat'])) {
            $msg = $update['edited_message'];
            $channel_identifier = $msg['chat']['username'] ?? $msg['chat']['id'] ?? null;
            $is_edited = true;
        }
        
        if (!$msg || !$channel_identifier) {
            return;
        }
        
        // Format channel identifier with @ prefix if it's a username
        $channel = is_numeric($channel_identifier) ? (string) $channel_identifier : '@' . $channel_identifier;
        
        // Extract message data
        $message_data = $this->extract_message_data($msg, $bot_token);
        
        if (empty($message_data)) {
            return;
        }
        
        // Store or update the message in the database
        if ($is_edited) {
            PostType::instance()->update_message($channel, $message_data);
        } else {
            PostType::instance()->store_message($channel, $message_data);
        }
    }
    
    /**
     * Process deleted messages update
     * 
     * @param array $deleted_info Deleted messages info from Telegram
     */
    private function process_deleted_messages($deleted_info) {
        if (empty($deleted_info['message_ids']) || empty($deleted_info['chat'])) {
            return;
        }
        
        $chat = $deleted_info['chat'];
        $channel_identifier = $chat['username'] ?? $chat['id'] ?? null;
        
        if (!$channel_identifier) {
            return;
        }
        
        // Format channel identifier with @ prefix if it's a username
        $channel = is_numeric($channel_identifier) ? (string) $channel_identifier : '@' . $channel_identifier;
        
        // Delete each message from our database
        foreach ($deleted_info['message_ids'] as $message_id) {
            PostType::instance()->delete_message($channel, $message_id);
        }
    }
    
    /**
     * Extract message data from a Telegram message object
     * 
     * @param array $msg The Telegram message object
     * @param string $bot_token The bot token for API calls
     * @return array|null Message data array or null if invalid
     */
    private function extract_message_data($msg, $bot_token) {
        // Extract text content
        $text = $msg['text'] ?? $msg['caption'] ?? '';
        
        // Get entities for formatting
        $entities = $msg['entities'] ?? $msg['caption_entities'] ?? [];
        
        // Get media URL (photo, video thumbnail, or sticker)
        $media = null;
        $is_sticker = false;
        $sticker_type = null;
        $emoji = null;
        $file_id = null;
        
        if (isset($msg['photo'])) {
            $media = $this->get_photo_url($bot_token, $msg['photo']);
        } elseif (isset($msg['sticker'])) {
            $is_sticker = true;
            $sticker_info = $this->get_sticker_info($bot_token, $msg['sticker']);
            $media = $sticker_info['url'] ?? null;
            $sticker_type = $sticker_info['type'] ?? null;
            $emoji = $msg['sticker']['emoji'] ?? null;
            $file_id = $msg['sticker']['file_id'] ?? null;
        } elseif (isset($msg['video']) && isset($msg['video']['thumb'])) {
            $media = $this->get_file_url($bot_token, $msg['video']['thumb']['file_id']);
        } elseif (isset($msg['animation']) && isset($msg['animation']['thumb'])) {
            $media = $this->get_file_url($bot_token, $msg['animation']['thumb']['file_id']);
        }
        
        // Skip messages that have no text AND no media (empty messages)
        if (empty($text) && empty($media)) {
            return null;
        }
        
        // Get author information if available
        $author = null;
        if (isset($msg['from'])) {
            $author = [
                'first_name' => $msg['from']['first_name'] ?? '',
                'last_name' => $msg['from']['last_name'] ?? '',
                'username' => $msg['from']['username'] ?? '',
            ];
        } elseif (isset($msg['author_signature'])) {
            $author = [
                'signature' => $msg['author_signature'],
            ];
        }
        
        return [
            'id' => $msg['message_id'],
            'date' => $msg['date'],
            'edit_date' => $msg['edit_date'] ?? null, // Telegram provides this for edited messages
            'text' => $text,
            'entities' => $entities,
            'media' => $media,
            'sticker' => $is_sticker,
            'sticker_type' => $sticker_type,
            'emoji' => $emoji,
            'file_id' => $file_id,
            'author' => $author,
            'deleted' => false,
        ];
    }
    
    /**
     * Get URL for a photo attachment
     * 
     * @param string $bot_token Bot token
     * @param array $photo Photo array from Telegram
     * @return string|null Photo URL or null
     */
    private function get_photo_url($bot_token, $photo) {
        // Get the highest resolution photo (last in array)
        $file_id = end($photo)['file_id'] ?? null;
        if (!$file_id) {
            return null;
        }
        return $this->get_file_url($bot_token, $file_id);
    }
    
    /**
     * Get sticker information
     * 
     * @param string $bot_token Bot token
     * @param array $sticker Sticker object from Telegram
     * @return array Sticker info with url and type
     */
    private function get_sticker_info($bot_token, $sticker) {
        $file_id = $sticker['file_id'] ?? null;
        if (!$file_id) {
            return ['url' => null, 'type' => null];
        }
        
        // Determine sticker type
        $is_animated = $sticker['is_animated'] ?? false;
        $is_video = $sticker['is_video'] ?? false;
        
        $type = 'static';
        if ($is_animated) {
            $type = 'tgs';
        } elseif ($is_video) {
            $type = 'webm';
        }
        
        $url = $this->get_file_url($bot_token, $file_id);
        
        return ['url' => $url, 'type' => $type];
    }
    
    /**
     * Get file URL from Telegram
     * 
     * @param string $bot_token Bot token
     * @param string $file_id File ID
     * @return string|null File URL or null
     */
    private function get_file_url($bot_token, $file_id) {
        if (!$file_id) {
            return null;
        }
        
        // Use non-blocking request with short timeout for speed
        $response = wp_remote_get(
            "https://api.telegram.org/bot{$bot_token}/getFile?file_id=" . urlencode($file_id),
            ['timeout' => 5]
        );
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (($body['ok'] ?? false) && isset($body['result']['file_path'])) {
            return "https://api.telegram.org/file/bot{$bot_token}/{$body['result']['file_path']}";
        }
        
        return null;
    }
    
    /**
     * Get the webhook URL for this site
     * 
     * @return string The webhook URL
     */
    public static function get_webhook_url() {
        return rest_url(self::REST_NAMESPACE . '/' . self::WEBHOOK_ROUTE);
    }
    
    /**
     * Register webhook with Telegram
     * 
     * @param string $bot_token The bot token
     * @param string $secret_token Optional secret token for verification
     * @return array Result with success status and message
     */
    public static function register_webhook($bot_token, $secret_token = '') {
        $webhook_url = self::get_webhook_url();
        
        $params = [
            'url' => $webhook_url,
            // Include all relevant update types:
            // - message: regular messages
            // - edited_message: edited regular messages
            // - channel_post: channel messages
            // - edited_channel_post: edited channel messages
            'allowed_updates' => [
                'message',
                'edited_message',
                'channel_post',
                'edited_channel_post',
            ],
        ];
        
        if (!empty($secret_token)) {
            $params['secret_token'] = $secret_token;
        }
        
        $response = wp_remote_post(
            "https://api.telegram.org/bot{$bot_token}/setWebhook",
            [
                'body' => $params,
                'timeout' => 30,
            ]
        );
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['ok'] ?? false) {
            return [
                'success' => true,
                'message' => $body['description'] ?? 'Webhook registered successfully',
            ];
        }
        
        return [
            'success' => false,
            'message' => $body['description'] ?? 'Failed to register webhook',
        ];
    }
    
    /**
     * Unregister (delete) webhook from Telegram
     * 
     * @param string $bot_token The bot token
     * @return array Result with success status and message
     */
    public static function unregister_webhook($bot_token) {
        $response = wp_remote_post(
            "https://api.telegram.org/bot{$bot_token}/deleteWebhook",
            ['timeout' => 30]
        );
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['ok'] ?? false) {
            return [
                'success' => true,
                'message' => $body['description'] ?? 'Webhook removed successfully',
            ];
        }
        
        return [
            'success' => false,
            'message' => $body['description'] ?? 'Failed to remove webhook',
        ];
    }
    
    /**
     * Get current webhook info from Telegram
     * 
     * @param string $bot_token The bot token
     * @return array Webhook info or error
     */
    public static function get_webhook_info($bot_token) {
        $response = wp_remote_get(
            "https://api.telegram.org/bot{$bot_token}/getWebhookInfo",
            ['timeout' => 30]
        );
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['ok'] ?? false) {
            return [
                'success' => true,
                'info' => $body['result'],
            ];
        }
        
        return [
            'success' => false,
            'message' => $body['description'] ?? 'Failed to get webhook info',
        ];
    }
}
