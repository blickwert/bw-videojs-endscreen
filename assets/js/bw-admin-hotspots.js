jQuery( function ( $ ) {
	'use strict';

	var $list    = $( '#bw-hotspots-list' );
	var template = $( '#bw-hotspot-template' ).html();
	var rowIndex = $list.find( '.bw-hotspot-row' ).length;

	$( '#bw-add-hotspot' ).on( 'click', function () {
		var html = template.replace( /__IDX__/g, String( rowIndex ) );
		$list.append( html );
		rowIndex++;
	} );

	$list.on( 'click', '.bw-remove-hotspot', function () {
		$( this ).closest( '.bw-hotspot-row' ).remove();
	} );

	$list.on( 'change', '.bw-action-select', function () {
		var $row    = $( this ).closest( '.bw-hotspot-row' );
		var action  = $( this ).val();
		var isModal = ( action === 'modal' );
		$row.find( '.bw-field-content' ).toggle( isModal );
		$row.find( '.bw-field-url' ).toggle( ! isModal );
	} );

	$( document ).on( 'click', '.bw-media-select', function () {
		var $button    = $( this );
		var target     = $button.data( 'target' );
		var mediaType  = String( $button.data( 'media-type' ) || '' );
		var $input     = $( target );
		if ( ! $input.length || typeof wp === 'undefined' || ! wp.media ) return;

		var frame = wp.media( {
			title: 'Medium auswählen',
			button: { text: 'Übernehmen' },
			multiple: false,
			library: mediaType ? { type: mediaType } : {}
		} );

		frame.on( 'select', function () {
			var selection = frame.state().get( 'selection' ).first();
			if ( ! selection ) return;
			var attachment = selection.toJSON();
			if ( attachment && attachment.id ) {
				$input.val( String( attachment.id ) ).trigger( 'change' );
			}
		} );

		frame.open();
	} );
} );
