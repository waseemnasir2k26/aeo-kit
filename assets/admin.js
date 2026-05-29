/* global jQuery, wp */
( function ( $ ) {
	'use strict';

	$( function () {
		// Media picker for the logo field.
		$( '.aeo-media-pick' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( typeof wp === 'undefined' || ! wp.media ) {
				return;
			}
			var $field = $( this ).siblings( '.aeo-media-field' );
			var frame  = wp.media( {
				title: 'Select logo',
				multiple: false,
				library: { type: 'image' }
			} );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$field.val( att.url );
			} );
			frame.open();
		} );

		// FAQ repeatable rows.
		var $wrap = $( '#aeo-faq-rows' );

		$( '#aeo-faq-add' ).on( 'click', function ( e ) {
			e.preventDefault();
			var row =
				'<div class="aeo-faq-row">' +
				'<input type="text" name="aeo_faq_q[]" class="widefat" placeholder="Question" />' +
				'<textarea name="aeo_faq_a[]" class="widefat" rows="2" placeholder="Answer"></textarea>' +
				'<button type="button" class="button-link aeo-faq-remove">&times; Remove</button>' +
				'</div>';
			$wrap.append( row );
		} );

		$wrap.on( 'click', '.aeo-faq-remove', function ( e ) {
			e.preventDefault();
			if ( $wrap.find( '.aeo-faq-row' ).length > 1 ) {
				$( this ).closest( '.aeo-faq-row' ).remove();
			} else {
				$( this ).closest( '.aeo-faq-row' ).find( 'input, textarea' ).val( '' );
			}
		} );
	} );
} )( jQuery );
