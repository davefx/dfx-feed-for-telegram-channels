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
        
        // Clean the @ if present, or use as-is if it's a channel ID
        $channel_id = $channel_username;
        if (strpos($channel_username, '@') === 0) {
            $channel_id = substr($channel_username, 1);
        }

        // Bot API: getUpdates returns both messages and channel_post updates
        $api_url = "https://api.telegram.org/bot" . $bot_token . "/getUpdates";
        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            return [];
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $messages = [];
        
        if (isset($body['ok']) && $body['ok']) {
            foreach (array_reverse($body['result']) as $update) {
                $msg = null;
                $chat_identifier = null;
                
                // Check for channel_post (channel messages)
                if (!empty($update['channel_post'])) {
                    $msg = $update['channel_post'];
                    $chat_identifier = $msg['chat']['username'] ?? $msg['chat']['id'] ?? null;
                }
                // Check for regular message (if bot receives direct messages)
                elseif (!empty($update['message']) && isset($update['message']['chat'])) {
                    $msg = $update['message'];
                    $chat_identifier = $msg['chat']['username'] ?? $msg['chat']['id'] ?? null;
                }
                
                if ($msg && $chat_identifier) {
                    // Match by username or channel ID
                    $matches = false;
                    if (is_numeric($channel_id)) {
                        // Channel ID match (e.g., -1001234567890)
                        $matches = ($chat_identifier == $channel_id);
                    } else {
                        // Username match
                        $matches = (strtolower($chat_identifier) == strtolower($channel_id));
                    }
                    
                    if ($matches) {
                        // Extract text content
                        $text = $msg['text'] ?? $msg['caption'] ?? '';
                        
                        // Get entities for formatting
                        $entities = $msg['entities'] ?? $msg['caption_entities'] ?? [];
                        
                        // Get media URL (photo, video thumbnail, or sticker)
                        $media = null;
                        if (isset($msg['photo'])) {
                            $media = $this->get_attachment_url($bot_token, $msg['photo']);
                        } elseif (isset($msg['sticker'])) {
                            // For stickers, get the thumbnail or file
                            $media = $this->get_sticker_url($bot_token, $msg['sticker']);
                        } elseif (isset($msg['video'])) {
                            // For videos, get thumbnail
                            if (isset($msg['video']['thumb'])) {
                                $media = $this->get_file_url($bot_token, $msg['video']['thumb']['file_id']);
                            }
                        } elseif (isset($msg['animation'])) {
                            // For GIFs/animations, get thumbnail
                            if (isset($msg['animation']['thumb'])) {
                                $media = $this->get_file_url($bot_token, $msg['animation']['thumb']['file_id']);
                            }
                        }
                        
                        // Skip messages that have no text AND no media (empty messages)
                        if (empty($text) && empty($media)) {
                            continue;
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
                        
                        $message_data = [
                            'id'      => $msg['message_id'],
                            'date'    => $msg['date'],
                            'text'    => $text,
                            'entities' => $entities,
                            'media'   => $media,
                            'sticker' => isset($msg['sticker']),
                            'emoji'   => $msg['sticker']['emoji'] ?? null,
                            'author'  => $author,
                            'deleted' => false
                        ];
                        
                        $messages[] = $message_data;
                        
                        // Store in database
                        PostType::instance()->store_message($channel_username, $message_data);
                        
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
        return $this->get_file_url($bot_token, $file_id);
    }
    
    private function get_sticker_url($bot_token, $sticker) {
        // Get sticker file - stickers have file_id directly
        $file_id = $sticker['file_id'] ?? null;
        if (!$file_id) return null;
        return $this->get_file_url($bot_token, $file_id);
    }
    
    private function get_file_url($bot_token, $file_id) {
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
    
    /**
     * Format text with Telegram entities (bold, italic, code, etc.)
     */
    public static function format_text_with_entities($text, $entities) {
        if (empty($text) || empty($entities)) {
            return esc_html($text);
        }
        
        // Sort entities by offset in reverse order to apply from end to start
        usort($entities, function($a, $b) {
            return $b['offset'] - $a['offset'];
        });
        
        // Convert to UTF-16 for proper offset handling (Telegram uses UTF-16)
        $text_utf16 = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        
        foreach ($entities as $entity) {
            $offset = $entity['offset'];
            $length = $entity['length'];
            $type = $entity['type'];
            
            // Extract the text portion (in UTF-16)
            $start_byte = $offset * 2; // UTF-16LE uses 2 bytes per character
            $length_byte = $length * 2;
            $portion_utf16 = substr($text_utf16, $start_byte, $length_byte);
            $portion = mb_convert_encoding($portion_utf16, 'UTF-8', 'UTF-16LE');
            
            // Apply formatting based on entity type
            $formatted = '';
            switch ($type) {
                case 'bold':
                    $formatted = '<strong>' . esc_html($portion) . '</strong>';
                    break;
                case 'italic':
                    $formatted = '<em>' . esc_html($portion) . '</em>';
                    break;
                case 'underline':
                    $formatted = '<u>' . esc_html($portion) . '</u>';
                    break;
                case 'strikethrough':
                    $formatted = '<s>' . esc_html($portion) . '</s>';
                    break;
                case 'code':
                    $formatted = '<code>' . esc_html($portion) . '</code>';
                    break;
                case 'pre':
                    $language = $entity['language'] ?? '';
                    $formatted = '<pre' . ($language ? ' class="language-' . esc_attr($language) . '"' : '') . '><code>' . esc_html($portion) . '</code></pre>';
                    break;
                case 'text_link':
                    $url = $entity['url'] ?? '';
                    $formatted = '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($portion) . '</a>';
                    break;
                case 'url':
                case 'email':
                case 'phone_number':
                    $formatted = '<a href="' . esc_url($portion) . '" target="_blank" rel="noopener noreferrer">' . esc_html($portion) . '</a>';
                    break;
                case 'mention':
                case 'hashtag':
                case 'cashtag':
                case 'bot_command':
                    $formatted = '<span class="tg-' . esc_attr($type) . '">' . esc_html($portion) . '</span>';
                    break;
                case 'blockquote':
                    $formatted = '<blockquote>' . esc_html($portion) . '</blockquote>';
                    break;
                default:
                    $formatted = esc_html($portion);
            }
            
            // Replace in UTF-16 string
            $before = substr($text_utf16, 0, $start_byte);
            $after = substr($text_utf16, $start_byte + $length_byte);
            $formatted_utf16 = mb_convert_encoding($formatted, 'UTF-16LE', 'UTF-8');
            $text_utf16 = $before . $formatted_utf16 . $after;
        }
        
        // Convert back to UTF-8
        $result = mb_convert_encoding($text_utf16, 'UTF-8', 'UTF-16LE');
        
        // Handle line breaks
        return nl2br($result);
    }
}