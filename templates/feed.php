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
        <article class="dfx-tg-feed-message" data-id="<?php echo esc_attr($msg['id']); ?>">
            <?php if (!empty($msg['media'])): ?>
                <div class="dfx-tg-feed-media">
                    <img src="<?php echo esc_url($msg['media']); ?>" alt="<?php esc_attr_e('Telegram message media', 'dfx-tg-feed'); ?>" />
                </div>
            <?php endif; ?>
            
            <div class="dfx-tg-feed-content">
                <div class="dfx-tg-feed-meta">
                    <time class="dfx-tg-feed-date" datetime="<?php echo esc_attr(date('c', $msg['date'])); ?>">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $msg['date'])); ?>
                    </time>
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
