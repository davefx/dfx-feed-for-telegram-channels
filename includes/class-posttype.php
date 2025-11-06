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
        // Register the post type immediately when this method is called
        $this->register_post_type();
        
        // Add filters and actions for post type management
        add_filter('manage_dfx_tg_message_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_dfx_tg_message_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('manage_edit-dfx_tg_message_sortable_columns', [$this, 'set_sortable_columns']);
        add_action('restrict_manage_posts', [$this, 'add_channel_filter']);
        add_action('restrict_manage_posts', [$this, 'add_refresh_button']);
        add_filter('parse_query', [$this, 'filter_by_channel']);
        add_filter('post_row_actions', [$this, 'modify_row_actions'], 10, 2);
        add_action('admin_menu', [$this, 'remove_standalone_menu'], 999);
        add_filter('parent_file', [$this, 'set_parent_file']);
        add_filter('submenu_file', [$this, 'set_submenu_file']);
        add_action('wp_ajax_dfx_tg_hide_message', [$this, 'ajax_hide_message']);
        add_action('wp_ajax_dfx_tg_unhide_message', [$this, 'ajax_unhide_message']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
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
        $new_columns['visibility'] = __('Visibility', 'dfx-tg-feed');
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
            case 'visibility':
                $is_hidden = get_post_meta($post_id, '_tg_hidden', true);
                if ($is_hidden) {
                    echo '<span style="color: #d63638;"><span class="dashicons dashicons-hidden"></span> ' . __('Hidden', 'dfx-tg-feed') . '</span>';
                } else {
                    echo '<span style="color: #00a32a;"><span class="dashicons dashicons-visibility"></span> ' . __('Visible', 'dfx-tg-feed') . '</span>';
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
    
    public function add_refresh_button() {
        global $typenow;
        
        if ($typenow !== 'dfx_tg_message') {
            return;
        }
        
        // Get current channel filter if set
        $current_channel = isset($_GET['channel_filter']) ? sanitize_text_field($_GET['channel_filter']) : '';
        
        // Display refresh button
        ?>
        <button type="button" class="button" id="dfx-tg-refresh-messages" <?php echo empty($current_channel) ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: 2px;"></span>
            <?php _e('Refresh Messages', 'dfx-tg-feed'); ?>
        </button>
        <?php if (empty($current_channel)): ?>
            <p class="description" style="display:inline; margin-left: 5px;">
                <?php _e('Select a channel to enable refresh', 'dfx-tg-feed'); ?>
            </p>
        <?php endif; ?>
        <span id="dfx-tg-refresh-status" style="margin-left: 10px;"></span>
        <?php
    }
    
    public function filter_by_channel($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'dfx_tg_message' && isset($_GET['channel_filter']) && $_GET['channel_filter'] !== '') {
            $query->query_vars['meta_key'] = '_tg_channel';
            $query->query_vars['meta_value'] = sanitize_text_field($_GET['channel_filter']);
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on the post type list page
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
        if ($hook !== 'edit.php' || $post_type !== 'dfx_tg_message') {
            return;
        }
        
        // Enqueue jQuery as dependency
        wp_enqueue_script('jquery');
        
        // Localize script data for AJAX
        wp_localize_script('jquery', 'dfxTgFeedRefresh', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dfx_tg_feed_refresh'),
            'i18n' => [
                'selectChannel' => __('Please select a channel first', 'dfx-tg-feed'),
                'refreshing' => __('Refreshing messages...', 'dfx-tg-feed'),
                'success' => __('Messages refreshed successfully! Reloading...', 'dfx-tg-feed'),
                'errorLabel' => __('Error:', 'dfx-tg-feed'),
                'requestFailed' => __('Request failed:', 'dfx-tg-feed'),
                'unknownError' => __('Unknown error', 'dfx-tg-feed'),
            ],
        ]);
        
        // Add inline CSS for status messages
        wp_add_inline_style('common', '
            .dfx-tg-status-loading { color: #0073aa; }
            .dfx-tg-status-success { color: #46b450; }
            .dfx-tg-status-error { color: #dc3232; }
        ');
        
        // Add inline script for refresh functionality
        wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            var refreshBtn = $('#dfx-tg-refresh-messages');
            var statusSpan = $('#dfx-tg-refresh-status');
            var channelFilter = $('select[name=\"channel_filter\"]');
            
            function setStatus(message, statusClass) {
                var span = $('<span>').addClass(statusClass).text(message);
                statusSpan.empty().append(span);
            }
            
            // Enable/disable button based on channel selection
            channelFilter.on('change', function() {
                if ($(this).val()) {
                    refreshBtn.prop('disabled', false);
                } else {
                    refreshBtn.prop('disabled', true);
                }
            });
            
            refreshBtn.on('click', function(e) {
                e.preventDefault();
                
                var channel = channelFilter.val();
                if (!channel) {
                    alert(dfxTgFeedRefresh.i18n.selectChannel);
                    return;
                }
                
                // Disable button and show loading status
                refreshBtn.prop('disabled', true);
                setStatus(dfxTgFeedRefresh.i18n.refreshing, 'dfx-tg-status-loading');
                
                $.ajax({
                    url: dfxTgFeedRefresh.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dfx_tg_feed_refresh',
                        channel: channel,
                        _ajax_nonce: dfxTgFeedRefresh.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            setStatus(dfxTgFeedRefresh.i18n.success, 'dfx-tg-status-success');
                            // Reload the page after a short delay to show updated messages
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            var errorMsg = response.data || dfxTgFeedRefresh.i18n.unknownError;
                            setStatus(dfxTgFeedRefresh.i18n.errorLabel + ' ' + errorMsg, 'dfx-tg-status-error');
                            refreshBtn.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        setStatus(dfxTgFeedRefresh.i18n.requestFailed + ' ' + error, 'dfx-tg-status-error');
                        refreshBtn.prop('disabled', false);
                    }
                });
            });
        });
        ");
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
            
            // Add hide/unhide action
            $is_hidden = get_post_meta($post->ID, '_tg_hidden', true);
            $nonce = wp_create_nonce('dfx_tg_hide_message_' . $post->ID);
            
            if ($is_hidden) {
                $actions['unhide'] = sprintf(
                    '<a href="#" class="dfx-tg-unhide-message" data-post-id="%d" data-nonce="%s">%s</a>',
                    $post->ID,
                    $nonce,
                    __('Unhide', 'dfx-tg-feed')
                );
            } else {
                $actions['hide'] = sprintf(
                    '<a href="#" class="dfx-tg-hide-message" data-post-id="%d" data-nonce="%s">%s</a>',
                    $post->ID,
                    $nonce,
                    __('Hide', 'dfx-tg-feed')
                );
            }
        }
        
        return $actions;
    }
    
    /**
     * Remove the standalone menu item for the post type
     * since we're adding it under a custom parent menu
     */
    public function remove_standalone_menu() {
        remove_menu_page('edit.php?post_type=dfx_tg_message');
    }
    
    /**
     * Set the parent file for the post type to our custom menu
     */
    public function set_parent_file($parent_file) {
        global $current_screen;
        
        if ($current_screen && $current_screen->post_type === 'dfx_tg_message') {
            $parent_file = Plugin::MENU_SLUG;
        }
        
        return $parent_file;
    }
    
    /**
     * Set the submenu file for the post type
     */
    public function set_submenu_file($submenu_file) {
        global $current_screen;
        
        if ($current_screen && $current_screen->post_type === 'dfx_tg_message') {
            $submenu_file = 'edit.php?post_type=dfx_tg_message';
        }
        
        return $submenu_file;
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
                'relation' => 'AND',
                [
                    'key' => '_tg_channel',
                    'value' => $channel,
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_tg_hidden',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_tg_hidden',
                        'value' => '1',
                        'compare' => '!=',
                    ],
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
        $old = $this->get_all_messages($channel, 200);
        $deleted_ids = [];
        if (!empty($old)) {
            $old_ids = array_column($old, 'id');
            $new_ids = array_column($messages, 'id');
            $deleted_ids = array_diff($old_ids, $new_ids);
            
            // Mark deleted messages by moving them to trash
            if (!empty($deleted_ids)) {
                // Fetch all posts with deleted message IDs in a single query
                $posts_to_trash = get_posts([
                    'post_type' => 'dfx_tg_message',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => '_tg_channel',
                            'value' => $channel,
                        ],
                        [
                            'key' => '_tg_message_id',
                            'value' => $deleted_ids,
                            'compare' => 'IN',
                        ],
                    ],
                ]);
                
                // Move all found posts to trash
                foreach ($posts_to_trash as $post_id) {
                    wp_trash_post($post_id);
                }
            }
        }
        
        return ['messages' => $messages, 'deleted_ids' => $deleted_ids];
    }
    
    /**
     * Get all messages from database (including hidden ones, for admin purposes)
     */
    private function get_all_messages($channel, $limit = 200) {
        $posts = get_posts([
            'post_type' => 'dfx_tg_message',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
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
            ];
        }
        
        return $messages;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on the messages list page
        global $typenow;
        
        if ($hook !== 'edit.php' || $typenow !== 'dfx_tg_message') {
            return;
        }
        
        wp_enqueue_script(
            'dfx-tg-admin',
            DFX_TG_FEED_URL . 'assets/js/admin.js',
            ['jquery'],
            DFX_TG_FEED_VER,
            true
        );
        
        wp_localize_script('dfx-tg-admin', 'dfxTgAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'hideConfirm' => __('Are you sure you want to hide this message from the frontend?', 'dfx-tg-feed'),
            'error' => __('An error occurred. Please try again.', 'dfx-tg-feed'),
        ]);
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
        if (empty($channel)) {
            wp_send_json_error('Channel parameter is required.');
        }
        
        $limit = intval(get_option('dfx_tg_feed_default_count', 10));
        $result = $this->refresh_messages($channel, $limit);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler to hide a message
     */
    public function ajax_hide_message() {
        // Verify nonce first
        $post_id = intval($_POST['post_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'dfx_tg_hide_message_' . $post_id)) {
            wp_send_json_error(__('Invalid security token.', 'dfx-tg-feed'));
        }
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid request.', 'dfx-tg-feed'));
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('No permission.', 'dfx-tg-feed'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'dfx_tg_message') {
            wp_send_json_error(__('Invalid post.', 'dfx-tg-feed'));
        }
        
        update_post_meta($post_id, '_tg_hidden', '1');
        wp_send_json_success(__('Message hidden from frontend.', 'dfx-tg-feed'));
    }
    
    /**
     * AJAX handler to unhide a message
     */
    public function ajax_unhide_message() {
        // Verify nonce first
        $post_id = intval($_POST['post_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'dfx_tg_hide_message_' . $post_id)) {
            wp_send_json_error(__('Invalid security token.', 'dfx-tg-feed'));
        }
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid request.', 'dfx-tg-feed'));
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('No permission.', 'dfx-tg-feed'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'dfx_tg_message') {
            wp_send_json_error(__('Invalid post.', 'dfx-tg-feed'));
        }
        
        delete_post_meta($post_id, '_tg_hidden');
        wp_send_json_success(__('Message is now visible in frontend.', 'dfx-tg-feed'));
    }
}
