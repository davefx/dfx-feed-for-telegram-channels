<?php
namespace DFXTgFeed;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Plugin {

    /** @var Plugin */
    private static $instance = null;
    
    /** @var string Menu slug for the top-level admin menu */
    const MENU_SLUG = 'dfxtgfeed-messages';

    /** @var string Cron hook name for the periodic background refresh */
    const CRON_HOOK = 'dfxtgfeed_cron_refresh';

    /** @var string Custom cron schedule slug */
    const CRON_SCHEDULE = 'dfxtgfeed_two_minutes';

    /** Return the singleton instance */
    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function init() {
        // Autoload or require class files
        spl_autoload_register(function($class) {
            if (strpos($class, __NAMESPACE__) === 0) {
                $name = strtolower(str_replace('\\', '-', str_replace(__NAMESPACE__ . '\\', '', $class)));
                $file = __DIR__ . '/class-' . $name . '.php';
                if (file_exists($file)) { require $file; }
            }
        });

        add_action('init',          [$this, 'register_shortcodes']);
        add_action('init',          [$this, 'register_blocks']);
        add_action('init',          [$this, 'register_post_type']);
        add_action('init',          [$this, 'init_elementor']);
        add_action('admin_menu',    [$this, 'settings_page']);
        add_action('admin_init',    [$this, 'register_settings']);
        add_action('wp_ajax_dfxtgfeed_test', [Settings::instance(), 'ajax_test_bot_channel']);
        add_action('wp_ajax_dfxtgfeed_reload', [Settings::instance(), 'ajax_reload_messages']);
        add_action('wp_ajax_dfxtgfeed_refresh', [PostType::instance(), 'ajax_refresh_messages']);
        add_action('wp_ajax_dfxtgfeed_proxy_sticker', [$this, 'ajax_proxy_sticker']);
        add_action('wp_ajax_nopriv_dfxtgfeed_proxy_sticker', [$this, 'ajax_proxy_sticker']);
        add_action('wp_ajax_dfxtgfeed_proxy_media', [$this, 'ajax_proxy_media']);
        add_action('wp_ajax_nopriv_dfxtgfeed_proxy_media', [$this, 'ajax_proxy_media']);

        // Background refresh: a 2-minute WP-cron job that walks every channel
        // we have stored data for and pulls new updates. Self-heals on each
        // request via wp_schedule_event() being idempotent.
        add_filter('cron_schedules',           [$this, 'register_cron_schedule']);
        add_action('init',                     [$this, 'schedule_background_refresh']);
        add_action(self::CRON_HOOK,            [$this, 'cron_refresh_callback']);

        // Migrate v1.0.0 prefixes and scrub legacy token-bearing media URLs.
        $this->maybe_run_db_migration();
    }

    /**
     * Build the public proxy URL for a Telegram file_id. The URL never carries
     * the bot token; the proxy resolves the file server-side.
     */
    public static function media_proxy_url($file_id) {
        if (empty($file_id)) {
            return '';
        }
        return add_query_arg([
            'action'  => 'dfxtgfeed_proxy_media',
            'file_id' => $file_id,
            'nonce'   => wp_create_nonce('dfxtgfeed_media_proxy'),
        ], admin_url('admin-ajax.php'));
    }

    /**
     * Register a 2-minute custom cron schedule. WP doesn't ship with anything
     * shorter than "hourly", so we add our own.
     */
    public function register_cron_schedule($schedules) {
        if (!isset($schedules[self::CRON_SCHEDULE])) {
            $schedules[self::CRON_SCHEDULE] = [
                'interval' => 2 * MINUTE_IN_SECONDS,
                'display'  => __('Every 2 minutes (DFX Telegram Channel Feed)', 'dfxtgfeed'),
            ];
        }
        return $schedules;
    }

    /**
     * Self-heal: ensure the cron event is scheduled. wp_schedule_event() is a
     * no-op if the hook is already scheduled, so calling this on every init
     * is safe and recovers from a deleted cron entry.
     */
    public function schedule_background_refresh() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Stagger first run by a minute to avoid piling onto activation traffic.
            wp_schedule_event(time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    /**
     * Cron callback. Walks every channel we have stored messages for and
     * pulls fresh updates via the existing PostType::refresh_messages path.
     *
     * Caveats:
     *   - WP-cron only fires when traffic hits the site. On low-traffic sites,
     *     configure a real system cron pinging wp-cron.php for reliable
     *     execution. The on-demand refresh in Shortcodes is the fallback.
     *   - Reuses the same `dfxtgfeed_refreshing_<channel>` lock that the
     *     on-demand path uses, so concurrent runs collapse to one.
     */
    public function cron_refresh_callback() {
        if (!get_option('dfxtgfeed_bot_token')) {
            return;
        }

        global $wpdb;
        $channels = $wpdb->get_col(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_dfxtgfeed_channel' AND meta_value != ''"
        );
        if (empty($channels)) {
            return;
        }

        $limit = max(10, (int) get_option('dfxtgfeed_default_count', 10));
        foreach ($channels as $channel) {
            $channel_safe = sanitize_key($channel);
            if (get_transient("dfxtgfeed_refreshing_{$channel_safe}")) {
                continue;
            }
            set_transient("dfxtgfeed_refreshing_{$channel_safe}", true, MINUTE_IN_SECONDS);
            try {
                PostType::instance()->refresh_messages($channel, $limit);
                set_transient("dfxtgfeed_last_sync_{$channel_safe}", time(), HOUR_IN_SECONDS);
            } catch (\Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('DFX TG Feed cron refresh failed for ' . $channel . ': ' . $e->getMessage());
                }
            } finally {
                delete_transient("dfxtgfeed_refreshing_{$channel_safe}");
            }
        }
    }

    public function register_shortcodes() {
        Shortcodes::instance()->register();
    }

    public function register_blocks() {
        Blocks::instance()->register();
    }

    public function register_post_type() {
        PostType::instance()->register();
    }

    public function init_elementor() {
        Elementor::instance()->init();
    }

    public function settings_page() {
        // Add top-level menu
        add_menu_page(
            __('Telegram Messages', 'dfxtgfeed'),
            __('Telegram Messages', 'dfxtgfeed'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'redirect_to_messages'],
            'dashicons-email-alt',
            25
        );
        
        // Add submenu for viewing all messages (custom post type listing)
        add_submenu_page(
            self::MENU_SLUG,
            __('All Messages', 'dfxtgfeed'),
            __('All Messages', 'dfxtgfeed'),
            'edit_posts',
            'edit.php?post_type=dfxtgfeed_message'
        );
        
        // Add settings submenu under the top-level menu
        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'dfxtgfeed'),
            __('Settings', 'dfxtgfeed'),
            'manage_options',
            'dfxtgfeed',
            [Settings::instance(), 'render_page']
        );
    }
    
    /**
     * Redirect to the messages listing page when main menu is clicked
     */
    public function redirect_to_messages() {
        wp_redirect(admin_url('edit.php?post_type=dfxtgfeed_message'));
        exit;
    }

    public function register_settings() {
        Settings::instance()->register();
    }

    /**
     * AJAX handler to proxy TGS sticker files (bypasses CORS).
     * Resolves the Telegram URL server-side from a file_id — never trusts a
     * client-supplied URL, which would otherwise allow open-proxying any
     * Telegram URL attached to our bot token.
     */
    public function ajax_proxy_sticker() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'dfxtgfeed_sticker_proxy')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $file_id = isset($_GET['file_id']) ? sanitize_text_field($_GET['file_id']) : '';
        if (empty($file_id)) {
            wp_send_json_error(['message' => 'Missing file_id'], 400);
        }

        // Cache parsed JSON for 24h (TGS files are immutable per file_id).
        $cache_key = 'dfxtgfeed_sticker_' . md5($file_id);
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            header('Content-Type: application/json');
            echo $cached_data;
            exit;
        }

        $sticker_url = API::instance()->resolve_file_url($file_id);
        if (!$sticker_url) {
            wp_send_json_error(['message' => 'Failed to resolve sticker'], 500);
        }

        $response = wp_remote_get($sticker_url, [
            'timeout'   => 15,
            'sslverify' => true,
        ]);
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DFX TG Feed: Failed to fetch sticker: ' . $response->get_error_message());
            }
            wp_send_json_error(['message' => 'Failed to fetch sticker from Telegram'], 500);
        }
        if (wp_remote_retrieve_response_code($response) !== 200) {
            wp_send_json_error(['message' => 'Telegram returned a non-200 status'], 500);
        }

        $sticker_data = wp_remote_retrieve_body($response);

        // TGS files are gzipped JSON
        if (function_exists('gzdecode')) {
            $decompressed = @gzdecode($sticker_data);
            if ($decompressed !== false) {
                $sticker_data = $decompressed;
            }
        }

        if (json_decode($sticker_data, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid TGS data (not valid JSON)'], 500);
        }

        set_transient($cache_key, $sticker_data, DAY_IN_SECONDS);

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo $sticker_data;
        exit;
    }

    /**
     * AJAX handler to proxy arbitrary Telegram media (photos, video/animation
     * thumbnails, static and webm stickers). Streams bytes from disk cache
     * when available; otherwise fetches from Telegram, persists, and serves.
     */
    public function ajax_proxy_media() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'dfxtgfeed_media_proxy')) {
            status_header(403);
            exit;
        }

        $file_id = isset($_GET['file_id']) ? sanitize_text_field($_GET['file_id']) : '';
        if (empty($file_id)) {
            status_header(400);
            exit;
        }

        $cache = $this->media_cache_paths($file_id);
        if (!$cache) {
            status_header(500);
            exit;
        }

        if (file_exists($cache['data']) && file_exists($cache['meta'])) {
            $meta = json_decode((string) file_get_contents($cache['meta']), true);
            $this->stream_media($cache['data'], $meta['content_type'] ?? 'application/octet-stream', $file_id);
            exit;
        }

        $url = API::instance()->resolve_file_url($file_id);
        if (!$url) {
            status_header(404);
            exit;
        }

        $response = wp_remote_get($url, [
            'timeout'   => 30,
            'sslverify' => true,
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DFX TG Feed: media proxy fetch failed for file_id ' . $file_id);
            }
            status_header(502);
            exit;
        }

        $bytes = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        if (empty($content_type) || stripos($content_type, 'text/html') !== false) {
            // Telegram occasionally returns generic types; infer from URL when needed.
            $content_type = $this->guess_mime_from_url($url);
        }

        // Persist to disk cache
        if (false !== file_put_contents($cache['data'], $bytes)) {
            file_put_contents($cache['meta'], wp_json_encode(['content_type' => $content_type]));
        }

        $this->stream_bytes($bytes, $content_type, $file_id);
        exit;
    }

    private function media_cache_paths($file_id) {
        $upload = wp_upload_dir();
        if (!empty($upload['error'])) {
            return null;
        }
        $dir = trailingslashit($upload['basedir']) . 'dfxtgfeed-cache';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
            // Best-effort hardening: deny direct directory listing.
            @file_put_contents($dir . '/index.html', '');
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            return null;
        }
        $hash = hash('sha256', $file_id);
        return [
            'data' => $dir . '/' . $hash,
            'meta' => $dir . '/' . $hash . '.meta',
        ];
    }

    private function stream_media($path, $content_type, $file_id) {
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: "' . hash('sha256', $file_id) . '"');
        readfile($path);
    }

    private function stream_bytes($bytes, $content_type, $file_id) {
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: "' . hash('sha256', $file_id) . '"');
        echo $bytes;
    }

    private function guess_mime_from_url($url) {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'webm' => 'video/webm',
            'mp4'  => 'video/mp4',
            'tgs'  => 'application/gzip',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    /**
     * One-time DB migration. Idempotent and gated by `dfxtgfeed_db_version`.
     *
     * Two things to clean up on upgrade from v1.0.0:
     *   1. Old prefixes — the pre-rename plugin used `dfx_tg_feed_*` options,
     *      `dfx_tg_message` post type, and `_tg_*` post meta. Migrate to the
     *      WP.org-compliant `dfxtgfeed*` prefix so existing data isn't lost.
     *   2. Token-bearing media URLs — the pre-fix plugin stored Telegram file
     *      URLs (with the bot token embedded) in the media meta. Replace any
     *      remaining ones with the boolean presence flag '1'.
     */
    private function maybe_run_db_migration() {
        $target_version = '3';
        if (get_option('dfxtgfeed_db_version') === $target_version) {
            return;
        }

        global $wpdb;

        // 1a. Rename custom post type rows.
        $wpdb->query(
            "UPDATE {$wpdb->posts} SET post_type = 'dfxtgfeed_message'
             WHERE post_type = 'dfx_tg_message'"
        );

        // 1b. Rename post meta keys (`_tg_*` → `_dfxtgfeed_*`).
        $wpdb->query(
            "UPDATE {$wpdb->postmeta}
             SET meta_key = CONCAT('_dfxtgfeed_', SUBSTRING(meta_key, 5))
             WHERE meta_key LIKE '\\_tg\\_%'"
        );

        // 1c. Rename options. Copy the old value to the new name only if the
        //     new name doesn't already exist, then delete the old.
        $option_renames = [
            'dfx_tg_feed_bot_token'      => 'dfxtgfeed_bot_token',
            'dfx_tg_feed_default_count'  => 'dfxtgfeed_default_count',
            'dfx_tg_feed_channel'        => 'dfxtgfeed_channel',
            'dfx_tg_feed_db_version'     => 'dfxtgfeed_db_version',
        ];
        foreach ($option_renames as $old => $new) {
            $value = get_option($old, null);
            if ($value !== null) {
                if (get_option($new, null) === null) {
                    update_option($new, $value);
                }
                delete_option($old);
            }
        }

        // 2. Scrub any leftover token-bearing URLs in the renamed media meta.
        $wpdb->query(
            "UPDATE {$wpdb->postmeta} SET meta_value = '1'
             WHERE meta_key = '_dfxtgfeed_media'
               AND meta_value LIKE 'https://api.telegram.org/%'"
        );

        update_option('dfxtgfeed_db_version', $target_version);
    }
}