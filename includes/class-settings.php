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
        register_setting('dfx_tg_feed', 'dfx_tg_feed_update_mode');
        register_setting('dfx_tg_feed', 'dfx_tg_feed_webhook_secret');
    }

    public function render_page() {
        $bot_token = esc_attr(get_option('dfx_tg_feed_bot_token', ''));
        $channel = esc_attr(get_option('dfx_tg_feed_channel', ''));
        $update_mode = get_option('dfx_tg_feed_update_mode', 'polling');
        $webhook_secret = get_option('dfx_tg_feed_webhook_secret', '');
        
        // Generate a new secret if not set
        if (empty($webhook_secret)) {
            $webhook_secret = wp_generate_password(32, false);
            update_option('dfx_tg_feed_webhook_secret', $webhook_secret);
        }
        
        $webhook_url = Webhook::get_webhook_url();
        ?>
        <div class="wrap">
            <h1><?php _e('DFX Telegram Channel Feed', 'dfx-tg-feed'); ?></h1>

            <h2>How to setup your Telegram Bot</h2>
            <ol>
                <li>Open Telegram and chat with <a href="https://t.me/botfather" target="_blank">@BotFather</a></li>
                <li>Send <code>/newbot</code>, follow instructions, and copy your bot token.</li>
                <li>Paste the bot token below and click "Save Settings".</li>
                <li>Add your bot as an <strong>admin</strong> to each Telegram channel you want to display (channel &rarr; Administrators &rarr; Add Admin &rarr; [YOUR BOT USERNAME]).</li>
                <li>Choose your preferred <strong>Update Mode</strong> below (Webhook for real-time updates, or Polling for on-demand fetching).</li>
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
                    <tr>
                        <th><?php _e('Update Mode', 'dfx-tg-feed'); ?></th>
                        <td>
                            <select name="dfx_tg_feed_update_mode" id="dfx_tg_feed_update_mode">
                                <option value="polling" <?php selected($update_mode, 'polling'); ?>><?php _e('Polling (getUpdates)', 'dfx-tg-feed'); ?></option>
                                <option value="webhook" <?php selected($update_mode, 'webhook'); ?>><?php _e('Webhook (Real-time)', 'dfx-tg-feed'); ?></option>
                            </select>
                            <p class="description">
                                <strong><?php _e('Polling:', 'dfx-tg-feed'); ?></strong> <?php _e('Messages are fetched on-demand when pages load. Simpler setup, but may have slight delays.', 'dfx-tg-feed'); ?><br>
                                <strong><?php _e('Webhook:', 'dfx-tg-feed'); ?></strong> <?php _e('Telegram sends messages to your site in real-time. Requires HTTPS and webhook registration.', 'dfx-tg-feed'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'dfx-tg-feed')); ?>
            </form>

            <div id="dfx-tg-webhook-section" style="<?php echo $update_mode !== 'webhook' ? 'display:none;' : ''; ?>">
                <hr />
                <h3><?php _e('Webhook Configuration', 'dfx-tg-feed'); ?></h3>
                <p><?php _e('Webhooks allow Telegram to send messages to your site in real-time, instead of polling. This is more efficient and provides instant updates.', 'dfx-tg-feed'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Webhook Secret Token', 'dfx-tg-feed'); ?></th>
                        <td>
                            <input type="text" name="dfx_tg_feed_webhook_secret" value="<?php echo esc_attr($webhook_secret); ?>" size="50" autocomplete="off" form="dfx-tg-webhook-secret-form"/>
                            <p class="description"><?php _e('This secret token is used to verify incoming webhook requests from Telegram.', 'dfx-tg-feed'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Your Webhook URL', 'dfx-tg-feed'); ?></th>
                        <td>
                            <code id="dfx-tg-webhook-url"><?php echo esc_url($webhook_url); ?></code>
                            <p class="description"><?php _e('This is the URL that Telegram will send updates to.', 'dfx-tg-feed'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Webhook Status', 'dfx-tg-feed'); ?></th>
                        <td>
                            <span id="dfx-tg-webhook-status"><?php _e('Checking...', 'dfx-tg-feed'); ?></span>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button class="button button-primary" id="dfx-tg-webhook-register-btn" <?php echo empty($bot_token) ? 'disabled' : ''; ?>><?php _e('Register Webhook', 'dfx-tg-feed'); ?></button>
                    <button class="button" id="dfx-tg-webhook-unregister-btn" <?php echo empty($bot_token) ? 'disabled' : ''; ?>><?php _e('Unregister Webhook', 'dfx-tg-feed'); ?></button>
                </p>
                <div id="dfx-tg-webhook-result"></div>
            </div>

            <hr />
            <h3><?php _e('Test your configuration', 'dfx-tg-feed'); ?></h3>
            <button class="button" id="dfx-tg-feed-test-btn"><?php _e('Test Connection', 'dfx-tg-feed'); ?></button>
            <div id="dfx-tg-feed-test-result"></div>

            <hr />
            <h3><?php _e('Reload Messages from Telegram', 'dfx-tg-feed'); ?></h3>
            <p><?php _e('Fetch all available messages from the channel and save them to the database. This will sync new messages and update existing ones.', 'dfx-tg-feed'); ?></p>
            <form id="dfx-tg-feed-reload-form" method="post">
                <input type="text" name="channel" value="<?php echo $channel;?>" placeholder="@channelusername" style="width: 300px;" />
                <button class="button button-primary" id="dfx-tg-feed-reload-btn"><?php _e('Reload All Messages', 'dfx-tg-feed'); ?></button>
                <?php wp_nonce_field('dfx_tg_feed_reload', 'dfx_tg_feed_reload_nonce'); ?>
            </form>
            <div id="dfx-tg-feed-reload-result"></div>

            <script>
            (function() {
                // Toggle webhook section visibility based on update mode
                var updateModeSelect = document.getElementById('dfx_tg_feed_update_mode');
                var webhookSection = document.getElementById('dfx-tg-webhook-section');
                
                updateModeSelect.addEventListener('change', function() {
                    if (this.value === 'webhook') {
                        webhookSection.style.display = '';
                        checkWebhookStatus();
                    } else {
                        webhookSection.style.display = 'none';
                    }
                });
                
                // Webhook status check
                function checkWebhookStatus() {
                    fetch(ajaxurl + '?action=dfx_tg_feed_webhook_status&_wpnonce=<?php echo wp_create_nonce('dfx_tg_feed_webhook'); ?>')
                        .then(r => r.json())
                        .then(resp => {
                            let statusEl = document.getElementById('dfx-tg-webhook-status');
                            if (resp.success && resp.data.info) {
                                let info = resp.data.info;
                                if (info.url) {
                                    let ourUrl = document.getElementById('dfx-tg-webhook-url').textContent;
                                    if (info.url === ourUrl) {
                                        statusEl.innerHTML = '<span style="color:green;">✓ <?php _e('Webhook is active and pointing to this site', 'dfx-tg-feed'); ?></span>';
                                    } else {
                                        statusEl.innerHTML = '<span style="color:orange;">⚠ <?php _e('Webhook is set to a different URL:', 'dfx-tg-feed'); ?> ' + info.url + '</span>';
                                    }
                                    if (info.last_error_message) {
                                        statusEl.innerHTML += '<br><span style="color:red;"><?php _e('Last error:', 'dfx-tg-feed'); ?> ' + info.last_error_message + '</span>';
                                    }
                                } else {
                                    statusEl.innerHTML = '<span style="color:gray;">○ <?php _e('No webhook configured', 'dfx-tg-feed'); ?></span>';
                                }
                            } else {
                                statusEl.innerHTML = '<span style="color:red;">✗ <?php _e('Unable to check status', 'dfx-tg-feed'); ?></span>';
                            }
                        })
                        .catch(() => {
                            document.getElementById('dfx-tg-webhook-status').innerHTML = '<span style="color:red;">✗ <?php _e('Error checking status', 'dfx-tg-feed'); ?></span>';
                        });
                }
                
                // Check status on page load if webhook mode is selected
                <?php if (!empty($bot_token) && $update_mode === 'webhook'): ?>
                checkWebhookStatus();
                <?php elseif ($update_mode === 'webhook'): ?>
                document.getElementById('dfx-tg-webhook-status').innerHTML = '<span style="color:gray;"><?php _e('Please configure your bot token first', 'dfx-tg-feed'); ?></span>';
                <?php endif; ?>
                
                // Register webhook
                document.getElementById('dfx-tg-webhook-register-btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    let btn = this;
                    let resultDiv = document.getElementById('dfx-tg-webhook-result');
                    btn.disabled = true;
                    resultDiv.innerHTML = '<span style="color:blue;"><?php _e('Registering webhook...', 'dfx-tg-feed'); ?></span>';
                    
                    fetch(ajaxurl + '?action=dfx_tg_feed_webhook_register&_wpnonce=<?php echo wp_create_nonce('dfx_tg_feed_webhook'); ?>')
                        .then(r => r.json())
                        .then(resp => {
                            btn.disabled = false;
                            if (resp.success) {
                                resultDiv.innerHTML = '<span style="color:green;">✓ ' + resp.data + '</span>';
                                checkWebhookStatus();
                            } else {
                                resultDiv.innerHTML = '<span style="color:red;">✗ ' + resp.data + '</span>';
                            }
                        })
                        .catch(err => {
                            btn.disabled = false;
                            resultDiv.innerHTML = '<span style="color:red;">✗ <?php _e('Error:', 'dfx-tg-feed'); ?> ' + err.message + '</span>';
                        });
                });
                
                // Unregister webhook
                document.getElementById('dfx-tg-webhook-unregister-btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    let btn = this;
                    let resultDiv = document.getElementById('dfx-tg-webhook-result');
                    btn.disabled = true;
                    resultDiv.innerHTML = '<span style="color:blue;"><?php _e('Removing webhook...', 'dfx-tg-feed'); ?></span>';
                    
                    fetch(ajaxurl + '?action=dfx_tg_feed_webhook_unregister&_wpnonce=<?php echo wp_create_nonce('dfx_tg_feed_webhook'); ?>')
                        .then(r => r.json())
                        .then(resp => {
                            btn.disabled = false;
                            if (resp.success) {
                                resultDiv.innerHTML = '<span style="color:green;">✓ ' + resp.data + '</span>';
                                checkWebhookStatus();
                            } else {
                                resultDiv.innerHTML = '<span style="color:red;">✗ ' + resp.data + '</span>';
                            }
                        })
                        .catch(err => {
                            btn.disabled = false;
                            resultDiv.innerHTML = '<span style="color:red;">✗ <?php _e('Error:', 'dfx-tg-feed'); ?> ' + err.message + '</span>';
                        });
                });
                
                // Test connection
                document.getElementById('dfx-tg-feed-test-btn').addEventListener('click', function(e){
                    e.preventDefault();
                    document.getElementById('dfx-tg-feed-test-result').textContent = '<?php _e('Testing...', 'dfx-tg-feed'); ?>';
                    fetch(ajaxurl + '?action=dfx_tg_feed_test')
                      .then(r=>r.json())
                      .then(resp=>{
                        document.getElementById('dfx-tg-feed-test-result').innerHTML = resp.success ? '<span style="color:green">'+resp.data+'</span>' : '<span style="color:red">'+resp.data+'</span>';
                      });
                });

                // Reload messages
                document.getElementById('dfx-tg-feed-reload-form').addEventListener('submit', function(e){
                    e.preventDefault();
                    let resultDiv = document.getElementById('dfx-tg-feed-reload-result');
                    let btn = document.getElementById('dfx-tg-feed-reload-btn');
                    btn.disabled = true;
                    resultDiv.innerHTML = '<span style="color:blue;"><?php _e('Reloading messages... This may take a moment.', 'dfx-tg-feed'); ?></span>';
                    
                    let data = new FormData(this);
                    data.append('action', 'dfx_tg_feed_reload');
                    
                    fetch(ajaxurl, { method: "POST", body: data })
                    .then(r=>r.json())
                    .then(resp=>{
                        btn.disabled = false;
                        resultDiv.innerHTML = resp.success ? '<span style="color:green;">'+resp.data+'</span>' : '<span style="color:red;"><?php _e('Failed:', 'dfx-tg-feed'); ?> '+resp.data+'</span>';
                    })
                    .catch(err => {
                        btn.disabled = false;
                        resultDiv.innerHTML = '<span style="color:red;"><?php _e('Error:', 'dfx-tg-feed'); ?> '+err.message+'</span>';
                    });
                });
            })();
            </script>
            <hr>
            <h4><?php _e('Troubleshooting', 'dfx-tg-feed'); ?></h4>
            <ul>
                <li><?php _e('If test fails, check your bot token and ensure the bot is an admin/member in your channel.', 'dfx-tg-feed'); ?></li>
                <li><?php _e('The Telegram Bot API will only receive messages that happen after your bot joins the channel (no access to full history before that point).', 'dfx-tg-feed'); ?></li>
                <li><?php _e('Make sure your site is accessible via HTTPS for the webhook to work.', 'dfx-tg-feed'); ?></li>
                <li><?php _e('If the webhook shows errors, try unregistering and re-registering it.', 'dfx-tg-feed'); ?></li>
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
        
        // Check webhook status
        $webhook_info = Webhook::get_webhook_info($bot_token);
        if ($webhook_info['success'] && !empty($webhook_info['info']['url'])) {
            $results[] = '<strong>✓ Webhook Active:</strong> Messages will be received in real-time';
        } else {
            $results[] = '<strong>⚠ No Webhook:</strong> Register a webhook for real-time updates, or messages will be fetched on page load';
        }
        
        // Check stored messages count
        $stored_messages = get_posts([
            'post_type' => 'dfx_tg_message',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_tg_channel',
                    'value' => $channel,
                ],
            ],
            'fields' => 'ids',
        ]);
        $stored_count = count($stored_messages);
        
        if ($stored_count > 0) {
            $results[] = '<strong>✓ Stored Messages:</strong> ' . $stored_count . ' message(s) in database for this channel';
        } else {
            $results[] = '<strong>⚠ No Stored Messages:</strong> No messages stored yet. Post a message to the channel to test.';
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
    
    /**
     * AJAX handler to register webhook with Telegram
     */
    public function ajax_webhook_register() {
        check_ajax_referer('dfx_tg_feed_webhook');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'dfx-tg-feed'));
        }
        
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        if (empty($bot_token)) {
            wp_send_json_error(__('Bot token not configured.', 'dfx-tg-feed'));
        }
        
        $secret_token = get_option('dfx_tg_feed_webhook_secret', '');
        
        $result = Webhook::register_webhook($bot_token, $secret_token);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler to unregister webhook from Telegram
     */
    public function ajax_webhook_unregister() {
        check_ajax_referer('dfx_tg_feed_webhook');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'dfx-tg-feed'));
        }
        
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        if (empty($bot_token)) {
            wp_send_json_error(__('Bot token not configured.', 'dfx-tg-feed'));
        }
        
        $result = Webhook::unregister_webhook($bot_token);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler to get webhook status
     */
    public function ajax_webhook_status() {
        check_ajax_referer('dfx_tg_feed_webhook');
        
        $bot_token = get_option('dfx_tg_feed_bot_token', '');
        if (empty($bot_token)) {
            wp_send_json_error(__('Bot token not configured.', 'dfx-tg-feed'));
        }
        
        $result = Webhook::get_webhook_info($bot_token);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}