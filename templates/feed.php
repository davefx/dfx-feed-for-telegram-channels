<?php
/**
 * Template for displaying Feed for Telegram Channels
 * 
 * Available variables:
 * @var array $messages Array of message objects
 * @var string $channel Channel username/ID
 * @var string $wrapper_class Additional wrapper classes (optional)
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dfxfftc dfxfftc-layout<?php echo !empty($wrapper_class) ? ' ' . esc_attr($wrapper_class) : ''; ?>"><?php foreach ($messages as $msg): ?>
        <?php
        // Skip empty messages (no text and no media)
        if (empty($msg['text']) && empty($msg['media'])) {
            continue;
        }
        
        $is_sticker = !empty($msg['sticker']);
        $has_emoji = !empty($msg['emoji']);
        $has_author = !empty($msg['author']);
        ?>
        <article class="dfxfftc-message <?php echo $is_sticker ? 'dfxfftc-sticker' : ''; ?>" data-id="<?php echo esc_attr($msg['id']); ?>">
            <!-- 1. Date (first) -->
            <div class="dfxfftc-meta">
                <time class="dfxfftc-date" datetime="<?php echo esc_attr(date('c', $msg['date'])); ?>">
                    <?php
                    // Convert UTC timestamp to local timezone
                    $local_timestamp = get_date_from_gmt(date('Y-m-d H:i:s', $msg['date']), 'U');
                    echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $local_timestamp));
                    ?>
                </time>
                <?php if (!empty($msg['edit_date'])): ?>
                    <span class="dfxfftc-edited" title="<?php echo esc_attr(wp_date(get_option('date_format') . ' ' . get_option('time_format'), get_date_from_gmt(date('Y-m-d H:i:s', $msg['edit_date']), 'U'))); ?>"><?php esc_html_e('(edited)', 'dfx-feed-for-telegram-channels'); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 2. Author (if exists) -->
            <?php if ($has_author): ?>
                <div class="dfxfftc-author">
                    <?php
                    $author = $msg['author'];
                    if (isset($author['signature'])) {
                        echo esc_html($author['signature']);
                    } else {
                        $author_name = trim(($author['first_name'] ?? '') . ' ' . ($author['last_name'] ?? ''));
                        if (!empty($author_name)) {
                            echo esc_html($author_name);
                        }
                        if (!empty($author['username'])) {
                            echo ' <span class="dfxfftc-username">@' . esc_html($author['username']) . '</span>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- 3. Media/Images (if exists) -->
            <?php if (!empty($msg['media']) && !empty($msg['file_id'])): ?>
                <?php $media_url = \DFXFFTC\Plugin::media_proxy_url($msg['file_id']); ?>
                <div class="dfxfftc-media <?php echo $is_sticker ? 'dfxfftc-media-sticker' : ''; ?>">
                    <?php
                    $sticker_type = $msg['sticker_type'] ?? null;
                    if ($is_sticker && $sticker_type === 'tgs'):
                        // TGS (Lottie) animated sticker — JS fetches via the sticker proxy using file_id
                    ?>
                        <div class="dfxfftc-sticker-container" data-file-id="<?php echo esc_attr($msg['file_id']); ?>"></div>
                        <?php if ($has_emoji): ?>
                            <span class="dfxfftc-emoji-overlay"><?php echo esc_html($msg['emoji']); ?></span>
                        <?php endif; ?>
                    <?php elseif ($is_sticker && $sticker_type === 'webm'):
                        // WEBM video sticker
                    ?>
                        <video class="dfxfftc-sticker-video" autoplay loop muted playsinline>
                            <source src="<?php echo esc_url($media_url); ?>" type="video/webm">
                            <?php if ($has_emoji): ?>
                                <span class="dfxfftc-emoji-fallback"><?php echo esc_html($msg['emoji']); ?></span>
                            <?php endif; ?>
                        </video>
                        <?php if ($has_emoji): ?>
                            <span class="dfxfftc-emoji-overlay"><?php echo esc_html($msg['emoji']); ?></span>
                        <?php endif; ?>
                    <?php else:
                        // Static sticker or regular image
                    ?>
                        <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo $is_sticker ? esc_attr__('Telegram sticker', 'dfx-feed-for-telegram-channels') : esc_attr__('Telegram message media', 'dfx-feed-for-telegram-channels'); ?>" />
                        <?php if ($has_emoji): ?>
                            <span class="dfxfftc-emoji-overlay"><?php echo esc_html($msg['emoji']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- 4. Text content (last) -->
            <?php if (!empty($msg['text'])): ?>
                <div class="dfxfftc-text">
                    <?php 
                    if (!empty($msg['entities'])) {
                        echo \DFXFFTC\API::format_text_with_entities($msg['text'], $msg['entities']);
                    } else {
                        echo nl2br(esc_html($msg['text']));
                    }
                    ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>