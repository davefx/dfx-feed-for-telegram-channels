/**
 * DFX Telegram Channel Feed - Sticker Support
 * Initializes Lottie animations for TGS stickers
 */

(function() {
    'use strict';
    
    function initStickers() {
        // Find all TGS sticker containers
        var stickerContainers = document.querySelectorAll('.dfx-tg-sticker-container');
        
        console.log('DFX Telegram Feed: Found', stickerContainers.length, 'sticker containers');
        
        if (!stickerContainers.length) {
            return;
        }
        
        // Check if lottie is available
        if (typeof lottie === 'undefined') {
            console.error('DFX Telegram Feed: Lottie library not loaded');
            return;
        }
        
        console.log('DFX Telegram Feed: Lottie library loaded successfully');
        
        stickerContainers.forEach(function(container, index) {
            var stickerUrl = container.getAttribute('data-sticker-url');
            var fileId = container.getAttribute('data-file-id');
            
            console.log('DFX Telegram Feed: Processing sticker', index, 'URL:', stickerUrl);
            
            if (!stickerUrl) {
                console.warn('DFX Telegram Feed: Sticker container has no URL');
                return;
            }
            
            // Check if already initialized
            if (container.dataset.initialized === 'true') {
                console.log('DFX Telegram Feed: Sticker', index, 'already initialized, skipping');
                return;
            }
            
            // Mark as initialized
            container.dataset.initialized = 'true';
            
            // Use WordPress AJAX proxy to bypass CORS
            var proxyUrl = dfxTgFeedStickers.ajaxUrl + 
                '?action=dfx_tg_proxy_sticker' +
                '&nonce=' + encodeURIComponent(dfxTgFeedStickers.nonce) +
                '&url=' + encodeURIComponent(stickerUrl) +
                '&file_id=' + encodeURIComponent(fileId || '');
            
            // Load the TGS file and initialize Lottie
            console.log('DFX Telegram Feed: Fetching sticker data via proxy:', proxyUrl);
            fetch(proxyUrl)
                .then(function(response) {
                    console.log('DFX Telegram Feed: Fetch response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Failed to load sticker (HTTP ' + response.status + ')');
                    }
                    return response.json();
                })
                .then(function(animationData) {
                    console.log('DFX Telegram Feed: Animation data loaded, initializing Lottie');
                    // Initialize Lottie animation
                    var animation = lottie.loadAnimation({
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
                    console.log('DFX Telegram Feed: Lottie animation initialized for sticker', index);
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
