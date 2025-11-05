/**
 * DFX Telegram Channel Feed - Sticker Support
 * Initializes Lottie animations for TGS stickers
 */

(function() {
    'use strict';
    
    function initStickers() {
        // Find all TGS sticker containers
        var stickerContainers = document.querySelectorAll('.dfx-tg-sticker-container');
        
        if (!stickerContainers.length) {
            return;
        }
        
        // Check if lottie is available
        if (typeof lottie === 'undefined') {
            console.error('DFX Telegram Feed: Lottie library not loaded');
            return;
        }
        
        stickerContainers.forEach(function(container) {
            var stickerUrl = container.getAttribute('data-sticker-url');
            
            if (!stickerUrl) {
                return;
            }
            
            // Load the TGS file and initialize Lottie
            fetch(stickerUrl)
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Failed to load sticker');
                    }
                    return response.json();
                })
                .then(function(animationData) {
                    // Initialize Lottie animation
                    lottie.loadAnimation({
                        container: container,
                        renderer: 'canvas',
                        loop: true,
                        autoplay: true,
                        animationData: animationData,
                        rendererSettings: {
                            preserveAspectRatio: 'xMidYMid meet',
                            clearCanvas: true,
                            progressiveLoad: true
                        }
                    });
                })
                .catch(function(error) {
                    console.error('DFX Telegram Feed: Error loading TGS sticker:', error);
                    // Show emoji overlay as fallback
                    var emojiOverlay = container.nextElementSibling;
                    if (emojiOverlay && emojiOverlay.classList.contains('dfx-tg-feed-emoji-overlay')) {
                        emojiOverlay.style.fontSize = '48px';
                        emojiOverlay.style.position = 'static';
                    }
                });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStickers);
    } else {
        initStickers();
    }
    
    // Re-initialize on dynamic content load (for AJAX-loaded content)
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldInit = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (
                            node.classList.contains('dfx-tg-sticker-container') ||
                            node.querySelector('.dfx-tg-sticker-container')
                        )) {
                            shouldInit = true;
                        }
                    });
                }
            });
            if (shouldInit) {
                initStickers();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();
