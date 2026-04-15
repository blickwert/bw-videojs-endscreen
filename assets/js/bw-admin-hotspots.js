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
} );
