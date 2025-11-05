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
            'show_in_menu'        => 'options-general.php',
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
        $new_columns['message_id'] = __('Message ID', 'dfx-tg-feed');
        $new_columns['date'] = __('Posted Date', 'dfx-tg-feed');
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'channel':
                echo esc_html(get_post_meta($post_id, '_tg_channel', true));
                break;
            case 'message_id':
                echo esc_html(get_post_meta($post_id, '_tg_message_id', true));
                break;
        }
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
            'post_date' => date('Y-m-d H:i:s', $message_data['date']),
        ]);
        
        if ($post_id) {
            update_post_meta($post_id, '_tg_channel', $channel);
            update_post_meta($post_id, '_tg_message_id', $message_data['id']);
            update_post_meta($post_id, '_tg_date', $message_data['date']);
            if (!empty($message_data['media'])) {
                update_post_meta($post_id, '_tg_media', $message_data['media']);
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
                'media' => get_post_meta($post->ID, '_tg_media', true),
                'deleted' => false,
            ];
        }
        
        return $messages;
    }
}
