<?php
namespace DFXFFTC;

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
        add_filter('manage_dfxfftc_message_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_dfxfftc_message_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('manage_edit-dfxfftc_message_sortable_columns', [$this, 'set_sortable_columns']);
        add_action('restrict_manage_posts', [$this, 'add_channel_filter']);
        add_action('restrict_manage_posts', [$this, 'add_refresh_button']);
        add_filter('parse_query', [$this, 'filter_by_channel']);
        add_action('pre_get_posts', [$this, 'handle_custom_column_sorting']);
        add_filter('post_row_actions', [$this, 'modify_row_actions'], 10, 2);
        add_action('admin_menu', [$this, 'remove_standalone_menu'], 999);
        add_filter('parent_file', [$this, 'set_parent_file']);
        add_filter('submenu_file', [$this, 'set_submenu_file']);
        add_action('wp_ajax_dfxfftc_hide_message', [$this, 'ajax_hide_message']);
        add_action('wp_ajax_dfxfftc_unhide_message', [$this, 'ajax_unhide_message']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('bulk_actions-edit-dfxfftc_message', [$this, 'register_bulk_actions']);
        add_filter('handle_bulk_actions-edit-dfxfftc_message', [$this, 'handle_bulk_actions'], 10, 3);
        add_action('admin_notices', [$this, 'bulk_action_notices']);
        // Prevent WordPress from changing post dates when status changes
        add_filter('wp_insert_post_data', [$this, 'preserve_post_dates'], 10, 2);
    }
    
    public function register_post_type() {
        $labels = [
            'name'               => __('Telegram Messages', 'dfx-feed-for-telegram-channels'),
            'singular_name'      => __('Telegram Message', 'dfx-feed-for-telegram-channels'),
            'menu_name'          => __('Telegram Messages', 'dfx-feed-for-telegram-channels'),
            'add_new'            => __('Add New', 'dfx-feed-for-telegram-channels'),
            'add_new_item'       => __('Add New Message', 'dfx-feed-for-telegram-channels'),
            'edit_item'          => __('Edit Message', 'dfx-feed-for-telegram-channels'),
            'new_item'           => __('New Message', 'dfx-feed-for-telegram-channels'),
            'view_item'          => __('View Message', 'dfx-feed-for-telegram-channels'),
            'search_items'       => __('Search Messages', 'dfx-feed-for-telegram-channels'),
            'not_found'          => __('No messages found', 'dfx-feed-for-telegram-channels'),
            'not_found_in_trash' => __('No messages found in trash', 'dfx-feed-for-telegram-channels'),
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 75,
            'capability_type'     => 'post',
            'capabilities'        => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'supports'            => ['title', 'editor'],
            'menu_icon'           => 'dashicons-email-alt',
        ];
        
        register_post_type('dfxfftc_message', $args);
    }
    
    public function set_custom_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Message Preview', 'dfx-feed-for-telegram-channels');
        $new_columns['channel'] = __('Channel', 'dfx-feed-for-telegram-channels');
        $new_columns['author'] = __('Author', 'dfx-feed-for-telegram-channels');
        $new_columns['media'] = __('Media', 'dfx-feed-for-telegram-channels');
        $new_columns['message_id'] = __('Message ID', 'dfx-feed-for-telegram-channels');
        $new_columns['visibility'] = __('Visibility', 'dfx-feed-for-telegram-channels');
        $new_columns['date'] = __('Posted Date', 'dfx-feed-for-telegram-channels');
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'channel':
                $channel = get_post_meta($post_id, '_dfxfftc_channel', true);
                echo '<strong>' . esc_html($channel) . '</strong>';
                break;
            case 'message_id':
                echo '<code>' . esc_html(get_post_meta($post_id, '_dfxfftc_message_id', true)) . '</code>';
                break;
            case 'media':
                $media = get_post_meta($post_id, '_dfxfftc_media', true);
                $is_sticker = get_post_meta($post_id, '_dfxfftc_is_sticker', true);
                if ($is_sticker) {
                    echo '<span class="dashicons dashicons-format-image" title="Sticker"></span>';
                } elseif ($media) {
                    echo '<span class="dashicons dashicons-format-gallery" title="Has media"></span>';
                } else {
                    echo '—';
                }
                break;
            case 'author':
                $author = get_post_meta($post_id, '_dfxfftc_author', true);
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
                $is_hidden = get_post_meta($post_id, '_dfxfftc_hidden', true);
                if ($is_hidden) {
                    echo '<span style="color: #d63638;"><span class="dashicons dashicons-hidden"></span> ' . esc_html__('Hidden', 'dfx-feed-for-telegram-channels') . '</span>';
                } else {
                    echo '<span style="color: #00a32a;"><span class="dashicons dashicons-visibility"></span> ' . esc_html__('Visible', 'dfx-feed-for-telegram-channels') . '</span>';
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
        
        if ($typenow !== 'dfxfftc_message') {
            return;
        }
        
        // Get all unique channels
        global $wpdb;
        $channels = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dfxfftc_channel' 
            ORDER BY meta_value ASC
        ");
        
        $current_channel = isset($_GET['channel_filter']) ? sanitize_text_field(wp_unslash($_GET['channel_filter'])) : '';

        echo '<select name="channel_filter">';
        echo '<option value="">' . esc_html__('All Channels', 'dfx-feed-for-telegram-channels') . '</option>';
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
        
        if ($typenow !== 'dfxfftc_message') {
            return;
        }
        
        // Get current channel filter if set
        $current_channel = isset($_GET['channel_filter']) ? sanitize_text_field($_GET['channel_filter']) : '';
        
        // Display refresh button
        ?>
        <button type="button" class="button" id="dfxfftc-refresh-messages" <?php echo empty($current_channel) ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: 2px;"></span>
            <?php esc_html_e('Refresh Messages', 'dfx-feed-for-telegram-channels'); ?>
        </button>
        <?php if (empty($current_channel)): ?>
            <p class="description" style="display:inline; margin-left: 5px;">
                <?php esc_html_e('Select a channel to enable refresh', 'dfx-feed-for-telegram-channels'); ?>
            </p>
        <?php endif; ?>
        <span id="dfxfftc-refresh-status" style="margin-left: 10px;"></span>
        <?php
    }

    public function filter_by_channel($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'dfxfftc_message' && isset($_GET['channel_filter']) && $_GET['channel_filter'] !== '') {
            // Use meta_query instead of meta_key/meta_value to avoid conflicts with sorting
            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key' => '_dfxfftc_channel',
                'value' => sanitize_text_field($_GET['channel_filter']),
                'compare' => '='
            ];
            $query->set('meta_query', $meta_query);
        }
    }
    
    /**
     * Handle custom column sorting for the admin list table
     */
    public function handle_custom_column_sorting($query) {
        // Only run on admin edit.php page for our post type
        global $pagenow, $typenow;
        if (!is_admin() || !$query->is_main_query() || $pagenow !== 'edit.php' || $typenow !== 'dfxfftc_message') {
            return;
        }
        
        // Get the orderby parameter
        $orderby = $query->get('orderby');
        
        // Handle sorting by custom meta fields
        if ($orderby === 'channel') {
            // Sort by channel meta field (alphabetically)
            $query->set('meta_key', '_dfxfftc_channel');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'message_id') {
            // Sort by message_id meta field (numerically)
            $query->set('meta_key', '_dfxfftc_message_id');
            $query->set('orderby', 'meta_value_num');
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on the post type list page
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
        if ($hook !== 'edit.php' || $post_type !== 'dfxfftc_message') {
            return;
        }
        
        // Enqueue jQuery as dependency
        wp_enqueue_script('jquery');
        
        // Localize script data for AJAX
        wp_localize_script('jquery', 'dfxfftcRefresh', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dfxfftc_refresh'),
            'i18n' => [
                'selectChannel'      => __('Please select a channel first', 'dfx-feed-for-telegram-channels'),
                'refreshing'         => __('Refreshing messages...', 'dfx-feed-for-telegram-channels'),
                'success'            => __('Messages refreshed successfully! Reloading...', 'dfx-feed-for-telegram-channels'),
                'errorLabel'         => __('Error:', 'dfx-feed-for-telegram-channels'),
                'requestFailed'      => __('Request failed:', 'dfx-feed-for-telegram-channels'),
                'unknownError'       => __('Unknown error', 'dfx-feed-for-telegram-channels'),
            ],
        ]);
        
        // Add inline CSS for status messages
        wp_add_inline_style('common', '
            .dfxfftc-status-loading { color: #0073aa; }
            .dfxfftc-status-success { color: #46b450; }
            .dfxfftc-status-error { color: #dc3232; }
        ');
        
        // Add inline script for refresh functionality
        wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            var refreshBtn = $('#dfxfftc-refresh-messages');
            var refreshStatus = $('#dfxfftc-refresh-status');
            var channelFilter = $('select[name=\"channel_filter\"]');
            var i18n = dfxfftcRefresh.i18n;

            function setStatus(span, message, statusClass) {
                var el = $('<span>').addClass(statusClass).text(message);
                span.empty().append(el);
            }

            channelFilter.on('change', function() {
                refreshBtn.prop('disabled', !$(this).val());
            });

            refreshBtn.on('click', function(e) {
                e.preventDefault();
                var channel = channelFilter.val();
                if (!channel) { alert(i18n.selectChannel); return; }
                refreshBtn.prop('disabled', true);
                setStatus(refreshStatus, i18n.refreshing, 'dfxfftc-status-loading');
                $.ajax({
                    url: dfxfftcRefresh.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dfxfftc_refresh',
                        channel: channel,
                        _ajax_nonce: dfxfftcRefresh.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            setStatus(refreshStatus, i18n.success, 'dfxfftc-status-success');
                            setTimeout(function() { window.location.reload(); }, 1000);
                        } else {
                            var errorMsg = response.data || i18n.unknownError;
                            setStatus(refreshStatus, i18n.errorLabel + ' ' + errorMsg, 'dfxfftc-status-error');
                            refreshBtn.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        setStatus(refreshStatus, i18n.requestFailed + ' ' + error, 'dfxfftc-status-error');
                        refreshBtn.prop('disabled', false);
                    }
                });
            });
        });
        ");

        
        wp_enqueue_script(
            'dfxfftc-admin',
            DFXFFTC_URL . 'assets/js/admin.js',
            ['jquery'],
            DFXFFTC_VER,
            true
        );
        
        wp_localize_script('dfxfftc-admin', 'dfxTgAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'hideConfirm' => __('Are you sure you want to hide this message from the frontend?', 'dfx-feed-for-telegram-channels'),
            'error' => __('An error occurred. Please try again.', 'dfx-feed-for-telegram-channels'),
        ]);
    }
    
    public function modify_row_actions($actions, $post) {
        if ($post->post_type === 'dfxfftc_message') {
            // Remove "Quick Edit" since posts are synced from Telegram
            unset($actions['inline hide-if-no-js']);
            
            // Add custom action to view in Telegram
            $message_id = get_post_meta($post->ID, '_dfxfftc_message_id', true);
            $channel = get_post_meta($post->ID, '_dfxfftc_channel', true);
            
            if ($message_id && $channel) {
                // Remove @ if present
                $channel_clean = ltrim($channel, '@');
                $actions['view_telegram'] = sprintf(
                    '<a href="https://t.me/%s/%s" target="_blank">%s</a>',
                    esc_attr($channel_clean),
                    esc_attr($message_id),
                    __('View in Telegram', 'dfx-feed-for-telegram-channels')
                );
            }
            
            // Add hide/unhide action
            $is_hidden = get_post_meta($post->ID, '_dfxfftc_hidden', true);
            $nonce = wp_create_nonce('dfxfftc_hide_message_' . $post->ID);
            
            if ($is_hidden) {
                $actions['unhide'] = sprintf(
                    '<a href="#" class="dfxfftc-unhide-message" data-post-id="%d" data-nonce="%s">%s</a>',
                    $post->ID,
                    $nonce,
                    __('Unhide', 'dfx-feed-for-telegram-channels')
                );
            } else {
                $actions['hide'] = sprintf(
                    '<a href="#" class="dfxfftc-hide-message" data-post-id="%d" data-nonce="%s">%s</a>',
                    $post->ID,
                    $nonce,
                    __('Hide', 'dfx-feed-for-telegram-channels')
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
        remove_menu_page('edit.php?post_type=dfxfftc_message');
    }
    
    /**
     * Set the parent file for the post type to our custom menu
     */
    public function set_parent_file($parent_file) {
        global $current_screen;
        
        if ($current_screen && $current_screen->post_type === 'dfxfftc_message') {
            $parent_file = Plugin::MENU_SLUG;
        }
        
        return $parent_file;
    }
    
    /**
     * Set the submenu file for the post type
     */
    public function set_submenu_file($submenu_file) {
        global $current_screen;
        
        if ($current_screen && $current_screen->post_type === 'dfxfftc_message') {
            $submenu_file = 'edit.php?post_type=dfxfftc_message';
        }
        
        return $submenu_file;
    }
    
    /**
     * Store or update a message.
     *
     * Three scenarios:
     *   1. New message (no existing post)        — insert.
     *   2. Existing publish post + edit update   — update content and meta in place.
     *   3. Existing trashed post                 — skip (admin manually removed it).
     *
     * Trashed posts are intentionally left alone: that's how the manual
     * "Move to Trash" admin action works as a sticky local-delete.
     */
    public function store_message($channel, $message_data) {
        $existing = get_posts([
            'post_type'      => 'dfxfftc_message',
            'post_status'    => ['publish', 'trash'],
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => '_dfxfftc_channel',    'value' => $channel],
                ['key' => '_dfxfftc_message_id', 'value' => $message_data['id']],
            ],
            'posts_per_page' => 1,
        ]);

        $is_edit = !empty($message_data['edit_date']);

        if (!empty($existing)) {
            $existing_post = $existing[0];

            // Sticky trash: refresh never resurrects a manually-trashed message.
            if ($existing_post->post_status === 'trash') {
                return $existing_post->ID;
            }

            // Non-edit refresh hit on an already-stored message: nothing to do.
            if (!$is_edit) {
                return $existing_post->ID;
            }

            $this->apply_message_payload($existing_post->ID, $message_data, false);
            return $existing_post->ID;
        }

        // New message: insert and populate.
        $text_preview = mb_substr($message_data['text'] ?? '', 0, 100);
        if (strlen($message_data['text'] ?? '') > 100) {
            $text_preview .= '...';
        }
        $post_date_gmt = gmdate('Y-m-d H:i:s', $message_data['date']);
        $post_date = get_date_from_gmt($post_date_gmt);

        $post_id = wp_insert_post([
            'post_type'     => 'dfxfftc_message',
            'post_title'    => $text_preview ?: __('(No text)', 'dfx-feed-for-telegram-channels'),
            'post_content'  => $message_data['text'] ?? '',
            'post_status'   => 'publish',
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        ]);

        if ($post_id) {
            update_post_meta($post_id, '_dfxfftc_channel', $channel);
            update_post_meta($post_id, '_dfxfftc_message_id', $message_data['id']);
            update_post_meta($post_id, '_dfxfftc_date', $message_data['date']);
            $this->apply_message_payload($post_id, $message_data, true);
        }

        return $post_id;
    }

    /**
     * Write the variable parts of a message payload (text, entities, media,
     * author, sticker info, edit timestamp) onto an existing post. Used for
     * both new inserts and incoming edits.
     *
     * For edits, also updates post_title / post_content. The post_date is
     * preserved by the original Telegram timestamp via preserve_post_dates().
     */
    private function apply_message_payload($post_id, $message_data, $is_new) {
        if (!$is_new) {
            $text_preview = mb_substr($message_data['text'] ?? '', 0, 100);
            if (strlen($message_data['text'] ?? '') > 100) {
                $text_preview .= '...';
            }
            wp_update_post([
                'ID'           => $post_id,
                'post_title'   => $text_preview ?: __('(No text)', 'dfx-feed-for-telegram-channels'),
                'post_content' => $message_data['text'] ?? '',
            ]);
        }

        // _dfxfftc_media is a presence flag ('1'); the actual URL is
        // resolved at render time from _dfxfftc_file_id via the media proxy.
        if (!empty($message_data['media'])) {
            update_post_meta($post_id, '_dfxfftc_media', '1');
        } else {
            delete_post_meta($post_id, '_dfxfftc_media');
        }

        $this->set_or_clear_meta($post_id, '_dfxfftc_is_sticker', !empty($message_data['sticker']) ? true : null);
        $this->set_or_clear_meta($post_id, '_dfxfftc_sticker_type', $message_data['sticker_type'] ?? null);
        $this->set_or_clear_meta($post_id, '_dfxfftc_emoji', $message_data['emoji'] ?? null);
        $this->set_or_clear_meta($post_id, '_dfxfftc_file_id', $message_data['file_id'] ?? null);
        $this->set_or_clear_meta($post_id, '_dfxfftc_entities', !empty($message_data['entities']) ? $message_data['entities'] : null);
        $this->set_or_clear_meta($post_id, '_dfxfftc_author', !empty($message_data['author']) ? $message_data['author'] : null);
        $this->set_or_clear_meta($post_id, '_dfxfftc_edit_date', $message_data['edit_date'] ?? null);
    }

    private function set_or_clear_meta($post_id, $key, $value) {
        if ($value === null || $value === '' || $value === false) {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }
    
    /**
     * Get messages from database
     */
    public function get_messages($channel, $limit = 10) {
        $posts = get_posts([
            'post_type' => 'dfxfftc_message',
            'posts_per_page' => $limit,
            'meta_key' => '_dfxfftc_message_id',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_dfxfftc_channel',
                    'value' => $channel,
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_dfxfftc_hidden',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_dfxfftc_hidden',
                        'value' => '1',
                        'compare' => '!=',
                    ],
                ],
            ],
        ]);
        
        $messages = [];
        foreach ($posts as $post) {
            $edit_date = get_post_meta($post->ID, '_dfxfftc_edit_date', true);
            $messages[] = [
                'id' => get_post_meta($post->ID, '_dfxfftc_message_id', true),
                'date' => get_post_meta($post->ID, '_dfxfftc_date', true),
                'edit_date' => $edit_date ? (int) $edit_date : null,
                'text' => $post->post_content,
                'entities' => get_post_meta($post->ID, '_dfxfftc_entities', true) ?: [],
                'media' => get_post_meta($post->ID, '_dfxfftc_media', true),
                'sticker' => get_post_meta($post->ID, '_dfxfftc_is_sticker', true),
                'sticker_type' => get_post_meta($post->ID, '_dfxfftc_sticker_type', true),
                'emoji' => get_post_meta($post->ID, '_dfxfftc_emoji', true),
                'file_id' => get_post_meta($post->ID, '_dfxfftc_file_id', true),
                'author' => get_post_meta($post->ID, '_dfxfftc_author', true),
                'deleted' => false,
            ];
        }

        return $messages;
    }
    
    /**
     * Refresh messages from the Telegram Bot API.
     *
     * Note: Bot API does NOT notify about channel message deletions. There is
     * no `deleted_channel_post` update type, so we cannot reliably know when a
     * message has been removed from the channel. Admins can manually remove a
     * message from the local feed via the per-row "Move to Trash" action; the
     * sticky-trash logic in store_message keeps it from reappearing on refresh.
     *
     * Edits ARE detected: edited_channel_post / edited_message updates are
     * fetched and store_message updates the existing post in place.
     */
    public function refresh_messages($channel, $limit) {
        $messages = API::instance()->fetch_channel_messages($channel, $limit);
        return ['messages' => $messages];
    }
    
    /**
     * AJAX handler to refresh messages for admin panel
     */
    public function ajax_refresh_messages() {
        check_ajax_referer('dfxfftc_refresh');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission.');
        }

        $channel = sanitize_text_field($_POST['channel'] ?? '');
        if (empty($channel)) {
            wp_send_json_error('Channel parameter is required.');
        }

        $limit = intval(get_option('dfxfftc_default_count', 10));
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
        
        if (!wp_verify_nonce($nonce, 'dfxfftc_hide_message_' . $post_id)) {
            wp_send_json_error(__('Invalid security token.', 'dfx-feed-for-telegram-channels'));
        }
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid request.', 'dfx-feed-for-telegram-channels'));
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('No permission.', 'dfx-feed-for-telegram-channels'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'dfxfftc_message') {
            wp_send_json_error(__('Invalid post.', 'dfx-feed-for-telegram-channels'));
        }
        
        update_post_meta($post_id, '_dfxfftc_hidden', '1');
        wp_send_json_success(__('Message hidden from frontend.', 'dfx-feed-for-telegram-channels'));
    }
    
    /**
     * AJAX handler to unhide a message
     */
    public function ajax_unhide_message() {
        // Verify nonce first
        $post_id = intval($_POST['post_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'dfxfftc_hide_message_' . $post_id)) {
            wp_send_json_error(__('Invalid security token.', 'dfx-feed-for-telegram-channels'));
        }
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid request.', 'dfx-feed-for-telegram-channels'));
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('No permission.', 'dfx-feed-for-telegram-channels'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'dfxfftc_message') {
            wp_send_json_error(__('Invalid post.', 'dfx-feed-for-telegram-channels'));
        }
        
        delete_post_meta($post_id, '_dfxfftc_hidden');
        wp_send_json_success(__('Message is now visible in frontend.', 'dfx-feed-for-telegram-channels'));
    }
    
    /**
     * Register bulk actions for the post type
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['hide_messages'] = __('Hide', 'dfx-feed-for-telegram-channels');
        $bulk_actions['unhide_messages'] = __('Unhide', 'dfx-feed-for-telegram-channels');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action === 'hide_messages') {
            $count = 0;
            foreach ($post_ids as $post_id) {
                if (current_user_can('edit_post', $post_id)) {
                    $post = get_post($post_id);
                    if ($post && $post->post_type === 'dfxfftc_message') {
                        update_post_meta($post_id, '_dfxfftc_hidden', '1');
                        $count++;
                    }
                }
            }
            $redirect_to = add_query_arg('bulk_hidden_messages', $count, $redirect_to);
        } elseif ($action === 'unhide_messages') {
            $count = 0;
            foreach ($post_ids as $post_id) {
                if (current_user_can('edit_post', $post_id)) {
                    $post = get_post($post_id);
                    if ($post && $post->post_type === 'dfxfftc_message') {
                        delete_post_meta($post_id, '_dfxfftc_hidden');
                        $count++;
                    }
                }
            }
            $redirect_to = add_query_arg('bulk_unhidden_messages', $count, $redirect_to);
        }
        
        return $redirect_to;
    }
    
    /**
     * Display admin notices for bulk actions
     */
    public function bulk_action_notices() {
        global $typenow;
        
        if ($typenow !== 'dfxfftc_message') {
            return;
        }
        
        if (!empty($_REQUEST['bulk_hidden_messages'])) {
            $count = absint($_REQUEST['bulk_hidden_messages']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n('%s message hidden from frontend.', '%s messages hidden from frontend.', $count, 'dfx-feed-for-telegram-channels') .
                '</p></div>',
                number_format_i18n($count)
            );
        }
        
        if (!empty($_REQUEST['bulk_unhidden_messages'])) {
            $count = absint($_REQUEST['bulk_unhidden_messages']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n('%s message is now visible in frontend.', '%s messages are now visible in frontend.', $count, 'dfx-feed-for-telegram-channels') .
                '</p></div>',
                number_format_i18n($count)
            );
        }
    }
    
    /**
     * Preserve post dates when status changes
     * 
     * WordPress has a default behavior where it updates post_date when a post
     * transitions from draft/pending to published. For Telegram messages, we must
     * preserve the original date from Telegram, so we prevent this behavior.
     * 
     * This filter ensures that post_date and post_date_gmt are ALWAYS set from
     * the original Telegram message timestamp stored in the _dfxfftc_date meta field.
     * 
     * @param array $data    An array of slashed post data
     * @param array $postarr An array of sanitized post data
     * @return array Modified post data with preserved dates
     */
    public function preserve_post_dates($data, $postarr) {
        // Only apply to our custom post type
        if ($data['post_type'] !== 'dfxfftc_message') {
            return $data;
        }
        
        // If this is an update (not a new post), get the original Telegram timestamp
        if (!empty($postarr['ID'])) {
            $telegram_timestamp = get_post_meta($postarr['ID'], '_dfxfftc_date', true);
            
            if ($telegram_timestamp) {
                // Convert Telegram timestamp to WordPress date format
                $post_date_gmt = gmdate('Y-m-d H:i:s', $telegram_timestamp);
                $post_date = get_date_from_gmt($post_date_gmt);
                
                // Force the dates to be from the original Telegram message
                $data['post_date'] = $post_date;
                $data['post_date_gmt'] = $post_date_gmt;
            }
        }
        
        return $data;
    }
}
