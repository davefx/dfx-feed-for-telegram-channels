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
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('DFX Telegram Channel Feed', 'dfx-tg-feed'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('dfx_tg_feed'); do_settings_sections('dfx_tg_feed'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Telegram Bot Token', 'dfx-tg-feed'); ?></th>
                        <td><input type="text" name="dfx_tg_feed_bot_token" value="<?php echo esc_attr(get_option('dfx_tg_feed_bot_token')); ?>" size="50"/></td>
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
                <?php submit_button(); ?>
            </form>
            <form id="dfx-tg-feed-refresh-form" method="post">
                <h3><?php _e('Force cache refresh', 'dfx-tg-feed'); ?></h3>
                <input type="text" name="channel" placeholder="@channelusername" />
                <button class="button" id="dfx-tg-feed-refresh-btn"><?php _e('Refresh Now', 'dfx-tg-feed'); ?></button>
                <?php wp_nonce_field('dfx_tg_feed_refresh'); ?>
            </form>
            <div id="dfx-tg-feed-refresh-result"></div>
            <script>
            document.getElementById('dfx-tg-feed-refresh-form').addEventListener('submit', function(e){
                e.preventDefault();
                let channel = this.querySelector('input[name="channel"]').value;
                let data = new FormData(this);
                fetch(ajaxurl, { method: "POST", body: data })
                .then(r=>r.json())
                .then(resp=>{
                    document.getElementById('dfx-tg-feed-refresh-result').textContent = resp.success ? "Refreshed!" : "Failed: "+resp.data
                });
            });
            </script>
        </div>
        <?php
    }
}