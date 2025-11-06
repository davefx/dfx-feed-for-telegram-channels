<?php
namespace DFX\TelegramChannelFeed;

if (!defined('ABSPATH')) exit;

class PostType {
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    public function register() {
        add_action('init', [$this, 'register_post_type']);
        add_filter('manage_dfx_tg_message_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_dfx_tg_message_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('manage_edit-dfx_tg_message_sortable_columns', [$this, 'set_sortable_columns']);
        add_action('restrict_manage_posts', [$this, 'add_channel_filter']);
        add_filter('parse_query', [$this, 'filter_by_channel']);
        add_filter('post_row_actions', [$this, 'modify_row_actions'], 10, 2);
    }
    
    public function register_post_type() {
        $labels = [
            'name'               => __('Telegram Messages', 'dfx-tg-feed'),
            'singular_name'      => __('Telegram Message', 'dfx-tg-feed'),
            'menu_name'          => __('Telegram Messages', 'dfx-tg-feed'),
            'add_new'            => __('Add New', 'dfx-tg-feed'),
            'add_new_item'       => __('Add New Message', 'dfx-tg-feed'),
            'edit_item'          => __('Edit Message', 'dfx-tg-feed'),
            'new_item'           => __('New Message', 'dfx-tg-feed'),
            'view_item'          => __('View Message', 'dfx-tg-feed'),
            'search_items'       => __('Search Messages', 'dfx-tg-feed'),
            'not_found'          => __('No messages found', 'dfx-tg-feed'),
            'not_found_in_trash' => __('No messages found in trash', 'dfx-tg-feed'),
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 25,
            'capability_type'     => 'post',
            'capabilities'        => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'supports'            => ['title', 'editor'],
            'menu_icon'           => 'dashicons-email-alt',
        ];
        
        register_post_type('dfx_tg_message', $args);
    }
    
    public function set_custom_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Message Preview', 'dfx-tg-feed');
        $new_columns['channel'] = __('Channel', 'dfx-tg-feed');
        $new_columns['author'] = __('Author', 'dfx-tg-feed');
        $new_columns['media'] = __('Media', 'dfx-tg-feed');
        $new_columns['message_id'] = __('Message ID', 'dfx-tg-feed');
        $new_columns['date'] = __('Posted Date', 'dfx-tg-feed');
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'channel':
                $channel = get_post_meta($post_id, '_tg_channel', true);
                echo '<strong>' . esc_html($channel) . '</strong>';
                break;
            case 'message_id':
                echo '<code>' . esc_html(get_post_meta($post_id, '_tg_message_id', true)) . '</code>';
                break;
            case 'media':
                $media = get_post_meta($post_id, '_tg_media', true);
                $is_sticker = get_post_meta($post_id, '_tg_is_sticker', true);
                if ($is_sticker) {
                    echo '<span class="dashicons dashicons-format-image" title="Sticker"></span>';
                } elseif ($media) {
                    echo '<span class="dashicons dashicons-format-gallery" title="Has media"></span>';
                } else {
                    echo '—';
                }
                break;
            case 'author':
                $author = get_post_meta($post_id, '_tg_author', true);
                if ($author) {
                    $display = $author['name'] ?? '';
                    if (!empty($author['username'])) {
                        $display .= ' (@' . $author['username'] . ')';
                    }
                    echo esc_html($display);
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    public function set_sortable_columns($columns) {
        $columns['channel'] = 'channel';
        $columns['message_id'] = 'message_id';
        return $columns;
    }
    
    public function add_channel_filter() {
        global $typenow;
        
        if ($typenow !== 'dfx_tg_message') {
            return;
        }
        
        // Get all unique channels
        global $wpdb;
        $channels = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_tg_channel' 
            ORDER BY meta_value ASC
        ");
        
        $current_channel = isset($_GET['channel_filter']) ? $_GET['channel_filter'] : '';
        
        echo '<select name="channel_filter">';
        echo '<option value="">' . __('All Channels', 'dfx-tg-feed') . '</option>';
        foreach ($channels as $channel) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($channel),
                selected($current_channel, $channel, false),
                esc_html($channel)
            );
        }
        echo '</select>';
    }
    
    public function filter_by_channel($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'dfx_tg_message' && isset($_GET['channel_filter']) && $_GET['channel_filter'] !== '') {
            $query->query_vars['meta_key'] = '_tg_channel';
            $query->query_vars['meta_value'] = sanitize_text_field($_GET['channel_filter']);
        }
    }
    
    public function modify_row_actions($actions, $post) {
        if ($post->post_type === 'dfx_tg_message') {
            // Remove "Quick Edit" since posts are synced from Telegram
            unset($actions['inline hide-if-no-js']);
            
            // Add custom action to view in Telegram
            $message_id = get_post_meta($post->ID, '_tg_message_id', true);
            $channel = get_post_meta($post->ID, '_tg_channel', true);
            
            if ($message_id && $channel) {
                // Remove @ if present
                $channel_clean = ltrim($channel, '@');
                $actions['view_telegram'] = sprintf(
                    '<a href="https://t.me/%s/%s" target="_blank">%s</a>',
                    esc_attr($channel_clean),
                    esc_attr($message_id),
                    __('View in Telegram', 'dfx-tg-feed')
                );
            }
        }
        
        return $actions;
    }
    
    /**
     * Store a message in the database
     */
    public function store_message($channel, $message_data) {
        // Check if message already exists
        $existing = get_posts([
            'post_type' => 'dfx_tg_message',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_tg_channel',
                    'value' => $channel,
                ],
                [
                    'key' => '_tg_message_id',
                    'value' => $message_data['id'],
                ],
            ],
            'posts_per_page' => 1,
        ]);
        
        if (!empty($existing)) {
            return $existing[0]->ID;
        }
        
        // Create new post
        $text_preview = mb_substr($message_data['text'] ?? '', 0, 100);
        if (strlen($message_data['text'] ?? '') > 100) $text_preview .= '...';
        
        $post_id = wp_insert_post([
            'post_type' => 'dfx_tg_message',
            'post_title' => $text_preview ?: __('(No text)', 'dfx-tg-feed'),
            'post_content' => $message_data['text'] ?? '',
            'post_status' => 'publish',
            'post_date_gmt' => date('Y-m-d H:i:s', $message_data['date']),
        ]);
        
        if ($post_id) {
            update_post_meta($post_id, '_tg_channel', $channel);
            update_post_meta($post_id, '_tg_message_id', $message_data['id']);
            update_post_meta($post_id, '_tg_date', $message_data['date']);
            if (!empty($message_data['media'])) {
                update_post_meta($post_id, '_tg_media', $message_data['media']);
            }
            if (!empty($message_data['sticker'])) {
                update_post_meta($post_id, '_tg_is_sticker', true);
            }
            if (!empty($message_data['sticker_type'])) {
                update_post_meta($post_id, '_tg_sticker_type', $message_data['sticker_type']);
            }
            if (!empty($message_data['emoji'])) {
                update_post_meta($post_id, '_tg_emoji', $message_data['emoji']);
            }
            if (!empty($message_data['file_id'])) {
                update_post_meta($post_id, '_tg_file_id', $message_data['file_id']);
            }
            if (!empty($message_data['entities'])) {
                update_post_meta($post_id, '_tg_entities', $message_data['entities']);
            }
            if (!empty($message_data['author'])) {
                update_post_meta($post_id, '_tg_author', $message_data['author']);
            }
        }
        
        return $post_id;
    }
    
    /**
     * Get messages from database
     */
    public function get_messages($channel, $limit = 10) {
        $posts = get_posts([
            'post_type' => 'dfx_tg_message',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_tg_channel',
                    'value' => $channel,
                ],
            ],
        ]);
        
        $messages = [];
        foreach ($posts as $post) {
            $messages[] = [
                'id' => get_post_meta($post->ID, '_tg_message_id', true),
                'date' => get_post_meta($post->ID, '_tg_date', true),
                'text' => $post->post_content,
                'entities' => get_post_meta($post->ID, '_tg_entities', true) ?: [],
                'media' => get_post_meta($post->ID, '_tg_media', true),
                'sticker' => get_post_meta($post->ID, '_tg_is_sticker', true),
                'sticker_type' => get_post_meta($post->ID, '_tg_sticker_type', true),
                'emoji' => get_post_meta($post->ID, '_tg_emoji', true),
                'file_id' => get_post_meta($post->ID, '_tg_file_id', true),
                'author' => get_post_meta($post->ID, '_tg_author', true),
                'deleted' => false,
            ];
        }
        
        return $messages;
    }
    
    /**
     * Refresh messages from Telegram API and detect deleted messages
     */
    public function refresh_messages($channel, $limit) {
        // Fetch new messages from API (this will also store them)
        $messages = API::instance()->fetch_channel_messages($channel, $limit);
        
        // Detect deleted messages by comparing with stored messages
        $old = $this->get_messages($channel, 200);
        $deleted_ids = [];
        if (!empty($old)) {
            $old_ids = array_column($old, 'id');
            $new_ids = array_column($messages, 'id');
            $deleted_ids = array_diff($old_ids, $new_ids);
        }
        
        return ['messages' => $messages, 'deleted_ids' => $deleted_ids];
    }
    
    /**
     * AJAX handler to refresh messages for admin panel
     */
    public function ajax_refresh_messages() {
        check_ajax_referer('dfx_tg_feed_refresh');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission.');
        }
        
        $channel = sanitize_text_field($_POST['channel'] ?? '');
        $limit = intval(get_option('dfx_tg_feed_default_count', 10));
        $result = $this->refresh_messages($channel, $limit);
        
        wp_send_json_success($result);
    }
}
