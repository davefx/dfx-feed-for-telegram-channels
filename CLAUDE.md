# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build commands

This is a WordPress plugin that uses `@wordpress/scripts` to compile editor-side block code in `src/` into `build/`. PHP runtime files in `includes/` are not compiled.

- `npm run build` — production build of both blocks (channel-feed, channel-browser).
- `npm run start` — watch mode for block development.
- `npm run lint:js` / `npm run format:js` — wp-scripts ESLint/Prettier wrappers.

There is no PHP test suite, no PHP linter config, and no CI. The two webpack entries are defined explicitly in `webpack.config.js` (the default wp-scripts config can't auto-discover both top-level src directories).

## Architecture

### Three rendering surfaces, one core

The plugin exposes the same two views — a recent-messages **feed** and a full-history **browser** — through three different WordPress integration points:

1. **Shortcodes** (`includes/class-shortcodes.php`): `[dfxtgfeed_channel_feed]` and `[dfxtgfeed_channel_browser]`. This class is the canonical renderer — it loads the templates in `templates/feed.php` and `templates/browser.php`.
2. **Gutenberg blocks** (`includes/class-blocks.php` + `src/channel-{feed,browser}/`): The block `render_callback` ultimately calls into `Shortcodes::shortcode_channel_*`. Blocks add per-instance inline styles via the `dfxtgfeed_wrapper_class` filter (a one-shot filter pattern that injects a generated `dfxtgfeed-block-N` class onto the next render only).
3. **Elementor widgets** (`includes/elementor/widget-channel-*.php`): Same idea — widgets delegate to the shortcode handlers.

When changing rendering output, the templates and `Shortcodes` class are the source of truth; blocks and Elementor widgets just add styling wrappers around them.

### Data flow: Telegram → WP custom post type → frontend

Messages are not fetched on every page load. The architecture is:

1. **`API::fetch_channel_messages()`** calls Telegram's `getUpdates` Bot API. Critical limitation: **`getUpdates` only returns messages received since the bot joined the channel and only retains them briefly** — that's why local persistence exists and why "no messages found" is a common first-run state.
2. **`PostType::store_message()`** persists each message as a `dfxtgfeed_message` custom post type, with Telegram fields stored as post meta (`_dfxtgfeed_channel`, `_dfxtgfeed_message_id`, `_dfxtgfeed_date`, `_dfxtgfeed_media`, `_dfxtgfeed_entities`, `_dfxtgfeed_author`, `_dfxtgfeed_is_sticker`, `_dfxtgfeed_sticker_type`, `_dfxtgfeed_emoji`, `_dfxtgfeed_file_id`, `_dfxtgfeed_hidden`). `_dfxtgfeed_media` is a presence flag (`'1'`); the actual media URL is built at render time via `Plugin::media_proxy_url($file_id)` and never persisted (the bot token would otherwise leak — see "Media proxy" below).
3. **Two refresh paths**, both routing through `PostType::refresh_messages` and sharing the `dfxtgfeed_refreshing_{channel}` lock so concurrent runs collapse to one:
   - **WP-cron background task** (`Plugin::cron_refresh_callback`) on a custom 2-minute schedule (`Plugin::CRON_SCHEDULE`). Walks every channel discovered via `SELECT DISTINCT meta_value FROM wp_postmeta WHERE meta_key = '_dfxtgfeed_channel'` and refreshes each. Self-heals via `wp_schedule_event` in `init`. Cleared on plugin deactivation. Caveat: WP-cron only fires when traffic hits the site; on low-traffic installs configure a real system cron pinging `wp-cron.php`.
   - **Smart on-demand refresh** (`Shortcodes::shortcode_channel_feed`): on each frontend render, if the `dfxtgfeed_last_sync_{channel}` transient is older than 10 minutes, triggers a refresh inline. Acts as a fallback for low-traffic sites where WP-cron may not fire.
4. **`PostType::refresh_messages()`** also detects deletions: it compares stored message IDs (>= the oldest just-fetched ID) against the fetched set, and `wp_trash_post()`s anything missing. Don't widen this comparison window — older messages are simply outside our retrieval reach, not deleted.
5. **`PostType::get_messages()`** is what the frontend reads. It orders by `_dfxtgfeed_message_id` numeric DESC and excludes posts where `_dfxtgfeed_hidden = '1'`.

### Date preservation invariant

`PostType::preserve_post_dates()` is hooked into `wp_insert_post_data` and **forcibly overwrites `post_date` / `post_date_gmt` from `_dfxtgfeed_date` on every update** of a `dfxtgfeed_message`. This is intentional: WordPress would otherwise re-stamp `post_date` when a post transitions out of draft, but the post date for these messages must always reflect the Telegram timestamp. If you change how messages are stored or migrated, keep this invariant or downstream sorting/display will silently drift.

### Telegram entity formatting (UTF-16 offsets)

`API::format_text_with_entities()` converts Telegram's rich-text entities to HTML. Telegram entity offsets are in **UTF-16 code units**, not bytes or characters, so the function converts text to UTF-16LE before walking it. Touch this function with care — emoji and surrogate pairs are the failure mode.

### Media proxy (do NOT bypass)

Telegram file URLs embed the bot token (`https://api.telegram.org/file/bot<TOKEN>/<path>`). Those URLs must **never** be stored in post meta or rendered into HTML/JS — that would leak the token to anyone viewing page source. The plugin enforces this via two ajax handlers in `class-plugin.php`:

- **`Plugin::ajax_proxy_sticker`** (action `dfxtgfeed_proxy_sticker`, nopriv): for TGS Lottie JSON. Resolves the URL server-side from `file_id`, fetches and gzdecodes, caches the parsed JSON for 24h via transient. The frontend (`assets/js/stickers.js`) only ever knows the file_id.
- **`Plugin::ajax_proxy_media`** (action `dfxtgfeed_proxy_media`, nopriv): for everything else (photos, video/animation thumbnails, static and webm stickers). Streams bytes from a disk cache at `wp-content/uploads/dfxtgfeed-cache/`; on miss, fetches from Telegram and persists. Sets `Cache-Control: public, max-age=31536000, immutable` since file_id is content-addressed.

`API::resolve_file_url($file_id)` is the only place that constructs the token-bearing URL and is documented as server-side only. If you add a new media render path, route it through `Plugin::media_proxy_url($file_id)`, never `API::resolve_file_url` directly.

### Admin UX integration

The plugin re-parents the `dfxtgfeed_message` post type under a custom top-level "Telegram Messages" menu (`Plugin::MENU_SLUG = 'dfxtgfeed-messages'`) using `remove_menu_page` + `parent_file`/`submenu_file` filters. Creating new posts is disabled (`'create_posts' => 'do_not_allow'`); messages only appear via Telegram sync. Admin row actions add "View in Telegram" (deep-link to `t.me/<channel>/<message_id>`) and a Hide/Unhide toggle (sets `_dfxtgfeed_hidden` meta — frontend respects this; admins still see the row).

### Theme template overrides

`Shortcodes::load_template()` first calls `locate_template('dfxtgfeed/<name>.php')` before falling back to the bundled `templates/<name>.php`. Themes can drop overrides into `wp-content/themes/<theme>/dfxtgfeed/feed.php` or `browser.php`.

## Conventions worth knowing

- **Singleton everywhere.** Every class in `includes/` exposes `instance()`. Reuse the singleton; don't `new` these.
- **Namespace.** All PHP code lives in the `DFXTgFeed\` namespace (or `DFXTgFeed\Elementor\` for the two widget classes).
- **Autoload.** `Plugin::init()` registers a PSR-4-ish autoloader that maps `DFXTgFeed\Foo` to `includes/class-foo.php` (lowercased, namespace-stripped). The `Plugin` class itself is `require_once`'d explicitly from the main plugin file because the autoloader can't load itself. Subnamespaces use `-` joins (e.g. `Elementor\Widget_Channel_Feed` → `includes/elementor/widget-channel-feed.php` is required manually because the autoloader's flat mapping doesn't cover subnamespaces).
- **Prefixes (for WP.org compliance).** All public identifiers use the `dfxtgfeed`/`DFXTGFEED`/`DFXTgFeed` prefix consistently — options, hooks, post type, post meta keys, transient keys, action names, nonces, CSS classes, JS object names, and shortcodes. The pre-1.0.0 prefixes (`dfx_tg_feed`, `dfx-tg-`, `_tg_`, `DFX\TelegramChannelFeed`) were renamed; `Plugin::maybe_run_db_migration` (gated on `dfxtgfeed_db_version`) handles in-place rename of stored post type, options, and meta keys for existing installs.
- **Activation hook.** `register_activation_hook` flushes rewrite rules; if you add custom rewrite rules, make sure they're registered before the flush.
- **`WP_DEBUG` gates `error_log` calls** in `API` — keep new debug output behind the same guard.
