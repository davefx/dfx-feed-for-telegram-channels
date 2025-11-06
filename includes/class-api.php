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
                        $has_media_field = false;
                        
                        if (isset($msg['photo'])) {
                            $has_media_field = true;
                            $media = $this->get_attachment_url($bot_token, $msg['photo']);
                        } elseif (isset($msg['sticker'])) {
                            $has_media_field = true;
                            // For stickers, get the file and determine type
                            $sticker_info = $this->get_sticker_info($bot_token, $msg['sticker']);
                            $media = $sticker_info['url'] ?? null;
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log('DFX Telegram Feed: Processing sticker message. Media URL: ' . ($media ? $media : 'NULL'));
                                error_log('DFX Telegram Feed: Sticker type: ' . ($sticker_info['type'] ?? 'unknown'));
                                error_log('DFX Telegram Feed: Sticker emoji: ' . ($msg['sticker']['emoji'] ?? 'no emoji'));
                            }
                        } elseif (isset($msg['video'])) {
                            $has_media_field = true;
                            // For videos, get thumbnail
                            if (isset($msg['video']['thumb'])) {
                                $media = $this->get_file_url($bot_token, $msg['video']['thumb']['file_id']);
                            }
                        } elseif (isset($msg['animation'])) {
                            $has_media_field = true;
                            // For GIFs/animations, get thumbnail
                            if (isset($msg['animation']['thumb'])) {
                                $media = $this->get_file_url($bot_token, $msg['animation']['thumb']['file_id']);
                            }
                        }
                        
                        // Skip messages that have media fields but inaccessible files (likely deleted)
                        if ($has_media_field && empty($media)) {
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log('DFX Telegram Feed: Skipping message ' . $msg['message_id'] . ' - has media field but file is inaccessible (likely deleted)');
                            }
                            continue;
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
                        
                        $sticker_info = isset($msg['sticker']) ? ['type' => null] : [];
                        if (isset($msg['sticker']) && !empty($media)) {
                            // Determine sticker type from the sticker info we got earlier
                            $is_animated = $msg['sticker']['is_animated'] ?? false;
                            $is_video = $msg['sticker']['is_video'] ?? false;
                            
                            if ($is_animated) {
                                $sticker_info['type'] = 'tgs';
                            } elseif ($is_video) {
                                $sticker_info['type'] = 'webm';
                            } else {
                                $sticker_info['type'] = 'static';
                            }
                        }
                        
                        $message_data = [
                            'id'      => $msg['message_id'],
                            'date'    => $msg['date'],
                            'text'    => $text,
                            'entities' => $entities,
                            'media'   => $media,
                            'sticker' => isset($msg['sticker']),
                            'sticker_type' => $sticker_info['type'] ?? null,
                            'emoji'   => $msg['sticker']['emoji'] ?? null,
                            'file_id' => $msg['sticker']['file_id'] ?? null,
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
    
    private function get_sticker_info($bot_token, $sticker) {
        // Get sticker file - stickers have file_id directly
        $file_id = $sticker['file_id'] ?? null;
        if (!$file_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DFX Telegram Feed: Sticker has no file_id');
            }
            return ['url' => null, 'type' => null, 'file_id' => null];
        }
        
        // Determine sticker type
        $is_animated = $sticker['is_animated'] ?? false;
        $is_video = $sticker['is_video'] ?? false;
        
        $type = 'static'; // PNG/WEBP
        if ($is_animated) {
            $type = 'tgs'; // Lottie animation
        } elseif ($is_video) {
            $type = 'webm'; // Video sticker
        }
        
        $url = $this->get_file_url($bot_token, $file_id);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DFX Telegram Feed: Sticker URL fetched: ' . ($url ? $url : 'FAILED') . ' (type: ' . $type . ', file_id: ' . $file_id . ')');
        }
        
        return ['url' => $url, 'type' => $type, 'file_id' => $file_id];
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
            return nl2br(esc_html($text));
        }
        
        // Convert text to UTF-16 units (Telegram uses UTF-16 for offsets)
        $utf16_text = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        $utf16_length = mb_strlen($utf16_text, 'UTF-16LE');
        
        // Sort entities by start position, then by length (longer first for nested)
        usort($entities, function($a, $b) {
            if ($a['offset'] !== $b['offset']) {
                return $a['offset'] - $b['offset'];
            }
            // If same start, longer entities first (so nested ones are inside)
            return $b['length'] - $a['length'];
        });
        
        // Create array of segments with their formatting
        $segments = [];
        $last_pos = 0;
        
        // Track which entities are active at each position
        $position_entities = [];
        foreach ($entities as $entity) {
            $start = $entity['offset'];
            $end = $entity['offset'] + $entity['length'];
            for ($i = $start; $i < $end; $i++) {
                if (!isset($position_entities[$i])) {
                    $position_entities[$i] = [];
                }
                $position_entities[$i][] = $entity;
            }
        }
        
        // Build formatted text character by character
        $result = '';
        $open_tags = [];
        
        for ($pos = 0; $pos < $utf16_length; $pos++) {
            $current_entities = $position_entities[$pos] ?? [];
            
            // Determine which tags need to be closed
            $tags_to_close = [];
            foreach ($open_tags as $tag_info) {
                $still_active = false;
                foreach ($current_entities as $entity) {
                    if ($tag_info['entity'] === $entity) {
                        $still_active = true;
                        break;
                    }
                }
                if (!$still_active) {
                    $tags_to_close[] = $tag_info;
                }
            }
            
            // Close tags in reverse order (LIFO)
            foreach (array_reverse($tags_to_close) as $tag_info) {
                $result .= $tag_info['close'];
                $open_tags = array_filter($open_tags, function($t) use ($tag_info) {
                    return $t !== $tag_info;
                });
            }
            
            // Determine which new tags need to be opened
            $tags_to_open = [];
            foreach ($current_entities as $entity) {
                $already_open = false;
                foreach ($open_tags as $tag_info) {
                    if ($tag_info['entity'] === $entity) {
                        $already_open = true;
                        break;
                    }
                }
                if (!$already_open) {
                    $tags_to_open[] = $entity;
                }
            }
            
            // Open new tags
            foreach ($tags_to_open as $entity) {
                $tag_info = self::get_entity_tags($entity);
                if ($tag_info) {
                    $result .= $tag_info['open'];
                    $open_tags[] = array_merge($tag_info, ['entity' => $entity]);
                }
            }
            
            // Add the character (escaped)
            $char_utf16 = mb_substr($utf16_text, $pos, 1, 'UTF-16LE');
            $char = mb_convert_encoding($char_utf16, 'UTF-8', 'UTF-16LE');
            $result .= esc_html($char);
        }
        
        // Close any remaining open tags
        foreach (array_reverse($open_tags) as $tag_info) {
            $result .= $tag_info['close'];
        }
        
        // Handle line breaks
        return nl2br($result);
    }
    
    /**
     * Get opening and closing HTML tags for an entity
     */
    private static function get_entity_tags($entity) {
        $type = $entity['type'];
        
        switch ($type) {
            case 'bold':
                return ['open' => '<strong>', 'close' => '</strong>'];
            case 'italic':
                return ['open' => '<em>', 'close' => '</em>'];
            case 'underline':
                return ['open' => '<u>', 'close' => '</u>'];
            case 'strikethrough':
                return ['open' => '<s>', 'close' => '</s>'];
            case 'code':
                return ['open' => '<code>', 'close' => '</code>'];
            case 'pre':
                $language = $entity['language'] ?? '';
                $class = $language ? ' class="language-' . esc_attr($language) . '"' : '';
                return ['open' => '<pre' . $class . '><code>', 'close' => '</code></pre>'];
            case 'text_link':
                $url = $entity['url'] ?? '';
                return [
                    'open' => '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">',
                    'close' => '</a>'
                ];
            case 'url':
            case 'email':
            case 'phone_number':
                // For these, we need the actual text, but we'll create a placeholder
                // and fix it in a second pass
                return ['open' => '<a href="#" class="tg-auto-link" data-type="' . esc_attr($type) . '">', 'close' => '</a>'];
            case 'mention':
            case 'hashtag':
            case 'cashtag':
            case 'bot_command':
                return ['open' => '<span class="tg-' . esc_attr($type) . '">', 'close' => '</span>'];
            case 'blockquote':
                return ['open' => '<blockquote>', 'close' => '</blockquote>'];
            default:
                return null;
        }
    }
}