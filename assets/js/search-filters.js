/* global AbortController */
( function () {
	'use strict';

	var DEBOUNCE_MS = 300;
	var CF_EVENT    = 'cf:filter-change';

	function debounce( fn, ms ) {
		var timer;
		return function () {
			clearTimeout( timer );
			timer = setTimeout( fn, ms );
		};
	}

	function skeleton( n ) {
		var html = '';
		for ( var i = 0; i < n; i++ ) {
			html += '<div class="cf-skeleton__card"></div>';
		}
		return '<div class="cf-skeleton cf-grid">' + html + '</div>';
	}

	function currentParams() {
		var raw   = new URLSearchParams( window.location.search );
		var clean = new URLSearchParams();
		raw.forEach( function ( v, k ) {
			if ( v !== '' ) {
				clean.append( k, v );
			}
		} );
		return clean;
	}

	/* ── Shared serializer ─────────────────────────────────────────
	   Reads a NodeList/Array of .cf-filter elements and returns a
	   URLSearchParams where multi-selects are joined with commas.
	   ──────────────────────────────────────────────────────────── */
	function collectFilters( elements ) {
		var params = new URLSearchParams();
		var seen   = {};
		elements.forEach( function ( el ) {
			var name = el.getAttribute( 'name' );
			if ( ! name || seen[ name ] ) {
				return;
			}
			if ( el.tagName === 'SELECT' && el.multiple ) {
				seen[ name ] = true;
				var vals = Array.from( el.selectedOptions )
					.map( function ( o ) { return o.value; } )
					.filter( Boolean );
				if ( vals.length ) {
					params.append( name, vals.join( ',' ) );
				}
			} else if ( el.tagName === 'SELECT' || el.tagName === 'INPUT' ) {
				if ( el.value !== '' ) {
					params.append( name, el.value );
				}
			}
		} );
		return params;
	}

	/* ── Filter widget ─────────────────────────────────────────────
	   Intercepts form changes → updates URL → emits CF_EVENT.
	   Does NOT touch the results container directly.
	   ──────────────────────────────────────────────────────────── */
	function initFilterForm( form ) {
		function pushUrl() {
			var clean  = collectFilters( form.querySelectorAll( '.cf-filter[name]' ) );
			var qs     = clean.toString();
			var newUrl = window.location.pathname + ( qs ? '?' + qs : '' );
			window.history.pushState( {}, '', newUrl );
			window.dispatchEvent( new Event( CF_EVENT ) );
		}

		var debouncedPush = debounce( pushUrl, DEBOUNCE_MS );

		form.addEventListener( 'change', function ( e ) {
			e.preventDefault();
			debouncedPush();
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			pushUrl();
		} );

		var dateInputs = form.querySelectorAll( 'input[type="date"], input[type="text"]' );
		dateInputs.forEach( function ( input ) {
			input.addEventListener( 'input', debouncedPush );
		} );
	}

	/* ── Results widget ────────────────────────────────────────────
	   Listens for CF_EVENT and popstate → reads URL params →
	   fetches endpoint → replaces own innerHTML.
	   Fully independent of the filter widget.
	   ──────────────────────────────────────────────────────────── */
	function initResultsContainer( container ) {
		var endpoint = container.getAttribute( 'data-endpoint' );
		if ( ! endpoint ) {
			return;
		}

		var skeletonCount = container.querySelectorAll( '.cf-card' ).length || 3;
		var controller    = null;

		function fetchResults() {
			var params = currentParams();
			var qs     = params.toString();
			var url    = qs ? endpoint + '?' + qs : endpoint;

			if ( controller ) {
				controller.abort();
			}
			controller = new AbortController();

			container.innerHTML = skeleton( skeletonCount );

			window.fetch( url, { signal: controller.signal } )
				.then( function ( response ) {
					if ( ! response.ok ) {
						throw new Error( 'HTTP ' + response.status );
					}
					return response.json();
				} )
				.then( function ( data ) {
					if ( typeof data.html === 'string' ) {
						container.innerHTML = data.html;
						skeletonCount = container.querySelectorAll( '.cf-card' ).length || skeletonCount;
					}
				} )
				.catch( function ( err ) {
					if ( err.name === 'AbortError' ) {
						return;
					}
					container.innerHTML = '<p class="cf-empty cf-empty--error">Wystąpił błąd podczas ładowania wyników.</p>';
				} );
		}

		window.addEventListener( CF_EVENT, fetchResults );
		window.addEventListener( 'popstate', fetchResults );
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	/* ── Standalone filter fields ──────────────────────────────────
	   Individual .cf-filter elements placed outside a form
	   (e.g. independent Elementor widgets). On change they collect
	   ALL .cf-filter[name] values on the page → update URL → emit.
	   ──────────────────────────────────────────────────────────── */
	function initStandaloneFilters() {
		var standalone = document.querySelectorAll( '.cf-filter[name]' );
		if ( ! standalone.length ) {
			return;
		}

		function pushFromAll() {
			var params = collectFilters( document.querySelectorAll( '.cf-filter[name]' ) );
			var qs     = params.toString();
			var newUrl = window.location.pathname + ( qs ? '?' + qs : '' );
			window.history.pushState( {}, '', newUrl );
			window.dispatchEvent( new Event( CF_EVENT ) );
		}

		var debouncedPush = debounce( pushFromAll, DEBOUNCE_MS );

		standalone.forEach( function ( el ) {
			var isInForm = el.closest( 'form.cf-search-form' );
			if ( isInForm ) {
				return;
			}
			el.addEventListener( 'change', debouncedPush );
			if ( el.tagName === 'INPUT' ) {
				el.addEventListener( 'input', debouncedPush );
			}
		} );
	}

	ready( function () {
		document.querySelectorAll( 'form.cf-search-form[data-endpoint]' ).forEach( initFilterForm );
		document.querySelectorAll( '.cf-search-results[data-endpoint]' ).forEach( initResultsContainer );
		initStandaloneFilters();
	} );
}() );
