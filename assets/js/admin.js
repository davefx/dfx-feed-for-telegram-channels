/**
 * Admin JavaScript for DFX Telegram Channel Feed
 * Handles hide/unhide message actions
 *
 * @param {Object} $ jQuery object
 */
/* global jQuery, dfxfftcAdmin, location, confirm, alert */
( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		// Handle hide message action
		$( document ).on( 'click', '.dfxfftc-hide-message', function ( e ) {
			e.preventDefault();

			// eslint-disable-next-line @wordpress/no-unused-vars-before-return
			const $link = $( this );

			// eslint-disable-next-line no-alert
			if ( ! confirm( dfxfftcAdmin.hideConfirm ) ) {
				return;
			}

			const postId = $link.data( 'post-id' );
			const nonce = $link.data( 'nonce' );

			// Disable the link during request
			$link.css( 'opacity', '0.5' ).css( 'pointer-events', 'none' );

			$.ajax( {
				url: dfxfftcAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dfxfftc_hide_message',
					post_id: postId,
					nonce,
				},
				success( response ) {
					if ( response.success ) {
						// Reload the page to update the UI
						location.reload();
					} else {
						// eslint-disable-next-line no-alert
						alert( response.data || dfxfftcAdmin.error );
						$link
							.css( 'opacity', '1' )
							.css( 'pointer-events', 'auto' );
					}
				},
				error() {
					// eslint-disable-next-line no-alert
					alert( dfxfftcAdmin.error );
					$link.css( 'opacity', '1' ).css( 'pointer-events', 'auto' );
				},
			} );
		} );

		// Handle unhide message action
		$( document ).on( 'click', '.dfxfftc-unhide-message', function ( e ) {
			e.preventDefault();

			const $link = $( this );
			const postId = $link.data( 'post-id' );
			const nonce = $link.data( 'nonce' );

			// Disable the link during request
			$link.css( 'opacity', '0.5' ).css( 'pointer-events', 'none' );

			$.ajax( {
				url: dfxfftcAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dfxfftc_unhide_message',
					post_id: postId,
					nonce,
				},
				success( response ) {
					if ( response.success ) {
						// Reload the page to update the UI
						location.reload();
					} else {
						// eslint-disable-next-line no-alert
						alert( response.data || dfxfftcAdmin.error );
						$link
							.css( 'opacity', '1' )
							.css( 'pointer-events', 'auto' );
					}
				},
				error() {
					// eslint-disable-next-line no-alert
					alert( dfxfftcAdmin.error );
					$link.css( 'opacity', '1' ).css( 'pointer-events', 'auto' );
				},
			} );
		} );
	} );
} )( jQuery );
