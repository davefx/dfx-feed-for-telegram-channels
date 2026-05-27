=== DFX Feed for Telegram Channels ===
Contributors: davefx
Tags: telegram, channel, feed, shortcode, block
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display messages from Telegram channels on your WordPress site, with full media support, via shortcode, Gutenberg block, or Elementor widget.

== Description ==

DFX Feed for Telegram Channels mirrors messages from one or more Telegram channels onto your WordPress site. Messages are fetched via a Telegram bot you control, stored locally as a custom post type, and rendered through a shortcode, Gutenberg block, or Elementor widget. Both public and private channels are supported.

= Features =

* **Three rendering surfaces** — shortcode, Gutenberg block, or Elementor widget. The block exposes per-instance styling controls (border, background, padding, typography per element).
* **Full media support** — photos at the highest resolution available, video and animation thumbnails, static stickers, animated TGS stickers via Lottie, and WEBM video stickers.
* **Bot token never reaches the client** — Telegram file URLs (which embed your bot token) are resolved server-side; the frontend only receives a token-less proxy URL. Media is cached on disk for performance.
* **Edits sync automatically** with a small "(edited)" marker carrying the edit timestamp.
* **Background refresh every 2 minutes** via WP-cron, plus on-demand refresh on frontend visits as a fallback for low-traffic sites.
* **Per-message admin controls** — Hide (reversible, suppresses on frontend) and Move to Trash (sticky against refresh) row actions.
* **Theme template overrides** — drop `dfxfftc/feed.php` or `dfxfftc/browser.php` into your theme to fully customize the output.
* **Works with private channels** if your bot is admin in them.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu.
3. In WordPress admin, go to **Settings → Telegram Feed**.
4. Create a Telegram bot via [@BotFather](https://t.me/botfather), paste the bot token into the settings page, and save.
5. Add the bot as an **administrator** of every channel you want to mirror.
6. Add a shortcode such as `[dfxfftc_channel_feed channel="@yourchannel" count="10"]` to a page, or use the "Feed for Telegram Channels" Gutenberg block / Elementor widget.

Note: messages posted in the channel **before** the bot was added are not retrievable via the Bot API.

== Usage ==

- Use `[dfxfftc_channel_feed channel="yourchannel" count="5"]` to show the N latest messages.
- Use `[dfxfftc_channel_browser channel="yourchannel"]` to show the full channel history.

== Behaviour and known limitations ==

* **Bot-only access.** Messages are fetched via the Telegram Bot API, which only sees posts that arrived in the channel after the bot was added. There is no access to historical messages from before that point.
* **Background refresh every 2 minutes.** The plugin schedules a WP-cron task that pulls fresh updates from Telegram for every channel you have stored data for. This keeps the local feed within ~2 minutes of the live channel without any manual action. Note that WP-cron only fires when traffic hits your site — on low-traffic installations you may want to disable WP-cron and trigger `wp-cron.php` from a real system cron job for reliable execution. The on-demand refresh on frontend visits is also kept as a fallback.
* **Edits sync automatically.** When a message is edited in Telegram, the local copy is updated on the next refresh and a small "(edited)" marker is shown next to the date.
* **Deletions are not syncable automatically.** The Telegram Bot API does not notify bots about channel message deletions — there is no `deleted_channel_post` update type. To remove a message that has been deleted in Telegram from the local feed, use the per-message **"Move to Trash"** admin row action. Trashed messages are sticky: refresh will not bring them back.
* **Hide vs. Trash.** Use **Hide** to keep a message in the database but suppress it on the frontend (reversible via "Unhide"). Use **Move to Trash** for a permanent local removal.

== External services ==

This plugin connects to the **Telegram Bot API** to fetch channel messages, resolve media file URLs, and verify bot credentials.

= What data is sent =

* **Bot token** (configured in Settings) — sent with every API call to authenticate your bot.
* **Channel username or ID** — sent when fetching messages via `getUpdates` and when testing the bot configuration via `getChatMember`.
* **File IDs** — sent when resolving media URLs via `getFile` so the plugin can proxy photos, stickers, and video thumbnails to the frontend without exposing the bot token.

= When data is sent =

* On every WP-cron background refresh cycle (every ~2 minutes when site traffic is present).
* On frontend page loads when the on-demand refresh fallback fires (at most once per 10 minutes per channel).
* When an admin clicks "Refresh Messages" in the admin list page.
* When an admin clicks "Test Connection" on the Settings page.
* When a visitor views a page containing a message with media (the media proxy fetches the file from Telegram on first access and caches it locally).

= Service provider =

Telegram Bot API, provided by Telegram FZ-LLC.

* Terms of Service: https://telegram.org/tos
* Privacy Policy: https://telegram.org/privacy
* Bot API documentation: https://core.telegram.org/bots/api

== Changelog ==

= 2.0.1 =
* Sanitize nonce inputs with sanitize_text_field(wp_unslash()) before wp_verify_nonce().
* Escape _n() translation output in bulk action admin notices.
* Rename dfxTgAdmin JS global to dfxfftcAdmin for prefix consistency.

= 2.0.0 =
* Renamed plugin from "DFX Telegram Channel Feed" to "DFX Feed for Telegram Channels" to comply with WordPress.org trademark naming guidelines.
* Changed plugin slug to `dfx-feed-for-telegram-channels` and internal prefix from `dfxtgfeed` to `dfxfftc`.
* Text domain updated to `dfx-feed-for-telegram-channels`.
* Replaced all `_e()` with `esc_html_e()` and wrapped unescaped `__()` outputs in `esc_html()` for proper output escaping.
* Moved inline `<script>` and `<style>` blocks to use `wp_add_inline_script` and `wp_add_inline_style` per WordPress enqueue best practices.
* Bundled Lottie library locally instead of loading from cdnjs.cloudflare.com.
* Documented Telegram Bot API usage in the "External services" readme section.
* Lowered admin menu position to avoid colliding with core WordPress menu items.
* Made GitHub repository public so the Plugin URI resolves correctly.
* Added DB migration path from `dfxtgfeed_*` identifiers to `dfxfftc_*` for existing installs.

= 1.0.2 =
* Fixed Plugin Check warnings: text domain now matches the plugin slug, and the unused `Domain Path` header was removed.

= 1.0.1 =
* Removed the experimental "Sync Deletions" feature (which scraped the public `t.me/s/<channel>` preview) ahead of WordPress.org submission.

= 1.0.0 =
* Internal release (not published to WordPress.org).

== License ==

GPL v3 or later
