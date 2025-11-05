<?php
/**
 * Template for displaying Telegram Channel Browser
 * 
 * Available variables:
 * @var array $messages Array of message objects
 * @var string $channel Channel username/ID
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dfx-tg-feed-browser dfx-tg-feed-layout">
    <?php foreach ($messages as $msg): ?>
        <article class="dfx-tg-feed-message dfx-tg-feed-message-browser" data-id="<?php echo esc_attr($msg['id']); ?>">
            <?php if (!empty($msg['media'])): ?>
                <div class="dfx-tg-feed-media dfx-tg-feed-media-browser">
                    <img src="<?php echo esc_url($msg['media']); ?>" alt="<?php esc_attr_e('Telegram message media', 'dfx-tg-feed'); ?>" />
                </div>
            <?php endif; ?>
            
            <div class="dfx-tg-feed-content">
                <div class="dfx-tg-feed-meta">
                    <time class="dfx-tg-feed-date" datetime="<?php echo esc_attr(date('c', $msg['date'])); ?>">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $msg['date'])); ?>
                    </time>
                    <span class="dfx-tg-feed-id">#<?php echo esc_html($msg['id']); ?></span>
                </div>
                
                <?php if (!empty($msg['text'])): ?>
                    <div class="dfx-tg-feed-text">
                        <?php echo nl2br(esc_html($msg['text'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
