<?php
namespace DFXTgFeed;

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
        $bot_token = get_option('dfxtgfeed_bot_token');
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
                // Recognize both new and edited channel/regular messages.
                // edited_* updates carry the full new payload + an edit_date,
                // so we treat them like channel_post and let store_message
                // update the existing post in place.
                $msg = null;
                $chat_identifier = null;
                if (!empty($update['channel_post'])) {
                    $msg = $update['channel_post'];
                } elseif (!empty($update['edited_channel_post'])) {
                    $msg = $update['edited_channel_post'];
                } elseif (!empty($update['message']) && isset($update['message']['chat'])) {
                    $msg = $update['message'];
                } elseif (!empty($update['edited_message']) && isset($update['edited_message']['chat'])) {
                    $msg = $update['edited_message'];
                }
                if ($msg) {
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

                        // Capture the displayable media file_id for any media type.
                        // We never store or render Telegram file URLs — those embed the bot
                        // token (https://api.telegram.org/file/bot<TOKEN>/...). Instead we
                        // store the file_id and resolve to bytes server-side via the media
                        // proxy at render time.
                        $media_file_id = null;
                        if (isset($msg['photo']) && is_array($msg['photo'])) {
                            // 'photo' is an array of size variants; last is highest res.
                            $largest = end($msg['photo']);
                            $media_file_id = $largest['file_id'] ?? null;
                        } elseif (isset($msg['sticker'])) {
                            $media_file_id = $msg['sticker']['file_id'] ?? null;
                        } elseif (isset($msg['video'])) {
                            $thumb = $msg['video']['thumbnail'] ?? $msg['video']['thumb'] ?? null;
                            if ($thumb) {
                                $media_file_id = $thumb['file_id'] ?? null;
                            }
                        } elseif (isset($msg['animation'])) {
                            $thumb = $msg['animation']['thumbnail'] ?? $msg['animation']['thumb'] ?? null;
                            if ($thumb) {
                                $media_file_id = $thumb['file_id'] ?? null;
                            }
                        }

                        // Skip messages that have no text AND no media (empty messages)
                        if (empty($text) && empty($media_file_id)) {
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
                        
                        $sticker_type = null;
                        if (isset($msg['sticker'])) {
                            $is_animated = $msg['sticker']['is_animated'] ?? false;
                            $is_video = $msg['sticker']['is_video'] ?? false;
                            if ($is_animated) {
                                $sticker_type = 'tgs';
                            } elseif ($is_video) {
                                $sticker_type = 'webm';
                            } else {
                                $sticker_type = 'static';
                            }
                        }

                        $message_data = [
                            'id'        => $msg['message_id'],
                            'date'      => $msg['date'],
                            'edit_date' => $msg['edit_date'] ?? null,
                            'text'      => $text,
                            'entities'  => $entities,
                            // 'media' is a presence flag now ('1' or null), not a URL.
                            // Templates render a proxy URL built from 'file_id'.
                            'media'     => !empty($media_file_id) ? '1' : null,
                            'sticker'   => isset($msg['sticker']),
                            'sticker_type' => $sticker_type,
                            'emoji'     => $msg['sticker']['emoji'] ?? null,
                            'file_id'   => $media_file_id,
                            'author'    => $author,
                            'deleted'   => false,
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

    /**
     * Scrape the public channel preview (t.me/s/<username>) to learn which
     * message IDs are currently visible. Used as an opt-in deletion-detection
     * mechanism — the Bot API does not notify about channel message deletions,
     * but the public preview reflects the live state, so any locally-stored
     * message that is no longer in the preview was probably deleted.
     *
     * Limitations:
     *  - Public channels only. Private channels have no t.me/s/ preview.
     *  - The HTML is undocumented and may change at any time. Defensive: if
     *    the preview parses to zero messages, we abort rather than treating
     *    everything as deleted.
     *  - Heavy: walks back via ?before=<id> until either max_messages is
     *    reached, the preview returns nothing new, or a hard cap on requests
     *    is hit.
     *
     * Returns an array of integer message IDs on success, or WP_Error on
     * fetch failure / non-public channel.
     */
    public function fetch_public_preview_ids($channel, $max_messages = 200) {
        $username = ltrim((string) $channel, '@');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            return new \WP_Error(
                'dfxtgfeed_invalid_channel',
                __('Channel must be a public username (alphanumerics and underscores).', 'dfxtgfeed')
            );
        }

        $ids        = [];
        $before     = null;
        $max_pages  = (int) max(1, ceil($max_messages / 16)); // typical preview page ~16 msgs
        $pages_done = 0;

        while ($pages_done < $max_pages && count($ids) < $max_messages) {
            $url = 'https://t.me/s/' . $username;
            if ($before !== null) {
                $url .= '?before=' . (int) $before;
            }

            $response = wp_remote_get($url, [
                'timeout'     => 15,
                'redirection' => 0,
                'sslverify'   => true,
                'headers'     => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; DFX TG Feed/' . DFXTGFEED_VER . ')',
                ],
            ]);
            if (is_wp_error($response)) {
                return $response;
            }
            $code = wp_remote_retrieve_response_code($response);

            if ($code === 301 || $code === 302) {
                // t.me redirects to the join page when the channel is private
                // or the /s/ preview is unavailable.
                return new \WP_Error(
                    'dfxtgfeed_not_public',
                    __('Channel has no public preview. Deletion sync only works on public channels.', 'dfxtgfeed')
                );
            }
            if ($code !== 200) {
                return new \WP_Error(
                    'dfxtgfeed_fetch_failed',
                    sprintf(__('t.me returned HTTP %d.', 'dfxtgfeed'), $code)
                );
            }

            $html = wp_remote_retrieve_body($response);
            $pattern = '/data-post="' . preg_quote($username, '/') . '\/(\d+)"/i';
            if (!preg_match_all($pattern, $html, $matches)) {
                // Zero matches on the first page is treated as "preview empty
                // or layout changed" — abort rather than declare everything
                // deleted. This is the critical defensive check.
                if ($pages_done === 0) {
                    return new \WP_Error(
                        'dfxtgfeed_empty_preview',
                        __('Public preview returned no recognisable messages. Aborting to avoid false positives.', 'dfxtgfeed')
                    );
                }
                break;
            }

            $page_ids = array_map('intval', $matches[1]);
            $page_ids = array_values(array_unique($page_ids));

            $new_ids = array_diff($page_ids, $ids);
            if (empty($new_ids)) {
                // Pagination didn't move forward; we've hit the floor.
                break;
            }

            $ids = array_merge($ids, $new_ids);
            $before = min($page_ids);
            $pages_done++;
        }

        sort($ids);
        return $ids;
    }

    /**
     * Resolve a Telegram file_id to a token-bearing fetchable URL.
     * Server-side only — never include the return value in HTML, JS, or any
     * client-visible response: the URL embeds the bot token. Use the media
     * proxy (admin-ajax dfxtgfeed_proxy_media) for client-facing rendering.
     *
     * The file_path → URL mapping is cached briefly because Telegram URLs
     * remain valid for a limited time and a getFile call per asset is wasteful.
     */
    public function resolve_file_url($file_id) {
        if (empty($file_id)) {
            return null;
        }
        $bot_token = get_option('dfxtgfeed_bot_token');
        if (!$bot_token) {
            return null;
        }

        $cache_key = 'dfxtgfeed_path_' . md5($file_id);
        $file_path = get_transient($cache_key);
        if ($file_path === false) {
            $resp = wp_remote_get("https://api.telegram.org/bot{$bot_token}/getFile?file_id=" . urlencode($file_id), [
                'timeout' => 15,
            ]);
            if (is_wp_error($resp)) {
                return null;
            }
            $body = json_decode(wp_remote_retrieve_body($resp), true);
            if (empty($body['ok']) || empty($body['result']['file_path'])) {
                return null;
            }
            $file_path = $body['result']['file_path'];
            // Telegram URLs typically remain valid for ~1 hour; refresh sooner.
            set_transient($cache_key, $file_path, 30 * MINUTE_IN_SECONDS);
        }

        return "https://api.telegram.org/file/bot{$bot_token}/{$file_path}";
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

        // Pre-compute the displayable text slice for each entity (used to build
        // hrefs for url/email/phone_number, since the entity payload only has
        // offset/length, not the text).
        foreach ($entities as &$entity) {
            $offset = (int) ($entity['offset'] ?? 0);
            $length = (int) ($entity['length'] ?? 0);
            if ($length > 0 && $offset >= 0 && $offset + $length <= $utf16_length) {
                $slice_utf16 = mb_substr($utf16_text, $offset, $length, 'UTF-16LE');
                $entity['__text'] = mb_convert_encoding($slice_utf16, 'UTF-8', 'UTF-16LE');
            } else {
                $entity['__text'] = '';
            }
        }
        unset($entity);
        
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
                $href = $entity['__text'] ?? '';
                return [
                    'open' => '<a href="' . esc_url($href) . '" target="_blank" rel="noopener noreferrer">',
                    'close' => '</a>',
                ];
            case 'email':
                $href = $entity['__text'] ?? '';
                return [
                    'open' => '<a href="' . esc_attr('mailto:' . sanitize_email($href)) . '">',
                    'close' => '</a>',
                ];
            case 'phone_number':
                $href = preg_replace('/[^0-9+]/', '', $entity['__text'] ?? '');
                return [
                    'open' => '<a href="' . esc_attr('tel:' . $href) . '">',
                    'close' => '</a>',
                ];
            case 'mention':
            case 'hashtag':
            case 'cashtag':
            case 'bot_command':
                return ['open' => '<span class="dfxtgfeed-' . esc_attr($type) . '">', 'close' => '</span>'];
            case 'blockquote':
                return ['open' => '<blockquote>', 'close' => '</blockquote>'];
            default:
                return null;
        }
    }
}