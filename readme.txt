=== DFX Telegram Channel Feed ===
Contributors: davefx
Tags: telegram, channel, feed, shortcode, block
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display messages from Telegram channels as a block or shortcode. Created by David Marín Carreño.

== Description ==

DFX Telegram Channel Feed allows you to show the message history or most recent posts from one or more Telegram channels. You can use a shortcode or a Block, and the output will include images/media as available.

Messages are cached locally for configurable time to minimize traffic and API usage.

== Installation ==

1. Upload plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin from the 'Plugins' menu in WordPress.
3. Go to Settings &gt; DFX Telegram Feed to configure.

== Usage ==

- Use `[dfxtgfeed_channel_feed channel="yourchannel" count="5"]` to show the N latest messages.
- Use `[dfxtgfeed_channel_browser channel="yourchannel"]` to show the full channel history.

== Changelog ==

= 1.0.0 =
* Initial release

== License ==

GPL v3 or later