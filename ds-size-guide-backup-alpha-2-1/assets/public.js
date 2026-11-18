/* ==========================================================================
   DSSG — راهنمای سایز بندی محصولات سفارشی (فرانت‌اند JS)
   ========================================================================== */
( function () {
	'use strict';

	/* ------------------------------------------------- native capability */
	var supportsNative = CSS.supports( 'selector(details::details-content)' ) &&
		CSS.supports( 'transition-behavior', 'allow-discrete' ) &&
		'name' in document.createElement( 'details' );

	if ( supportsNative ) {
		// Modern browsers handle grouped details animation via CSS only.
		initModalBehavior();
		initTabsBehavior();
		return;
	}

	/* ------------------------------------------------- JS fallback animation */
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	document.querySelectorAll( '.dssg' ).forEach( function ( guide ) {
		var items = Array.from( guide.querySelectorAll( '.dssg-item' ) );
		var requested = items.find( function ( item ) { return item.open; } );
		var running = null;

		function transition() {
			var current = items.find( function ( item ) { return item.open; } );
			var item = current || requested;
			if ( !item ) { return; }

			var from = item.getBoundingClientRect().height;
			if ( running ) {
				running.cancel();
				running = null;
			}
			var opening = item === requested;
			item.open = opening;
			var to = item.getBoundingClientRect().height;

			var finish = function () {
				item.open = opening;
				running = null;
				if ( !opening && requested ) { transition(); }
			};

			if ( reduceMotion.matches || !item.animate || Math.abs( to - from ) < 1 ) {
				finish();
				return;
			}

			item.open = true;
			var animation = item.animate(
				[ { height: from + 'px' }, { height: to + 'px' } ],
				{
					duration: opening ? 310 : 245,
					easing: 'cubic-bezier(.32, 0, .18, 1)'
				}
			);
			running = animation;
			animation.onfinish = function () {
				if ( running === animation ) { finish(); }
			};
		}

		items.forEach( function ( item ) {
			item.querySelector( 'summary' ).addEventListener( 'click', function ( event ) {
				if ( event.defaultPrevented ) { return; }
				event.preventDefault();
				requested = requested === item ? undefined : item;
				transition();
			} );
		} );

		function settle() {
			if ( !running ) { return; }
			running.cancel();
			running = null;
			items.forEach( function ( item ) { item.open = item === requested; } );
		}

		window.addEventListener( 'resize', settle, { passive: true } );
		if ( reduceMotion.addEventListener ) { reduceMotion.addEventListener( 'change', settle ); }
	} );

	/* ------------------------------------------------- tabs behavior (JS fallback) */
	function initTabsBehavior() {
		document.querySelectorAll( '.dssg-style-tabs' ).forEach( function ( guide ) {
			var buttons = Array.from( guide.querySelectorAll( '.dssg-tab-button' ) );
			var panels  = Array.from( guide.querySelectorAll( '.dssg-tab-panel' ) );

			function activate( index ) {
				buttons.forEach( function ( btn, i ) {
					var active = i === index;
					btn.classList.toggle( 'dssg-is-active', active );
					btn.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				} );
				panels.forEach( function ( panel, i ) {
					var active = i === index;
					panel.classList.toggle( 'dssg-is-active', active );
					panel.style.display = active ? '' : 'none';
				} );
			}

			buttons.forEach( function ( btn, i ) {
				btn.addEventListener( 'click', function () { activate( i ); } );
			} );
			activate( 0 );
		} );
	}

	/* ------------------------------------------------- modal behavior */
	function initModalBehavior() {
		document.querySelectorAll( '.dssg-style-modal' ).forEach( function ( guide ) {
			var trigger = guide.querySelector( '.dssg-modal-trigger' );
			var backdrop = guide.querySelector( '.dssg-modal-backdrop' );
			var close = guide.querySelector( '.dssg-modal-close' );
			var modalId = backdrop.getAttribute( 'id' );

			if ( !trigger || !backdrop || !close ) { return; }

			function open() {
				trigger.setAttribute( 'aria-expanded', 'true' );
				backdrop.style.display = 'flex';
				document.body.style.overflow = 'hidden';
			}
			function closeModal() {
				trigger.setAttribute( 'aria-expanded', 'false' );
				backdrop.style.display = 'none';
				document.body.style.overflow = '';
			}

			trigger.addEventListener( 'click', open );
			close.addEventListener( 'click', closeModal );
			backdrop.addEventListener( 'click', function ( event ) {
				if ( event.target === backdrop ) { closeModal(); }
			} );
			document.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Escape' && backdrop.style.display === 'flex' ) {
					closeModal();
				}
			} );
		} );
	}

	/* ------------------------------------------------- expose for Elementor */
	window.DSSG = window.DSSG || {};
	window.DSSG.refreshTabs = initTabsBehavior;
	window.DSSG.refreshModal = initModalBehavior;

} )();