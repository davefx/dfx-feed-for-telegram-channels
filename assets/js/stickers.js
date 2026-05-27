/**
 * DFX Feed for Telegram Channels - Sticker Support
 * Initializes Lottie animations for TGS stickers.
 *
 * Sticker bytes are fetched through the WordPress AJAX proxy using only the
 * Telegram file_id — the bot token is never exposed to the client.
 */

/* global lottie, dfxfftcStickers, MutationObserver */

( function () {
	'use strict';

	function buildProxyUrl( fileId ) {
		return (
			dfxfftcStickers.ajaxUrl +
			'?action=dfxfftc_proxy_sticker' +
			'&nonce=' +
			encodeURIComponent( dfxfftcStickers.nonce ) +
			'&file_id=' +
			encodeURIComponent( fileId )
		);
	}

	function showEmojiFallback( container ) {
		const emojiOverlay = container.nextElementSibling;
		if (
			emojiOverlay &&
			emojiOverlay.classList.contains( 'dfxfftc-emoji-overlay' )
		) {
			emojiOverlay.style.fontSize = '48px';
			emojiOverlay.style.position = 'static';
		}
	}

	function initStickers() {
		const stickerContainers = document.querySelectorAll(
			'.dfxfftc-sticker-container'
		);
		if ( ! stickerContainers.length ) {
			return;
		}
		if ( typeof lottie === 'undefined' ) {
			return;
		}

		stickerContainers.forEach( function ( container ) {
			if ( container.dataset.dfxTgInitialized === 'true' ) {
				return;
			}
			const fileId = container.getAttribute( 'data-file-id' );
			if ( ! fileId ) {
				return;
			}
			container.dataset.dfxTgInitialized = 'true';

			fetch( buildProxyUrl( fileId ) )
				.then( function ( response ) {
					if ( ! response.ok ) {
						throw new Error( 'HTTP ' + response.status );
					}
					return response.json();
				} )
				.then( function ( animationData ) {
					lottie.loadAnimation( {
						container,
						renderer: 'canvas',
						loop: true,
						autoplay: true,
						animationData,
						rendererSettings: {
							preserveAspectRatio: 'xMidYMid meet',
							clearCanvas: true,
							progressiveLoad: true,
						},
					} );
				} )
				.catch( function () {
					showEmojiFallback( container );
				} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initStickers );
	} else {
		initStickers();
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
							( node.classList.contains(
								'dfxfftc-sticker-container'
							) ||
								node.querySelector(
									'.dfxfftc-sticker-container'
								) )
						) {
							shouldInit = true;
						}
					} );
				}
			} );
			if ( shouldInit ) {
				initStickers();
			}
		} );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	}
} )();
