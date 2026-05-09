=== DFX Telegram Channel Feed ===
Contributors: davefx
Tags: telegram, channel, feed, shortcode, block
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display messages from Telegram channels on your WordPress site, with full media support, via shortcode, Gutenberg block, or Elementor widget.

== Description ==

DFX Telegram Channel Feed mirrors messages from one or more Telegram channels onto your WordPress site. Messages are fetched via a Telegram bot you control, stored locally as a custom post type, and rendered through a shortcode, Gutenberg block, or Elementor widget. Both public and private channels are supported.

= Features =

* **Three rendering surfaces** — shortcode, Gutenberg block, or Elementor widget. The block exposes per-instance styling controls (border, background, padding, typography per element).
* **Full media support** — photos at the highest resolution available, video and animation thumbnails, static stickers, animated TGS stickers via Lottie, and WEBM video stickers.
* **Bot token never reaches the client** — Telegram file URLs (which embed your bot token) are resolved server-side; the frontend only receives a token-less proxy URL. Media is cached on disk for performance.
* **Edits sync automatically** with a small "(edited)" marker carrying the edit timestamp.
* **Background refresh every 2 minutes** via WP-cron, plus on-demand refresh on frontend visits as a fallback for low-traffic sites.
* **Per-message admin controls** — Hide (reversible, suppresses on frontend) and Move to Trash (sticky against refresh) row actions.
* **Sync deletions** for public channels via an opt-in admin button that reconciles against the public channel preview.
* **Theme template overrides** — drop `dfxtgfeed/feed.php` or `dfxtgfeed/browser.php` into your theme to fully customize the output.
* **Works with private channels** if your bot is admin in them.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu.
3. In WordPress admin, go to **Telegram Messages → Settings**.
4. Create a Telegram bot via [@BotFather](https://t.me/botfather), paste the bot token into the settings page, and save.
5. Add the bot as an **administrator** of every channel you want to mirror.
6. Add a shortcode such as `[dfxtgfeed_channel_feed channel="@yourchannel" count="10"]` to a page, or use the "Telegram Channel Feed" Gutenberg block / Elementor widget.

Note: messages posted in the channel **before** the bot was added are not retrievable via the Bot API.

== Usage ==

- Use `[dfxtgfeed_channel_feed channel="yourchannel" count="5"]` to show the N latest messages.
- Use `[dfxtgfeed_channel_browser channel="yourchannel"]` to show the full channel history.

== Behaviour and known limitations ==

* **Bot-only access.** Messages are fetched via the Telegram Bot API, which only sees posts that arrived in the channel after the bot was added. There is no access to historical messages from before that point.
* **Background refresh every 2 minutes.** The plugin schedules a WP-cron task that pulls fresh updates from Telegram for every channel you have stored data for. This keeps the local feed within ~2 minutes of the live channel without any manual action. Note that WP-cron only fires when traffic hits your site — on low-traffic installations you may want to disable WP-cron and trigger `wp-cron.php` from a real system cron job for reliable execution. The on-demand refresh on frontend visits is also kept as a fallback.
* **Edits sync automatically.** When a message is edited in Telegram, the local copy is updated on the next refresh and a small "(edited)" marker is shown next to the date.
* **Deletions are not syncable automatically.** The Telegram Bot API does not notify bots about channel message deletions — there is no `deleted_channel_post` update type. There are two ways to remove a message that has been deleted in Telegram from the local feed:
  * **Per-message "Move to Trash"** in the admin row actions. Trashed messages are sticky: refresh will not bring them back.
  * **"Sync Deletions" button** (public channels only). Located next to "Refresh Messages" in the admin list, this scrapes the public channel preview at `t.me/s/<channel>`, compares against locally stored messages within the preview's visible window, and offers to trash any that no longer appear. You confirm before anything is moved to Trash. Private channels have no public preview, so this option errors out for them.
* **Hide vs. Trash.** Use **Hide** to keep a message in the database but suppress it on the frontend (reversible via "Unhide"). Use **Move to Trash** for a permanent local removal.

== Changelog ==

= 1.0.0 =
* Initial release

== License ==

GPL v3 or later