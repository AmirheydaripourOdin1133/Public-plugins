/* DSSG Admin JS */
jQuery( function ( $ ) {
	'use strict';

	var $doc = $( document );

	/* ------------------------------------------------- colour pickers */
	$( '.dssg-color-picker' ).each( function () {
		var $input = $( this );
		if ( $input.data( 'picker-ready' ) ) {
			return;
		}
		$input.data( 'picker-ready', true );
		try {
			$input.wpColorPicker();
		} catch ( e ) {
			$input.attr( 'type', 'color' );
		}
	} );

	/* ------------------------------------------------- add tab */
	$doc.on( 'click', '#dssg-add-tab', function ( e ) {
		e.preventDefault();
		var $tpl   = $( '#dssg-tab-template' ),
		    $list  = $( '#dssg-tabs-list' ),
		    index  = $list.children( '.dssg-tab-block' ).length,
		    html   = $tpl.html().replace( /__INDEX__/g, index );
		$list.append( html );
	} );

	/* ------------------------------------------------- duplicate / remove tab */
	$doc.on( 'click', '.dssg-duplicate-tab', function ( e ) {
		e.preventDefault();
		var $block   = $( this ).closest( '.dssg-tab-block' ),
		    $list    = $block.parent(),
		    index    = $list.children( '.dssg-tab-block' ).length,
		    $clone   = $block.clone();
		$clone.insertAfter( $block ).attr( 'data-index', index );
		$clone.find( 'input' ).val( '' );
	} );
	$doc.on( 'click', '.dssg-remove-tab', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( dssgAdmin && dssgAdmin.i18n ? dssgAdmin.i18n.delete : 'حذف کنید؟' ) ) {
			return;
		}
		$( this ).closest( '.dssg-tab-block' ).remove();
		reindexTabs();
	} );

	/* ------------------------------------------------- add item */
	$doc.on( 'click', '.dssg-add-item', function ( e ) {
		e.preventDefault();
		var $btn     = $( this ),
		    tabIndex = $btn.data( 'tab' ),
		    $list    = $( '#dssg-items-list-' + tabIndex ),
		    template = $( '#dssg-item-template-' + tabIndex ),
		    index    = $list.children( '.dssg-item-block' ).length,
		    html     = template.html()
		        .replace( /__ITEM__/g, index )
		        .replace( /__NEW__/g, 'item-' + Date.now() );
		$list.append( html );
	} );

	/* ------------------------------------------------- duplicate / remove item */
	$doc.on( 'click', '.dssg-duplicate-item', function ( e ) {
		e.preventDefault();
		var $block = $( this ).closest( '.dssg-item-block' );
		$block.clone().insertAfter( $block ).find( 'input[type=text]' ).val( '' );
	} );
	$doc.on( 'click', '.dssg-remove-item', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( dssgAdmin && dssgAdmin.i18n ? dssgAdmin.i18n.delete : 'حذف کنید؟' ) ) {
			return;
		}
		$( this ).closest( '.dssg-item-block' ).remove();
	} );

	/* ------------------------------------------------- custom icon upload / remove */
	$doc.on( 'click', '.dssg-remove-custom-icon', function ( e ) {
		e.preventDefault();
		var $row   = $( this ).closest( 'tr' ),
		    $input = $row.find( 'input[type=hidden]' ),
		    $img   = $row.find( 'img' );
		$input.val( '' );
		$img.remove();
		$( this ).remove();
	} );

	/* ------------------------------------------------- reindex tabs after remove */
	function reindexTabs() {
		$( '#dssg-tabs-list .dssg-tab-block' ).each( function ( index ) {
			var $tab = $( this );
			$tab.attr( 'data-index', index );
			$tab.find( 'select, input, textarea' ).each( function () {
				var name = $( this ).attr( 'name' );
				if ( name ) {
					name = name.replace( /tabs\[(\d+)\]/, 'tabs[' + index + ']' );
					$( this ).attr( 'name', name );
				}
			} );
			$tab.find( '.dssg-add-item' ).data( 'tab', index );
			$tab.find( 'template' ).attr( 'id', 'dssg-item-template-' + index );
			$tab.find( '#dssg-items-list-' + index ).attr( 'id', 'dssg-items-list-' + index );
		} );
	}
} );