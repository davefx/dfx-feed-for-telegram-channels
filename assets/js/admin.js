/**
 * Admin JavaScript for DFX Telegram Channel Feed
 * Handles hide/unhide message actions
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle hide message action
        $(document).on('click', '.dfx-tg-hide-message', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var postId = $link.data('post-id');
            var nonce = $link.data('nonce');
            
            if (!confirm(dfxTgAdmin.hideConfirm)) {
                return;
            }
            
            // Disable the link during request
            $link.css('opacity', '0.5').css('pointer-events', 'none');
            
            $.ajax({
                url: dfxTgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_tg_hide_message',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to update the UI
                        location.reload();
                    } else {
                        alert(response.data || dfxTgAdmin.error);
                        $link.css('opacity', '1').css('pointer-events', 'auto');
                    }
                },
                error: function() {
                    alert(dfxTgAdmin.error);
                    $link.css('opacity', '1').css('pointer-events', 'auto');
                }
            });
        });
        
        // Handle unhide message action
        $(document).on('click', '.dfx-tg-unhide-message', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var postId = $link.data('post-id');
            var nonce = $link.data('nonce');
            
            // Disable the link during request
            $link.css('opacity', '0.5').css('pointer-events', 'none');
            
            $.ajax({
                url: dfxTgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_tg_unhide_message',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to update the UI
                        location.reload();
                    } else {
                        alert(response.data || dfxTgAdmin.error);
                        $link.css('opacity', '1').css('pointer-events', 'auto');
                    }
                },
                error: function() {
                    alert(dfxTgAdmin.error);
                    $link.css('opacity', '1').css('pointer-events', 'auto');
                }
            });
        });
    });
})(jQuery);
