<?php
namespace DFXTgFeed;

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
        add_filter('manage_dfxtgfeed_message_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_dfxtgfeed_message_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('manage_edit-dfxtgfeed_message_sortable_columns', [$this, 'set_sortable_columns']);
        add_action('restrict_manage_posts', [$this, 'add_channel_filter']);
        add_action('restrict_manage_posts', [$this, 'add_refresh_button']);
        add_action('restrict_manage_posts', [$this, 'add_sync_deletions_button']);
        add_filter('parse_query', [$this, 'filter_by_channel']);
        add_action('pre_get_posts', [$this, 'handle_custom_column_sorting']);
        add_filter('post_row_actions', [$this, 'modify_row_actions'], 10, 2);
        add_action('admin_menu', [$this, 'remove_standalone_menu'], 999);
        add_filter('parent_file', [$this, 'set_parent_file']);
        add_filter('submenu_file', [$this, 'set_submenu_file']);
        add_action('wp_ajax_dfxtgfeed_hide_message', [$this, 'ajax_hide_message']);
        add_action('wp_ajax_dfxtgfeed_unhide_message', [$this, 'ajax_unhide_message']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('bulk_actions-edit-dfxtgfeed_message', [$this, 'register_bulk_actions']);
        add_filter('handle_bulk_actions-edit-dfxtgfeed_message', [$this, 'handle_bulk_actions'], 10, 3);
        add_action('admin_notices', [$this, 'bulk_action_notices']);
        // Prevent WordPress from changing post dates when status changes
        add_filter('wp_insert_post_data', [$this, 'preserve_post_dates'], 10, 2);
    }
    
    public function register_post_type() {
        $labels = [
            'name'               => __('Telegram Messages', 'dfxtgfeed'),
            'singular_name'      => __('Telegram Message', 'dfxtgfeed'),
            'menu_name'          => __('Telegram Messages', 'dfxtgfeed'),
            'add_new'            => __('Add New', 'dfxtgfeed'),
            'add_new_item'       => __('Add New Message', 'dfxtgfeed'),
            'edit_item'          => __('Edit Message', 'dfxtgfeed'),
            'new_item'           => __('New Message', 'dfxtgfeed'),
            'view_item'          => __('View Message', 'dfxtgfeed'),
            'search_items'       => __('Search Messages', 'dfxtgfeed'),
            'not_found'          => __('No messages found', 'dfxtgfeed'),
            'not_found_in_trash' => __('No messages found in trash', 'dfxtgfeed'),
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
        
        register_post_type('dfxtgfeed_message', $args);
    }
    
    public function set_custom_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Message Preview', 'dfxtgfeed');
        $new_columns['channel'] = __('Channel', 'dfxtgfeed');
        $new_columns['author'] = __('Author', 'dfxtgfeed');
        $new_columns['media'] = __('Media', 'dfxtgfeed');
        $new_columns['message_id'] = __('Message ID', 'dfxtgfeed');
        $new_columns['visibility'] = __('Visibility', 'dfxtgfeed');
        $new_columns['date'] = __('Posted Date', 'dfxtgfeed');
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'channel':
                $channel = get_post_meta($post_id, '_dfxtgfeed_channel', true);
                echo '<strong>' . esc_html($channel) . '</strong>';
                break;
            case 'message_id':
                echo '<code>' . esc_html(get_post_meta($post_id, '_dfxtgfeed_message_id', true)) . '</code>';
                break;
            case 'media':
                $media = get_post_meta($post_id, '_dfxtgfeed_media', true);
                $is_sticker = get_post_meta($post_id, '_dfxtgfeed_is_sticker', true);
                if ($is_sticker) {
                    echo '<span class="dashicons dashicons-format-image" title="Sticker"></span>';
                } elseif ($media) {
                    echo '<span class="dashicons dashicons-format-gallery" title="Has media"></span>';
                } else {
                    echo '—';
                }
                break;
            case 'author':
                $author = get_post_meta($post_id, '_dfxtgfeed_author', true);
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
                $is_hidden = get_post_meta($post_id, '_dfxtgfeed_hidden', true);
                if ($is_hidden) {
                    echo '<span style="color: #d63638;"><span class="dashicons dashicons-hidden"></span> ' . __('Hidden', 'dfxtgfeed') . '</span>';
                } else {
                    echo '<span style="color: #00a32a;"><span class="dashicons dashicons-visibility"></span> ' . __('Visible', 'dfxtgfeed') . '</span>';
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
        
        if ($typenow !== 'dfxtgfeed_message') {
            return;
        }
        
        // Get all unique channels
        global $wpdb;
        $channels = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dfxtgfeed_channel' 
            ORDER BY meta_value ASC
        ");
        
        $current_channel = isset($_GET['channel_filter']) ? sanitize_text_field(wp_unslash($_GET['channel_filter'])) : '';

        echo '<select name="channel_filter">';
        echo '<option value="">' . __('All Channels', 'dfxtgfeed') . '</option>';
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
        
        if ($typenow !== 'dfxtgfeed_message') {
            return;
        }
        
        // Get current channel filter if set
        $current_channel = isset($_GET['channel_filter']) ? sanitize_text_field($_GET['channel_filter']) : '';
        
        // Display refresh button
        ?>
        <button type="button" class="button" id="dfxtgfeed-refresh-messages" <?php echo empty($current_channel) ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: 2px;"></span>
            <?php _e('Refresh Messages', 'dfxtgfeed'); ?>
        </button>
        <?php if (empty($current_channel)): ?>
            <p class="description" style="display:inline; margin-left: 5px;">
                <?php _e('Select a channel to enable refresh', 'dfxtgfeed'); ?>
            </p>
        <?php endif; ?>
        <span id="dfxtgfeed-refresh-status" style="margin-left: 10px;"></span>
        <?php
    }

    /**
     * Render the "Sync deletions" admin button next to the refresh button.
     * Opt-in helper that scrapes the public channel preview at t.me/s/<channel>
     * to identify locally-stored messages no longer present in the channel.
     * Public channels only — the button itself works for any channel filter,
     * but the AJAX handler returns an error for private channels.
     */
    public function add_sync_deletions_button() {
        global $typenow;
        if ($typenow !== 'dfxtgfeed_message') {
            return;
        }
        $current_channel = isset($_GET['channel_filter']) ? sanitize_text_field($_GET['channel_filter']) : '';
        ?>
        <button type="button" class="button" id="dfxtgfeed-sync-deletions" <?php echo empty($current_channel) ? 'disabled' : ''; ?> style="margin-left: 6px;">
            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 2px;"></span>
            <?php _e('Sync Deletions', 'dfxtgfeed'); ?>
        </button>
        <span id="dfxtgfeed-sync-deletions-status" style="margin-left: 10px;"></span>
        <?php
    }

    public function filter_by_channel($query) {
        global $pagenow, $typenow;
        
        if ($pagenow === 'edit.php' && $typenow === 'dfxtgfeed_message' && isset($_GET['channel_filter']) && $_GET['channel_filter'] !== '') {
            // Use meta_query instead of meta_key/meta_value to avoid conflicts with sorting
            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key' => '_dfxtgfeed_channel',
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
        if (!is_admin() || !$query->is_main_query() || $pagenow !== 'edit.php' || $typenow !== 'dfxtgfeed_message') {
            return;
        }
        
        // Get the orderby parameter
        $orderby = $query->get('orderby');
        
        // Handle sorting by custom meta fields
        if ($orderby === 'channel') {
            // Sort by channel meta field (alphabetically)
            $query->set('meta_key', '_dfxtgfeed_channel');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'message_id') {
            // Sort by message_id meta field (numerically)
            $query->set('meta_key', '_dfxtgfeed_message_id');
            $query->set('orderby', 'meta_value_num');
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on the post type list page
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
        if ($hook !== 'edit.php' || $post_type !== 'dfxtgfeed_message') {
            return;
        }
        
        // Enqueue jQuery as dependency
        wp_enqueue_script('jquery');
        
        // Localize script data for AJAX
        wp_localize_script('jquery', 'dfxtgfeedRefresh', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dfxtgfeed_refresh'),
            'syncNonce' => wp_create_nonce('dfxtgfeed_check_deletions'),
            'i18n' => [
                'selectChannel'      => __('Please select a channel first', 'dfxtgfeed'),
                'refreshing'         => __('Refreshing messages...', 'dfxtgfeed'),
                'success'            => __('Messages refreshed successfully! Reloading...', 'dfxtgfeed'),
                'errorLabel'         => __('Error:', 'dfxtgfeed'),
                'requestFailed'      => __('Request failed:', 'dfxtgfeed'),
                'unknownError'       => __('Unknown error', 'dfxtgfeed'),
                'checking'           => __('Checking public channel preview...', 'dfxtgfeed'),
                'noDeletions'        => __('No deletions detected. All locally stored messages are still visible in the public channel.', 'dfxtgfeed'),
                'confirmTrashTitle'  => __('Found {n} message(s) that no longer appear in the public channel:', 'dfxtgfeed'),
                'confirmTrashFooter' => __('Move all to Trash? (Trashed messages stay trashed across refreshes; you can restore them from the Trash filter.)', 'dfxtgfeed'),
                'trashing'           => __('Moving messages to Trash...', 'dfxtgfeed'),
                'trashed'            => __('{n} message(s) moved to Trash. Reloading...', 'dfxtgfeed'),
            ],
        ]);
        
        // Add inline CSS for status messages
        wp_add_inline_style('common', '
            .dfxtgfeed-status-loading { color: #0073aa; }
            .dfxtgfeed-status-success { color: #46b450; }
            .dfxtgfeed-status-error { color: #dc3232; }
        ');
        
        // Add inline script for refresh + sync-deletions functionality
        wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            var refreshBtn = $('#dfxtgfeed-refresh-messages');
            var refreshStatus = $('#dfxtgfeed-refresh-status');
            var syncBtn = $('#dfxtgfeed-sync-deletions');
            var syncStatus = $('#dfxtgfeed-sync-deletions-status');
            var channelFilter = $('select[name=\"channel_filter\"]');
            var i18n = dfxtgfeedRefresh.i18n;

            function setStatus(span, message, statusClass) {
                var el = $('<span>').addClass(statusClass).text(message);
                span.empty().append(el);
            }

            channelFilter.on('change', function() {
                var hasChannel = !!$(this).val();
                refreshBtn.prop('disabled', !hasChannel);
                syncBtn.prop('disabled', !hasChannel);
            });

            refreshBtn.on('click', function(e) {
                e.preventDefault();
                var channel = channelFilter.val();
                if (!channel) { alert(i18n.selectChannel); return; }
                refreshBtn.prop('disabled', true);
                setStatus(refreshStatus, i18n.refreshing, 'dfxtgfeed-status-loading');
                $.ajax({
                    url: dfxtgfeedRefresh.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dfxtgfeed_refresh',
                        channel: channel,
                        _ajax_nonce: dfxtgfeedRefresh.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            setStatus(refreshStatus, i18n.success, 'dfxtgfeed-status-success');
                            setTimeout(function() { window.location.reload(); }, 1000);
                        } else {
                            var errorMsg = response.data || i18n.unknownError;
                            setStatus(refreshStatus, i18n.errorLabel + ' ' + errorMsg, 'dfxtgfeed-status-error');
                            refreshBtn.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        setStatus(refreshStatus, i18n.requestFailed + ' ' + error, 'dfxtgfeed-status-error');
                        refreshBtn.prop('disabled', false);
                    }
                });
            });

            syncBtn.on('click', function(e) {
                e.preventDefault();
                var channel = channelFilter.val();
                if (!channel) { alert(i18n.selectChannel); return; }
                syncBtn.prop('disabled', true);
                setStatus(syncStatus, i18n.checking, 'dfxtgfeed-status-loading');
                $.ajax({
                    url: dfxtgfeedRefresh.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dfxtgfeed_check_deletions',
                        channel: channel,
                        _ajax_nonce: dfxtgfeedRefresh.syncNonce
                    },
                    success: function(response) {
                        if (!response.success) {
                            var errorMsg = response.data || i18n.unknownError;
                            setStatus(syncStatus, i18n.errorLabel + ' ' + errorMsg, 'dfxtgfeed-status-error');
                            syncBtn.prop('disabled', false);
                            return;
                        }
                        var candidates = (response.data && response.data.candidates) || [];
                        if (candidates.length === 0) {
                            setStatus(syncStatus, i18n.noDeletions, 'dfxtgfeed-status-success');
                            syncBtn.prop('disabled', false);
                            return;
                        }
                        var lines = candidates.map(function(c) {
                            var preview = (c.preview || '').substring(0, 60);
                            return '  • #' + c.message_id + ': ' + preview;
                        });
                        var msg = i18n.confirmTrashTitle.replace('{n}', candidates.length) + '\\n\\n' +
                                  lines.join('\\n') + '\\n\\n' + i18n.confirmTrashFooter;
                        if (!window.confirm(msg)) {
                            setStatus(syncStatus, '', '');
                            syncBtn.prop('disabled', false);
                            return;
                        }
                        var postIds = candidates.map(function(c) { return c.post_id; });
                        setStatus(syncStatus, i18n.trashing, 'dfxtgfeed-status-loading');
                        $.ajax({
                            url: dfxtgfeedRefresh.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'dfxtgfeed_trash_deletion_candidates',
                                'post_ids[]': postIds,
                                _ajax_nonce: dfxtgfeedRefresh.syncNonce
                            },
                            success: function(resp2) {
                                if (resp2.success) {
                                    var n = (resp2.data && resp2.data.count) || 0;
                                    setStatus(syncStatus, i18n.trashed.replace('{n}', n), 'dfxtgfeed-status-success');
                                    setTimeout(function() { window.location.reload(); }, 1200);
                                } else {
                                    var errorMsg2 = resp2.data || i18n.unknownError;
                                    setStatus(syncStatus, i18n.errorLabel + ' ' + errorMsg2, 'dfxtgfeed-status-error');
                                    syncBtn.prop('disabled', false);
                                }
                            },
                            error: function(xhr, status, error) {
                                setStatus(syncStatus, i18n.requestFailed + ' ' + error, 'dfxtgfeed-status-error');
                                syncBtn.prop('disabled', false);
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        setStatus(syncStatus, i18n.requestFailed + ' ' + error, 'dfxtgfeed-status-error');
                        syncBtn.prop('disabled', false);
                    }
                });
            });
        });
        ");

        
        wp_enqueue_script(
            'dfxtgfeed-admin',
            DFXTGFEED_URL . 'assets/js/admin.js',
            ['jquery'],
            DFXTGFEED_VER,
            true
        );
        
        wp_localize_script('dfxtgfeed-admin', 'dfxTgAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'hideConfirm' => __('Are you sure you want to hide this message from the frontend?', 'dfxtgfeed'),
            'error' => __('An error occurred. Please try again.', 'dfxtgfeed'),
        ]);
    }
    
    public function modify_row_actions($actions, $post) {
        if ($post->post_type === 'dfxtgfeed_message') {
            // Remove "Quick Edit" since posts are synced from Telegram
            unset($actions['inline hide-if-no-js']);
            
            // Add custom action to view in Telegram
            $message_id = get_post_meta($post->ID, '_dfxtgfeed_message_id', true);
            $channel = get_post_meta($post->ID, '_dfxtgfeed_channel', true);
            
            if ($message_id && $channel) {
                // Remove @ if present
                $channel_clean = ltrim($channel, '@');
                $actions['view_telegram'] = sprintf(
                    '<a href="https://t.me/%s/%s" target="_blank">%s</a>',
                    esc_attr($channel_clean),
                    esc_attr($message_id),
                    __('View in Telegram', 'dfxtgfeed')
                );
            }
            
            // Add hide/unhide action
            $is_hidden = get_post_meta($post->ID, '_dfxtgfeed_hidden', true);
            $nonce = wp_create_nonce('dfxtgfeed_hide_message_' . $post->ID);
            
            if ($is_hidden) {
                $actions['unhide'] = sprintf(
                    '<a href="#" class="dfxtgfeed-unhide-message" data-post-id="%d" data-nonce="%s">%s</a>',
                    $post->ID,
                    $nonce,
                    __('Unhide', 'dfxtgfeed')
                );
            } else {
                $actions['hide'] = sprintf(
                    '<a href="#" class="dfxtgfeed-hide-message" data-post-id="%d" data-nonce="%s">%s</a>',
                    $post->ID,
                    $nonce,
                    __('Hide', 'dfxtgfeed')
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
        remove_menu_page('edit.php?post_type=dfxtgfeed_message');
    }
    
    /**
     * Set the parent file for the post type to our custom menu
     */
    public function set_parent_file($parent_file) {
        global $current_screen;
        
        if ($current_screen && $current_screen->post_type === 'dfxtgfeed_message') {
            $parent_file = Plugin::MENU_SLUG;
        }
        
        return $parent_file;
    }
    
    /**
     * Set the submenu file for the post type
     */
    public function set_submenu_file($submenu_file) {
        global $current_screen;
        
        if ($current_screen && $current_screen->post_type === 'dfxtgfeed_message') {
            $submenu_file = 'edit.php?post_type=dfxtgfeed_message';
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
            'post_type'      => 'dfxtgfeed_message',
            'post_status'    => ['publish', 'trash'],
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => '_dfxtgfeed_channel',    'value' => $channel],
                ['key' => '_dfxtgfeed_message_id', 'value' => $message_data['id']],
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
            'post_type'     => 'dfxtgfeed_message',
            'post_title'    => $text_preview ?: __('(No text)', 'dfxtgfeed'),
            'post_content'  => $message_data['text'] ?? '',
            'post_status'   => 'publish',
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        ]);

        if ($post_id) {
            update_post_meta($post_id, '_dfxtgfeed_channel', $channel);
            update_post_meta($post_id, '_dfxtgfeed_message_id', $message_data['id']);
            update_post_meta($post_id, '_dfxtgfeed_date', $message_data['date']);
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
                'post_title'   => $text_preview ?: __('(No text)', 'dfxtgfeed'),
                'post_content' => $message_data['text'] ?? '',
            ]);
        }

        // _dfxtgfeed_media is a presence flag ('1'); the actual URL is
        // resolved at render time from _dfxtgfeed_file_id via the media proxy.
        if (!empty($message_data['media'])) {
            update_post_meta($post_id, '_dfxtgfeed_media', '1');
        } else {
            delete_post_meta($post_id, '_dfxtgfeed_media');
        }

        $this->set_or_clear_meta($post_id, '_dfxtgfeed_is_sticker', !empty($message_data['sticker']) ? true : null);
        $this->set_or_clear_meta($post_id, '_dfxtgfeed_sticker_type', $message_data['sticker_type'] ?? null);
        $this->set_or_clear_meta($post_id, '_dfxtgfeed_emoji', $message_data['emoji'] ?? null);
        $this->set_or_clear_meta($post_id, '_dfxtgfeed_file_id', $message_data['file_id'] ?? null);
        $this->set_or_clear_meta($post_id, '_dfxtgfeed_entities', !empty($message_data['entities']) ? $message_data['entities'] : null);
        $this->set_or_clear_meta($post_id, '_dfxtgfeed_author', !empty($message_data['author']) ? $message_data['author'] : null);
        $this->set_or_clear_meta($post_id, '_dfxtgfeed_edit_date', $message_data['edit_date'] ?? null);
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
            'post_type' => 'dfxtgfeed_message',
            'posts_per_page' => $limit,
            'meta_key' => '_dfxtgfeed_message_id',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_dfxtgfeed_channel',
                    'value' => $channel,
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_dfxtgfeed_hidden',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_dfxtgfeed_hidden',
                        'value' => '1',
                        'compare' => '!=',
                    ],
                ],
            ],
        ]);
        
        $messages = [];
        foreach ($posts as $post) {
            $edit_date = get_post_meta($post->ID, '_dfxtgfeed_edit_date', true);
            $messages[] = [
                'id' => get_post_meta($post->ID, '_dfxtgfeed_message_id', true),
                'date' => get_post_meta($post->ID, '_dfxtgfeed_date', true),
                'edit_date' => $edit_date ? (int) $edit_date : null,
                'text' => $post->post_content,
                'entities' => get_post_meta($post->ID, '_dfxtgfeed_entities', true) ?: [],
                'media' => get_post_meta($post->ID, '_dfxtgfeed_media', true),
                'sticker' => get_post_meta($post->ID, '_dfxtgfeed_is_sticker', true),
                'sticker_type' => get_post_meta($post->ID, '_dfxtgfeed_sticker_type', true),
                'emoji' => get_post_meta($post->ID, '_dfxtgfeed_emoji', true),
                'file_id' => get_post_meta($post->ID, '_dfxtgfeed_file_id', true),
                'author' => get_post_meta($post->ID, '_dfxtgfeed_author', true),
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
        check_ajax_referer('dfxtgfeed_refresh');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission.');
        }

        $channel = sanitize_text_field($_POST['channel'] ?? '');
        if (empty($channel)) {
            wp_send_json_error('Channel parameter is required.');
        }

        $limit = intval(get_option('dfxtgfeed_default_count', 10));
        $result = $this->refresh_messages($channel, $limit);

        wp_send_json_success($result);
    }

    /**
     * AJAX: scrape the public channel preview and return the list of locally
     * stored messages that no longer appear there. Does NOT trash them — the
     * UI shows the candidates first and asks for confirmation, after which
     * the second handler (ajax_trash_deletion_candidates) does the trashing.
     */
    public function ajax_check_deletions() {
        check_ajax_referer('dfxtgfeed_check_deletions');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No permission.', 'dfxtgfeed'));
        }
        $channel = sanitize_text_field($_POST['channel'] ?? '');
        if (empty($channel)) {
            wp_send_json_error(__('Channel parameter is required.', 'dfxtgfeed'));
        }

        $live_ids = API::instance()->fetch_public_preview_ids($channel, 200);
        if (is_wp_error($live_ids)) {
            wp_send_json_error($live_ids->get_error_message());
        }
        if (empty($live_ids)) {
            wp_send_json_error(__('Public preview returned no messages.', 'dfxtgfeed'));
        }

        // Look only at locally stored messages within the visible-preview range.
        // Messages older than min(live_ids) are beyond the preview window and
        // should NOT be considered candidates — we can't tell whether they
        // were deleted or simply pre-date the preview's depth.
        $min_live = (int) min($live_ids);
        $stored = get_posts([
            'post_type'      => 'dfxtgfeed_message',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => '_dfxtgfeed_channel', 'value' => $channel],
                [
                    'key'     => '_dfxtgfeed_message_id',
                    'value'   => $min_live,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ]);

        $live_set = array_flip($live_ids);
        $candidates = [];
        foreach ($stored as $post_id) {
            $message_id = (int) get_post_meta($post_id, '_dfxtgfeed_message_id', true);
            if (!isset($live_set[$message_id])) {
                $candidates[] = [
                    'post_id'    => (int) $post_id,
                    'message_id' => $message_id,
                    'preview'    => get_the_title($post_id),
                ];
            }
        }

        wp_send_json_success([
            'candidates' => $candidates,
            'count'      => count($candidates),
        ]);
    }

    /**
     * AJAX: move the user-confirmed list of "no-longer-in-channel" posts to
     * Trash. The sticky-trash logic in store_message prevents resurrection on
     * subsequent refreshes.
     */
    public function ajax_trash_deletion_candidates() {
        check_ajax_referer('dfxtgfeed_check_deletions');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No permission.', 'dfxtgfeed'));
        }
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array) $_POST['post_ids']) : [];
        if (empty($post_ids)) {
            wp_send_json_error(__('No posts specified.', 'dfxtgfeed'));
        }
        $count = 0;
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'dfxtgfeed_message' && current_user_can('delete_post', $post_id)) {
                if (wp_trash_post($post_id)) {
                    $count++;
                }
            }
        }
        wp_send_json_success(['count' => $count]);
    }
    
    /**
     * AJAX handler to hide a message
     */
    public function ajax_hide_message() {
        // Verify nonce first
        $post_id = intval($_POST['post_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'dfxtgfeed_hide_message_' . $post_id)) {
            wp_send_json_error(__('Invalid security token.', 'dfxtgfeed'));
        }
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid request.', 'dfxtgfeed'));
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('No permission.', 'dfxtgfeed'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'dfxtgfeed_message') {
            wp_send_json_error(__('Invalid post.', 'dfxtgfeed'));
        }
        
        update_post_meta($post_id, '_dfxtgfeed_hidden', '1');
        wp_send_json_success(__('Message hidden from frontend.', 'dfxtgfeed'));
    }
    
    /**
     * AJAX handler to unhide a message
     */
    public function ajax_unhide_message() {
        // Verify nonce first
        $post_id = intval($_POST['post_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        
        if (!wp_verify_nonce($nonce, 'dfxtgfeed_hide_message_' . $post_id)) {
            wp_send_json_error(__('Invalid security token.', 'dfxtgfeed'));
        }
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid request.', 'dfxtgfeed'));
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('No permission.', 'dfxtgfeed'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'dfxtgfeed_message') {
            wp_send_json_error(__('Invalid post.', 'dfxtgfeed'));
        }
        
        delete_post_meta($post_id, '_dfxtgfeed_hidden');
        wp_send_json_success(__('Message is now visible in frontend.', 'dfxtgfeed'));
    }
    
    /**
     * Register bulk actions for the post type
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['hide_messages'] = __('Hide', 'dfxtgfeed');
        $bulk_actions['unhide_messages'] = __('Unhide', 'dfxtgfeed');
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
                    if ($post && $post->post_type === 'dfxtgfeed_message') {
                        update_post_meta($post_id, '_dfxtgfeed_hidden', '1');
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
                    if ($post && $post->post_type === 'dfxtgfeed_message') {
                        delete_post_meta($post_id, '_dfxtgfeed_hidden');
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
        
        if ($typenow !== 'dfxtgfeed_message') {
            return;
        }
        
        if (!empty($_REQUEST['bulk_hidden_messages'])) {
            $count = absint($_REQUEST['bulk_hidden_messages']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n('%s message hidden from frontend.', '%s messages hidden from frontend.', $count, 'dfxtgfeed') .
                '</p></div>',
                number_format_i18n($count)
            );
        }
        
        if (!empty($_REQUEST['bulk_unhidden_messages'])) {
            $count = absint($_REQUEST['bulk_unhidden_messages']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n('%s message is now visible in frontend.', '%s messages are now visible in frontend.', $count, 'dfxtgfeed') .
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
     * the original Telegram message timestamp stored in the _dfxtgfeed_date meta field.
     * 
     * @param array $data    An array of slashed post data
     * @param array $postarr An array of sanitized post data
     * @return array Modified post data with preserved dates
     */
    public function preserve_post_dates($data, $postarr) {
        // Only apply to our custom post type
        if ($data['post_type'] !== 'dfxtgfeed_message') {
            return $data;
        }
        
        // If this is an update (not a new post), get the original Telegram timestamp
        if (!empty($postarr['ID'])) {
            $telegram_timestamp = get_post_meta($postarr['ID'], '_dfxtgfeed_date', true);
            
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
