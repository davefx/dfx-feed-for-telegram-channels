/**
 * DFX Telegram Channel Feed - Image Lightbox
 * Provides full-size image viewing with zoom and pan capabilities
 */

/* global MutationObserver */

( function () {
	'use strict';

	// Configuration constants
	const MAX_ZOOM_LEVEL = 5;
	const MIN_ZOOM_LEVEL = 1;
	const ZOOM_INCREMENT = 0.5;

	// Lightbox state
	let lightboxOpen = false;
	let zoomLevel = 1;
	let isDragging = false;
	let startX = 0;
	let startY = 0;
	let translateX = 0;
	let translateY = 0;

	// Lightbox container element
	let lightbox = null;
	let lightboxImg = null;

	/**
	 * Create the lightbox HTML structure
	 */
	function createLightbox() {
		if ( lightbox ) {
			return;
		}

		// Create lightbox container
		lightbox = document.createElement( 'div' );
		lightbox.className = 'dfxtgfeed-lightbox';
		lightbox.innerHTML = `
            <div class="dfxtgfeed-lightbox-backdrop"></div>
            <div class="dfxtgfeed-lightbox-content">
                <img class="dfxtgfeed-lightbox-image" src="" alt="">
                <div class="dfxtgfeed-lightbox-controls">
                    <button class="dfxtgfeed-lightbox-btn dfxtgfeed-lightbox-zoom-in" title="Zoom In">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="11" y1="8" x2="11" y2="14"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                    <button class="dfxtgfeed-lightbox-btn dfxtgfeed-lightbox-zoom-out" title="Zoom Out">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                    <button class="dfxtgfeed-lightbox-btn dfxtgfeed-lightbox-reset" title="Reset">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                            <path d="M21 3v5h-5"></path>
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                            <path d="M3 21v-5h5"></path>
                        </svg>
                    </button>
                    <button class="dfxtgfeed-lightbox-btn dfxtgfeed-lightbox-close" title="Close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        `;

		document.body.appendChild( lightbox );

		// Get references to elements
		lightboxImg = lightbox.querySelector( '.dfxtgfeed-lightbox-image' );

		// Attach event listeners
		attachLightboxListeners();
	}

	/**
	 * Attach event listeners to lightbox elements
	 */
	function attachLightboxListeners() {
		// Close on backdrop click
		lightbox
			.querySelector( '.dfxtgfeed-lightbox-backdrop' )
			.addEventListener( 'click', closeLightbox );

		// Close button
		lightbox
			.querySelector( '.dfxtgfeed-lightbox-close' )
			.addEventListener( 'click', closeLightbox );

		// Zoom in button
		lightbox
			.querySelector( '.dfxtgfeed-lightbox-zoom-in' )
			.addEventListener( 'click', function () {
				zoomIn();
			} );

		// Zoom out button
		lightbox
			.querySelector( '.dfxtgfeed-lightbox-zoom-out' )
			.addEventListener( 'click', function () {
				zoomOut();
			} );

		// Reset button
		lightbox
			.querySelector( '.dfxtgfeed-lightbox-reset' )
			.addEventListener( 'click', function () {
				resetZoom();
			} );

		// Mouse wheel for zoom
		lightboxImg.addEventListener( 'wheel', function ( e ) {
			e.preventDefault();
			if ( e.deltaY < 0 ) {
				zoomIn();
			} else {
				zoomOut();
			}
		} );

		// Drag to pan (when zoomed)
		lightboxImg.addEventListener( 'mousedown', startDrag );
		document.addEventListener( 'mousemove', drag );
		document.addEventListener( 'mouseup', stopDrag );

		// Touch support for pan
		lightboxImg.addEventListener( 'touchstart', handleTouchStart, {
			passive: false,
		} );
		lightboxImg.addEventListener( 'touchmove', handleTouchMove, {
			passive: false,
		} );
		lightboxImg.addEventListener( 'touchend', stopDrag );

		// Keyboard support
		document.addEventListener( 'keydown', handleKeyPress );
	}

	/**
	 * Open lightbox with specified image
	 *
	 * @param {string} imgSrc - The image URL to display
	 * @param {string} imgAlt - The alt text for the image
	 */
	function openLightbox( imgSrc, imgAlt ) {
		createLightbox();

		lightboxImg.src = imgSrc;
		lightboxImg.alt = imgAlt || 'Lightbox image';
		lightbox.classList.add( 'dfxtgfeed-lightbox-active' );
		lightboxOpen = true;

		// Reset zoom and position
		resetZoom();

		// Prevent body scrolling
		document.body.style.overflow = 'hidden';
	}

	/**
	 * Close lightbox
	 */
	function closeLightbox() {
		if ( ! lightbox ) {
			return;
		}

		lightbox.classList.remove( 'dfxtgfeed-lightbox-active' );
		lightboxOpen = false;

		// Re-enable body scrolling
		document.body.style.overflow = '';
	}

	/**
	 * Zoom in
	 */
	function zoomIn() {
		zoomLevel = Math.min( zoomLevel + ZOOM_INCREMENT, MAX_ZOOM_LEVEL );
		updateImageTransform();
	}

	/**
	 * Zoom out
	 */
	function zoomOut() {
		zoomLevel = Math.max( zoomLevel - ZOOM_INCREMENT, MIN_ZOOM_LEVEL );
		if ( zoomLevel === MIN_ZOOM_LEVEL ) {
			translateX = 0;
			translateY = 0;
		}
		updateImageTransform();
	}

	/**
	 * Reset zoom and position
	 */
	function resetZoom() {
		zoomLevel = 1;
		translateX = 0;
		translateY = 0;
		updateImageTransform();
	}

	/**
	 * Update image transform
	 */
	function updateImageTransform() {
		lightboxImg.style.transform = `translate(${ translateX }px, ${ translateY }px) scale(${ zoomLevel })`;

		// Update cursor
		if ( zoomLevel > 1 ) {
			lightboxImg.style.cursor = isDragging ? 'grabbing' : 'grab';
		} else {
			lightboxImg.style.cursor = 'default';
		}
	}

	/**
	 * Start dragging
	 *
	 * @param {MouseEvent} e - Mouse event
	 */
	function startDrag( e ) {
		if ( zoomLevel <= 1 ) {
			return;
		}

		e.preventDefault();
		isDragging = true;
		startX = e.clientX - translateX;
		startY = e.clientY - translateY;
		lightboxImg.style.cursor = 'grabbing';
	}

	/**
	 * Drag
	 *
	 * @param {MouseEvent} e - Mouse event
	 */
	function drag( e ) {
		if ( ! isDragging ) {
			return;
		}

		e.preventDefault();
		translateX = e.clientX - startX;
		translateY = e.clientY - startY;
		updateImageTransform();
	}

	/**
	 * Stop dragging
	 */
	function stopDrag() {
		isDragging = false;
		if ( zoomLevel > 1 ) {
			lightboxImg.style.cursor = 'grab';
		} else {
			lightboxImg.style.cursor = 'default';
		}
	}

	/**
	 * Handle touch start
	 *
	 * @param {TouchEvent} e - Touch event
	 */
	function handleTouchStart( e ) {
		if ( zoomLevel <= 1 ) {
			return;
		}

		e.preventDefault();
		const touch = e.touches[ 0 ];
		isDragging = true;
		startX = touch.clientX - translateX;
		startY = touch.clientY - translateY;
	}

	/**
	 * Handle touch move
	 *
	 * @param {TouchEvent} e - Touch event
	 */
	function handleTouchMove( e ) {
		if ( ! isDragging ) {
			return;
		}

		e.preventDefault();
		const touch = e.touches[ 0 ];
		translateX = touch.clientX - startX;
		translateY = touch.clientY - startY;
		updateImageTransform();
	}

	/**
	 * Handle keyboard events
	 *
	 * @param {KeyboardEvent} e - Keyboard event
	 */
	function handleKeyPress( e ) {
		if ( ! lightboxOpen ) {
			return;
		}

		switch ( e.key ) {
			case 'Escape':
				closeLightbox();
				break;
			case '+':
			case '=':
				zoomIn();
				break;
			case '-':
			case '_':
				zoomOut();
				break;
			case '0':
				resetZoom();
				break;
		}
	}

	/**
	 * Check if image is displayed smaller than its natural size
	 *
	 * @param {HTMLImageElement} img - The image element to check
	 * @return {boolean} True if image is constrained, false otherwise
	 */
	function isImageConstrained( img ) {
		// Wait for image to load if not yet loaded
		if ( ! img.complete ) {
			return false;
		}

		// Get natural (actual) dimensions
		const naturalWidth = img.naturalWidth;
		const naturalHeight = img.naturalHeight;

		// Get displayed dimensions
		const displayedWidth = img.offsetWidth;
		const displayedHeight = img.offsetHeight;

		// Image is constrained if displayed size is smaller than natural size
		// We add a small tolerance (5px) to account for rounding
		return (
			naturalWidth > displayedWidth + 5 ||
			naturalHeight > displayedHeight + 5
		);
	}

	/**
	 * Initialize lightbox for all images
	 */
	function initLightbox() {
		// Find all non-sticker images in feed and browser
		const images = document.querySelectorAll(
			'.dfxtgfeed-media:not(.dfxtgfeed-media-sticker) img'
		);

		images.forEach( function ( img ) {
			// Skip if already initialized
			if ( img.dataset.dfxTgLightboxInitialized === 'true' ) {
				return;
			}

			// Mark as initialized
			img.dataset.dfxTgLightboxInitialized = 'true';

			// Function to check and enable lightbox if needed
			const checkAndEnableLightbox = function () {
				if ( isImageConstrained( img ) ) {
					// Make clickable only if image is constrained
					img.style.cursor = 'pointer';

					// Add click handler (only once)
					if ( ! img.dataset.dfxTgLightboxClickAdded ) {
						img.dataset.dfxTgLightboxClickAdded = 'true';
						img.addEventListener( 'click', function ( e ) {
							e.preventDefault();
							openLightbox( img.src, img.alt );
						} );
					}
				} else {
					// Remove pointer cursor if not constrained
					img.style.cursor = 'default';
				}
			};

			// Check when image loads
			if ( img.complete ) {
				checkAndEnableLightbox();
			} else {
				img.addEventListener( 'load', checkAndEnableLightbox );
			}
		} );
	}

	// Initialize when DOM is ready
	if (
		document.readyState !== 'complete' &&
		document.readyState !== 'interactive'
	) {
		document.addEventListener( 'DOMContentLoaded', initLightbox );
	} else {
		initLightbox();
	}

	// Re-initialize on dynamic content load (for AJAX-loaded content)
	if ( typeof MutationObserver !== 'undefined' ) {
		const observer = new MutationObserver( function ( mutations ) {
			let shouldInit = false;
			mutations.forEach( function ( mutation ) {
				if ( mutation.addedNodes.length ) {
					mutation.addedNodes.forEach( function ( node ) {
						if (
							node.nodeType === 1 &&
							( node.classList.contains( 'dfxtgfeed-media' ) ||
								node.querySelector( '.dfxtgfeed-media' ) )
						) {
							shouldInit = true;
						}
					} );
				}
			} );
			if ( shouldInit ) {
				initLightbox();
			}
		} );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	}
} )();
