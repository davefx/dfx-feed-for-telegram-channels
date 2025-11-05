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
        register_setting('dfx_tg_feed', 'dfx_tg_feed_ttl');
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
                    <tr>
                        <th><?php _e('Cache Time (Seconds)', 'dfx-tg-feed'); ?></th>
                        <td><input type="number" name="dfx_tg_feed_ttl" value="<?php echo esc_attr(get_option('dfx_tg_feed_ttl', 300)); ?>" min="60" max="86400"/></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'dfx-tg-feed')); ?>
            </form>

            <hr />
            <h3><?php _e('Test your configuration', 'dfx-tg-feed'); ?></h3>
            <button class="button" id="dfx-tg-feed-test-btn"><?php _e('Test Connection', 'dfx-tg-feed'); ?></button>
            <div id="dfx-tg-feed-test-result"></div>

            <form id="dfx-tg-feed-refresh-form" method="post">
                <h3><?php _e('Force cache refresh', 'dfx-tg-feed'); ?></h3>
                <input type="text" name="channel" value="<?php echo $channel;?>" placeholder="@channelusername" />
                <button class="button" id="dfx-tg-feed-refresh-btn"><?php _e('Refresh Now', 'dfx-tg-feed'); ?></button>
                <?php wp_nonce_field('dfx_tg_feed_refresh'); ?>
            </form>
            <div id="dfx-tg-feed-refresh-result"></div>
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

            document.getElementById('dfx-tg-feed-refresh-form').addEventListener('submit', function(e){
                e.preventDefault();
                let data = new FormData(this);
                fetch(ajaxurl, { method: "POST", body: data })
                .then(r=>r.json())
                .then(resp=>{
                    document.getElementById('dfx-tg-feed-refresh-result').textContent = resp.success ? "Refreshed!" : "Failed: "+resp.data
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
        // Try to fetch channel info
        $url = "https://api.telegram.org/bot" . urlencode($bot_token) . "/getChat?chat_id=" . urlencode($channel);
        $resp = wp_remote_get($url);
        if (is_wp_error($resp)) {
            wp_send_json_error('Network error.');
        }
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (!isset($body['ok']) || !$body['ok']) {
            wp_send_json_error('Telegram returned error: ' . (isset($body['description']) ? $body['description'] : 'Unknown error.'));
        }
        wp_send_json_success('Success! Bot can access channel: <strong>' . esc_html($body['result']['title'] ?? $channel) . '</strong>');
    }
}