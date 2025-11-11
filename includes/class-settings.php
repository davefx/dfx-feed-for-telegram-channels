<?php
namespace DFX\TelegramChannelFeed;
if (!defined('ABSPATH')) exit;

class Settings {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function register() {
        register_setting('dfx_tg_feed', 'dfx_tg_feed_bot_token');
        register_setting('dfx_tg_feed', 'dfx_tg_feed_default_count');
        register_setting('dfx_tg_feed', 'dfx_tg_feed_channel');
    }

    public function render_page() {
        $bot_token = esc_attr(get_option('dfx_tg_feed_bot_token', ''));
        $channel = esc_attr(get_option('dfx_tg_feed_channel', ''));
        ?>
        <div class="wrap">
            <h1><?php _e('DFX Telegram Channel Feed', 'dfx-tg-feed'); ?></h1>

            <h2>How to setup your Telegram Bot</h2>
            <ol>
                <li>Open Telegram and chat with <a href="https://t.me/botfather" target="_blank">@BotFather</a></li>
                <li>Send <code>/newbot</code>, follow instructions, and copy your bot token.</li>
                <li>Paste the bot token below and click "Save Settings".</li>
                <li>Add your bot as an <strong>admin</strong> to each Telegram channel you want to display (channel &rarr; Administrators &rarr; Add Admin &rarr; [YOUR BOT USERNAME]).</li>
                <li>Use any channel with your bot by specifying the channel username in the shortcode or block (e.g., <code>[dfx_tg_channel_feed channel="@yourchannel"]</code>).</li>
            </ol>

            <form method="post" action="options.php">
                <?php settings_fields('dfx_tg_feed'); do_settings_sections('dfx_tg_feed'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Telegram Bot Token', 'dfx-tg-feed'); ?></th>
                        <td><input type="text" name="dfx_tg_feed_bot_token" value="<?php echo $bot_token; ?>" size="50" autocomplete="off"/></td>
                    </tr>
                    <tr>
                        <th><?php _e('Test Channel Username (optional)', 'dfx-tg-feed'); ?></th>
                        <td>
                            <input type="text" name="dfx_tg_feed_channel" value="<?php echo $channel; ?>" size="32" autocomplete="off" placeholder="@yourchannel"/>
                            <p class="description"><?php _e('This field is only used for connection testing below. You can specify any channel directly in your shortcodes or blocks.', 'dfx-tg-feed'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Default Message Count', 'dfx-tg-feed'); ?></th>
                        <td><input type="number" name="dfx_tg_feed_default_count" value="<?php echo esc_attr(get_option('dfx_tg_feed_default_count', 10)); ?>" min="1" max="100"/></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'dfx-tg-feed')); ?>
            </form>

            <hr />
            <h3><?php _e('Test your configuration', 'dfx-tg-feed'); ?></h3>
            <button class="button" id="dfx-tg-feed-test-btn"><?php _e('Test Connection', 'dfx-tg-feed'); ?></button>
            <div id="dfx-tg-feed-test-result"></div>

            <hr />
            <h3><?php _e('Reset Update Offset', 'dfx-tg-feed'); ?></h3>
            <p><?php _e('Reset the update offset to allow re-fetching of all available updates from Telegram. Use this if messages are not appearing after changing channel settings or if you suspect the offset is incorrect.', 'dfx-tg-feed'); ?></p>
            <button class="button" id="dfx-tg-feed-reset-offset-btn"><?php _e('Reset Offset', 'dfx-tg-feed'); ?></button>
            <div id="dfx-tg-feed-reset-offset-result"></div>

            <hr />
            <h3><?php _e('Reload Messages from Telegram', 'dfx-tg-feed'); ?></h3>
            <p><?php _e('Fetch all available messages from the channel and save them to the database. This will sync new messages and update existing ones.', 'dfx-tg-feed'); ?></p>
            <form id="dfx-tg-feed-reload-form" method="post">
                <input type="text" name="channel" value="<?php echo $channel;?>" placeholder="@channelusername" style="width: 300px;" />
                <button class="button button-primary" id="dfx-tg-feed-reload-btn"><?php _e('Reload All Messages', 'dfx-tg-feed'); ?></button>
                <?php wp_nonce_field('dfx_tg_feed_reload', 'dfx_tg_feed_reload_nonce'); ?>
            </form>
            <div id="dfx-tg-feed-reload-result"></div>
            
            <hr />
            <h3><?php _e('Fetch Historical Messages (Experimental)', 'dfx-tg-feed'); ?></h3>
            <p><?php _e('Attempt to retrieve historical messages using alternative API methods. Note: The Telegram Bot API has limitations and may not provide access to messages posted before the bot was added.', 'dfx-tg-feed'); ?></p>
            <form id="dfx-tg-feed-fetch-by-id-form" method="post">
                <input type="text" name="channel" value="<?php echo $channel;?>" placeholder="@channelusername" style="width: 300px;" />
                <input type="number" name="start_id" value="1" placeholder="Start Message ID" min="1" style="width: 150px;" />
                <input type="number" name="count" value="50" placeholder="Count" min="1" max="100" style="width: 100px;" />
                <button class="button" id="dfx-tg-feed-fetch-by-id-btn"><?php _e('Fetch by ID Range', 'dfx-tg-feed'); ?></button>
            </form>
            <div id="dfx-tg-feed-fetch-by-id-result"></div>

            <script>
            document.getElementById('dfx-tg-feed-test-btn').addEventListener('click', function(e){
                e.preventDefault();
                document.getElementById('dfx-tg-feed-test-result').textContent = 'Testing...';
                fetch(ajaxurl + '?action=dfx_tg_feed_test')
                  .then(r=>r.json())
                  .then(resp=>{
                    document.getElementById('dfx-tg-feed-test-result').innerHTML = resp.success ? '<span style="color:green">'+resp.data+'</span>' : '<span style="color:red">'+resp.data+'</span>';
                  });
            });

            document.getElementById('dfx-tg-feed-reload-form').addEventListener('submit', function(e){
                e.preventDefault();
                let resultDiv = document.getElementById('dfx-tg-feed-reload-result');
                let btn = document.getElementById('dfx-tg-feed-reload-btn');
                btn.disabled = true;
                resultDiv.innerHTML = '<span style="color:blue;">Reloading messages... This may take a moment.</span>';
                
                let data = new FormData(this);
                data.append('action', 'dfx_tg_feed_reload');
                
                fetch(ajaxurl, { method: "POST", body: data })
                .then(r=>r.json())
                .then(resp=>{
                    btn.disabled = false;
                    resultDiv.innerHTML = resp.success ? '<span style="color:green;">'+resp.data+'</span>' : '<span style="color:red;">Failed: '+resp.data+'</span>';
                })
                .catch(err => {
                    btn.disabled = false;
                    resultDiv.innerHTML = '<span style="color:red;">Error: '+err.message+'</span>';
                });
            });

            document.getElementById('dfx-tg-feed-reset-offset-btn').addEventListener('click', function(e){
                e.preventDefault();
                if (!confirm('Are you sure you want to reset the update offset? This will cause the plugin to re-fetch all available updates from Telegram.')) {
                    return;
                }
                let resultDiv = document.getElementById('dfx-tg-feed-reset-offset-result');
                let btn = this;
                btn.disabled = true;
                resultDiv.innerHTML = '<span style="color:blue;">Resetting offset...</span>';
                
                let data = new FormData();
                data.append('action', 'dfx_tg_feed_reset_offset');
                data.append('nonce', '<?php echo wp_create_nonce('dfx_tg_feed_reset_offset'); ?>');
                
                fetch(ajaxurl, { method: "POST", body: data })
                .then(r=>r.json())
                .then(resp=>{
                    btn.disabled = false;
                    resultDiv.innerHTML = resp.success ? '<span style="color:green;">'+resp.data+'</span>' : '<span style="color:red;">Failed: '+resp.data+'</span>';
                })
                .catch(err => {
                    btn.disabled = false;
                    resultDiv.innerHTML = '<span style="color:red;">Error: '+err.message+'</span>';
                });
            });

            document.getElementById('dfx-tg-feed-fetch-by-id-form').addEventListener('submit', function(e){
                e.preventDefault();
                let resultDiv = document.getElementById('dfx-tg-feed-fetch-by-id-result');
                let btn = document.getElementById('dfx-tg-feed-fetch-by-id-btn');
                btn.disabled = true;
                resultDiv.innerHTML = '<span style="color:blue;">Attempting to fetch messages... This may take a moment.</span>';
                
                let data = new FormData(this);
                data.append('action', 'dfx_tg_feed_fetch_by_id');
                data.append('nonce', '<?php echo wp_create_nonce('dfx_tg_feed_fetch_by_id'); ?>');
                
                fetch(ajaxurl, { method: "POST", body: data })
                .then(r=>r.json())
                .then(resp=>{
                    btn.disabled = false;
                    resultDiv.innerHTML = resp.success ? '<span style="color:green;">'+resp.data+'</span>' : '<span style="color:orange;">'+resp.data+'</span>';
                })
                .catch(err => {
                    btn.disabled = false;
                    resultDiv.innerHTML = '<span style="color:red;">Error: '+err.message+'</span>';
                });
            });
            </script>
            <hr>
            <h4>Troubleshooting</h4>
            <ul>
                <li>If test fails, check your bot token and ensure the bot is an admin/member in your channel.</li>
                <li>The Telegram Bot API will only receive messages that happen after your bot joins the channel (no access to full history before that point).</li>
            </ul>
        </div>
        <?php
    }

    public function ajax_test_bot_channel() {
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        $channel = get_option('dfx_tg_feed_channel', '');
        if (!$bot_token || !$channel) {
            wp_send_json_error('Please set both Bot Token and Channel.');
        }
        
        $results = [];
        
        // Try to fetch channel info
        $url = "https://api.telegram.org/bot" . urlencode($bot_token) . "/getChat?chat_id=" . urlencode($channel);
        $resp = wp_remote_get($url);
        if (is_wp_error($resp)) {
            wp_send_json_error('Network error: ' . $resp->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (!isset($body['ok']) || !$body['ok']) {
            wp_send_json_error('Telegram returned error: ' . (isset($body['description']) ? $body['description'] : 'Unknown error.'));
        }
        
        $channel_title = $body['result']['title'] ?? $channel;
        $channel_type = $body['result']['type'] ?? 'unknown';
        $results[] = '<strong>✓ Channel Found:</strong> ' . esc_html($channel_title) . ' (Type: ' . esc_html($channel_type) . ')';
        
        // Check if bot is an administrator
        $bot_info_url = "https://api.telegram.org/bot" . urlencode($bot_token) . "/getMe";
        $bot_resp = wp_remote_get($bot_info_url);
        if (!is_wp_error($bot_resp)) {
            $bot_body = json_decode(wp_remote_retrieve_body($bot_resp), true);
            if (isset($bot_body['ok']) && $bot_body['ok']) {
                $bot_id = $bot_body['result']['id'];
                $bot_username = $bot_body['result']['username'] ?? 'Unknown';
                
                // Check bot's status in the channel
                $member_url = "https://api.telegram.org/bot" . urlencode($bot_token) . "/getChatMember?chat_id=" . urlencode($channel) . "&user_id=" . $bot_id;
                $member_resp = wp_remote_get($member_url);
                if (!is_wp_error($member_resp)) {
                    $member_body = json_decode(wp_remote_retrieve_body($member_resp), true);
                    if (isset($member_body['ok']) && $member_body['ok']) {
                        $status = $member_body['result']['status'] ?? 'unknown';
                        if ($status === 'administrator' || $status === 'creator') {
                            $results[] = '<strong>✓ Bot is Administrator:</strong> @' . esc_html($bot_username) . ' has admin privileges';
                        } else {
                            $results[] = '<strong>⚠ Bot Status:</strong> @' . esc_html($bot_username) . ' is ' . esc_html($status) . ' (should be administrator for full access)';
                        }
                    }
                }
            }
        }
        
        // Fetch and count accessible messages
        $messages = API::instance()->fetch_channel_messages($channel, 100);
        $message_count = count($messages);
        
        if ($message_count > 0) {
            $results[] = '<strong>✓ Accessible Messages:</strong> ' . $message_count . ' message(s) currently available';
            
            // Show info about the most recent message
            if (isset($messages[0])) {
                $latest = $messages[0];
                $date = date('Y-m-d H:i:s', $latest['date']);
                $preview = mb_substr($latest['text'] ?? '', 0, 50);
                if (strlen($latest['text'] ?? '') > 50) $preview .= '...';
                $results[] = '<strong>Latest Message:</strong> ' . esc_html($date) . ' - "' . esc_html($preview) . '"';
            }
        } else {
            $results[] = '<strong>⚠ No Messages:</strong> No messages currently accessible. Make sure:
                <ul style="margin-top:5px;">
                    <li>The bot was added to the channel</li>
                    <li>Messages have been posted AFTER the bot was added</li>
                    <li>The bot has the necessary permissions</li>
                </ul>';
        }
        
        wp_send_json_success(implode('<br>', $results));
    }
    
    public function ajax_reload_messages() {
        check_ajax_referer('dfx_tg_feed_reload', 'dfx_tg_feed_reload_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }
        
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        $channel = sanitize_text_field($_POST['channel'] ?? '');
        
        if (!$bot_token || !$channel) {
            wp_send_json_error('Please set both Bot Token and Channel.');
        }
        
        // Fetch all available messages (limit to 100 for safety)
        $messages = API::instance()->fetch_channel_messages($channel, 100);
        
        if (empty($messages)) {
            wp_send_json_error('No messages found. Make sure the bot is admin and messages have been posted after the bot was added.');
        }
        
        $count = count($messages);
        $new_count = 0;
        $updated_count = 0;
        
        // Messages are already stored by the API, but let's count them
        foreach ($messages as $msg) {
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
                        'value' => $msg['id'],
                    ],
                ],
                'posts_per_page' => 1,
            ]);
            
            if (empty($existing)) {
                $new_count++;
            } else {
                $updated_count++;
            }
        }
        
        wp_send_json_success(sprintf(
            __('Successfully reloaded %d messages from channel %s. New: %d, Updated: %d', 'dfx-tg-feed'),
            $count,
            '<strong>' . esc_html($channel) . '</strong>',
            $new_count,
            $updated_count
        ));
    }
    
    public function ajax_reset_offset() {
        check_ajax_referer('dfx_tg_feed_reset_offset', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }
        
        $result = API::instance()->reset_update_offset();
        
        if ($result) {
            wp_send_json_success('Update offset has been reset. The next fetch will retrieve all available updates from Telegram.');
        } else {
            wp_send_json_error('Failed to reset offset. Make sure bot token is configured.');
        }
    }
    
    public function ajax_fetch_messages_by_id() {
        check_ajax_referer('dfx_tg_feed_fetch_by_id', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }
        
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        $channel = sanitize_text_field($_POST['channel'] ?? '');
        $start_id = intval($_POST['start_id'] ?? 1);
        $count = min(intval($_POST['count'] ?? 50), 100); // Max 100 for safety
        
        if (!$bot_token || !$channel) {
            wp_send_json_error('Please set both Bot Token and Channel.');
        }
        
        // Clean the @ if present
        $channel_id = $channel;
        if (strpos($channel, '@') === 0) {
            $channel_id = substr($channel, 1);
        }
        
        $fetched = 0;
        $errors = 0;
        $messages = [];
        
        // Try to fetch messages by ID range
        for ($msg_id = $start_id; $msg_id < $start_id + $count; $msg_id++) {
            // Use forwardMessage API to check if message exists and is accessible
            // This is a workaround since there's no direct getMessage API
            $url = "https://api.telegram.org/bot" . urlencode($bot_token) . "/getChat?chat_id=" . urlencode($channel);
            
            // Alternative: Try to copy the message to check if it exists
            // We'll use a different approach - just inform the user about the limitation
            $errors++;
        }
        
        // Since Telegram Bot API doesn't provide a direct way to fetch historical messages by ID,
        // we need to inform the user about this limitation
        wp_send_json_error(
            'The Telegram Bot API does not provide a method to fetch historical channel messages by ID. ' .
            'The bot can only access messages posted AFTER it was added as admin. ' .
            'To access historical messages, you would need to use Telegram\'s Client API (requires user authentication) or MTProto, ' .
            'which is beyond the scope of this WordPress plugin. ' .
            '<br><br><strong>Recommendation:</strong> Reset the offset (button above) and post new messages to the channel for them to appear.'
        );
    }
}