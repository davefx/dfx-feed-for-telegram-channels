<?php
/**
 * Template for displaying Telegram Channel Feed
 * 
 * Available variables:
 * @var array $messages Array of message objects
 * @var string $channel Channel username/ID
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dfx-tg-feed dfx-tg-feed-layout">
    <?php foreach ($messages as $msg): ?>
        <?php
        // Skip empty messages (no text and no media)
        if (empty($msg['text']) && empty($msg['media'])) {
            continue;
        }
        
        $is_sticker = !empty($msg['sticker']);
        $has_emoji = !empty($msg['emoji']);
        $has_author = !empty($msg['author']);
        ?>
        <article class="dfx-tg-feed-message <?php echo $is_sticker ? 'dfx-tg-feed-sticker' : ''; ?>" data-id="<?php echo esc_attr($msg['id']); ?>">
            <!-- 1. Date (first) -->
            <div class="dfx-tg-feed-meta">
                <time class="dfx-tg-feed-date" datetime="<?php echo esc_attr(date('c', $msg['date'])); ?>">
                    <?php 
                    // Convert UTC timestamp to local timezone
                    $local_timestamp = get_date_from_gmt(date('Y-m-d H:i:s', $msg['date']), 'U');
                    echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $local_timestamp)); 
                    ?>
                </time>
            </div>
            
            <!-- 2. Author (if exists) -->
            <?php if ($has_author): ?>
                <div class="dfx-tg-feed-author">
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
                            echo ' <span class="dfx-tg-feed-username">@' . esc_html($author['username']) . '</span>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- 3. Media/Images (if exists) -->
            <?php if (!empty($msg['media'])): ?>
                <div class="dfx-tg-feed-media <?php echo $is_sticker ? 'dfx-tg-feed-media-sticker' : ''; ?>">
                    <?php 
                    $sticker_type = $msg['sticker_type'] ?? null;
                    if ($is_sticker && $sticker_type === 'tgs'): 
                        // TGS (Lottie) animated sticker
                    ?>
                        <div class="dfx-tg-sticker-container" data-sticker-url="<?php echo esc_url($msg['media']); ?>" data-file-id="<?php echo esc_attr($msg['file_id'] ?? ''); ?>"></div>
                        <?php if ($has_emoji): ?>
                            <span class="dfx-tg-feed-emoji-overlay"><?php echo esc_html($msg['emoji']); ?></span>
                        <?php endif; ?>
                    <?php elseif ($is_sticker && $sticker_type === 'webm'): 
                        // WEBM video sticker
                    ?>
                        <video class="dfx-tg-sticker-video" autoplay loop muted playsinline>
                            <source src="<?php echo esc_url($msg['media']); ?>" type="video/webm">
                            <!-- Fallback to emoji if video doesn't load -->
                            <?php if ($has_emoji): ?>
                                <span class="dfx-tg-feed-emoji-fallback"><?php echo esc_html($msg['emoji']); ?></span>
                            <?php endif; ?>
                        </video>
                        <?php if ($has_emoji): ?>
                            <span class="dfx-tg-feed-emoji-overlay"><?php echo esc_html($msg['emoji']); ?></span>
                        <?php endif; ?>
                    <?php else: 
                        // Static sticker or regular image
                    ?>
                        <img src="<?php echo esc_url($msg['media']); ?>" alt="<?php echo $is_sticker ? esc_attr__('Telegram sticker', 'dfx-tg-feed') : esc_attr__('Telegram message media', 'dfx-tg-feed'); ?>" />
                        <?php if ($has_emoji): ?>
                            <span class="dfx-tg-feed-emoji-overlay"><?php echo esc_html($msg['emoji']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- 4. Text content (last) -->
            <?php if (!empty($msg['text'])): ?>
                <div class="dfx-tg-feed-text">
                    <?php 
                    if (!empty($msg['entities'])) {
                        echo \DFX\TelegramChannelFeed\API::format_text_with_entities($msg['text'], $msg['entities']);
                    } else {
                        echo nl2br(esc_html($msg['text']));
                    }
                    ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
