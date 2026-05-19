<?php
namespace DFXTgFeed;
if (!defined('ABSPATH')) exit;

class Settings {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function register() {
        register_setting('dfxtgfeed', 'dfxtgfeed_bot_token', [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitize_bot_token'],
            'default'           => '',
        ]);
        register_setting('dfxtgfeed', 'dfxtgfeed_default_count', [
            'type'              => 'integer',
            'sanitize_callback' => [$this, 'sanitize_default_count'],
            'default'           => 10,
        ]);
        register_setting('dfxtgfeed', 'dfxtgfeed_channel', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);
    }

    public function sanitize_bot_token($value) {
        // Bot tokens are of the form `<bot_id>:<secret>` — strip whitespace and
        // any character that can't appear in a real token.
        $value = trim((string) $value);
        return preg_replace('/[^A-Za-z0-9:_\-]/', '', $value);
    }

    public function sanitize_default_count($value) {
        $value = absint($value);
        if ($value < 1) {
            return 10;
        }
        if ($value > 100) {
            return 100;
        }
        return $value;
    }

    public function render_page() {
        $bot_token = esc_attr(get_option('dfxtgfeed_bot_token', ''));
        $channel = esc_attr(get_option('dfxtgfeed_channel', ''));
        ?>
        <div class="wrap">
            <h1><?php _e('DFX Telegram Channel Feed', 'dfx-telegram-channel-feed'); ?></h1>

            <h2>How to setup your Telegram Bot</h2>
            <ol>
                <li>Open Telegram and chat with <a href="https://t.me/botfather" target="_blank">@BotFather</a></li>
                <li>Send <code>/newbot</code>, follow instructions, and copy your bot token.</li>
                <li>Paste the bot token below and click "Save Settings".</li>
                <li>Add your bot as an <strong>admin</strong> to each Telegram channel you want to display (channel &rarr; Administrators &rarr; Add Admin &rarr; [YOUR BOT USERNAME]).</li>
                <li>Use any channel with your bot by specifying the channel username in the shortcode or block (e.g., <code>[dfxtgfeed_channel_feed channel="@yourchannel"]</code>).</li>
            </ol>

            <form method="post" action="options.php">
                <?php settings_fields('dfxtgfeed'); do_settings_sections('dfxtgfeed'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Telegram Bot Token', 'dfx-telegram-channel-feed'); ?></th>
                        <td><input type="text" name="dfxtgfeed_bot_token" value="<?php echo $bot_token; ?>" size="50" autocomplete="off"/></td>
                    </tr>
                    <tr>
                        <th><?php _e('Test Channel Username (optional)', 'dfx-telegram-channel-feed'); ?></th>
                        <td>
                            <input type="text" name="dfxtgfeed_channel" value="<?php echo $channel; ?>" size="32" autocomplete="off" placeholder="@yourchannel"/>
                            <p class="description"><?php _e('This field is only used for connection testing below. You can specify any channel directly in your shortcodes or blocks.', 'dfx-telegram-channel-feed'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Default Message Count', 'dfx-telegram-channel-feed'); ?></th>
                        <td><input type="number" name="dfxtgfeed_default_count" value="<?php echo esc_attr(get_option('dfxtgfeed_default_count', 10)); ?>" min="1" max="100"/></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'dfx-telegram-channel-feed')); ?>
            </form>

            <hr />
            <h3><?php _e('Test your configuration', 'dfx-telegram-channel-feed'); ?></h3>
            <button class="button" id="dfxtgfeed-test-btn"><?php _e('Test Connection', 'dfx-telegram-channel-feed'); ?></button>
            <div id="dfxtgfeed-test-result"></div>

            <hr />
            <h3><?php _e('Reload Messages from Telegram', 'dfx-telegram-channel-feed'); ?></h3>
            <p><?php _e('Fetch all available messages from the channel and save them to the database. This will sync new messages and update existing ones.', 'dfx-telegram-channel-feed'); ?></p>
            <form id="dfxtgfeed-reload-form" method="post">
                <input type="text" name="channel" value="<?php echo $channel;?>" placeholder="@channelusername" style="width: 300px;" />
                <button class="button button-primary" id="dfxtgfeed-reload-btn"><?php _e('Reload All Messages', 'dfx-telegram-channel-feed'); ?></button>
                <?php wp_nonce_field('dfxtgfeed_reload', 'dfxtgfeed_reload_nonce'); ?>
            </form>
            <div id="dfxtgfeed-reload-result"></div>

            <script>
            document.getElementById('dfxtgfeed-test-btn').addEventListener('click', function(e){
                e.preventDefault();
                document.getElementById('dfxtgfeed-test-result').textContent = 'Testing...';
                fetch(ajaxurl + '?action=dfxtgfeed_test')
                  .then(r=>r.json())
                  .then(resp=>{
                    document.getElementById('dfxtgfeed-test-result').innerHTML = resp.success ? '<span style="color:green">'+resp.data+'</span>' : '<span style="color:red">'+resp.data+'</span>';
                  });
            });

            document.getElementById('dfxtgfeed-reload-form').addEventListener('submit', function(e){
                e.preventDefault();
                let resultDiv = document.getElementById('dfxtgfeed-reload-result');
                let btn = document.getElementById('dfxtgfeed-reload-btn');
                btn.disabled = true;
                resultDiv.innerHTML = '<span style="color:blue;">Reloading messages... This may take a moment.</span>';
                
                let data = new FormData(this);
                data.append('action', 'dfxtgfeed_reload');
                
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
        $bot_token = get_option('dfxtgfeed_bot_token', '');
        $channel = get_option('dfxtgfeed_channel', '');
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
        check_ajax_referer('dfxtgfeed_reload', 'dfxtgfeed_reload_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }
        
        $bot_token = get_option('dfxtgfeed_bot_token', '');
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
                'post_type' => 'dfxtgfeed_message',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => '_dfxtgfeed_channel',
                        'value' => $channel,
                    ],
                    [
                        'key' => '_dfxtgfeed_message_id',
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
            __('Successfully reloaded %d messages from channel %s. New: %d, Updated: %d', 'dfx-telegram-channel-feed'),
            $count,
            '<strong>' . esc_html($channel) . '</strong>',
            $new_count,
            $updated_count
        ));
    }
}