/* global google, L */
( function () {
	'use strict';

	var CFM = window.cfMapConfig || {};

	function loadLeaflet( callback ) {
		if ( window.L ) { callback(); return; }
		var link = document.createElement( 'link' );
		link.rel  = 'stylesheet';
		link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
		document.head.appendChild( link );
		var script    = document.createElement( 'script' );
		script.src    = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
		script.onload = callback;
		document.head.appendChild( script );
	}

	function initLeafletMap( el, lat, lng, zoom ) {
		var map = L.map( el, { zoomControl: true } );
		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			maxZoom: 19,
		} ).addTo( map );
		map.setView( [ lat, lng ], zoom );
		L.marker( [ lat, lng ] ).addTo( map );
	}

	function geocodeNominatim( address, onOk, onFail ) {
		var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent( address );
		fetch( url )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data && data.length ) {
					onOk( parseFloat( data[ 0 ].lat ), parseFloat( data[ 0 ].lon ) );
				} else {
					onFail();
				}
			} )
			.catch( onFail );
	}

	function initOsm( el ) {
		var lat  = parseFloat( el.getAttribute( 'data-lat' ) || '' );
		var lng  = parseFloat( el.getAttribute( 'data-lng' ) || '' );
		var zoom = parseInt( el.getAttribute( 'data-zoom' ) || '14', 10 );
		loadLeaflet( function () {
			if ( ! isNaN( lat ) && ! isNaN( lng ) ) {
				initLeafletMap( el, lat, lng, zoom );
				return;
			}
			var addr = el.getAttribute( 'data-address' ) || '';
			if ( ! addr ) { el.innerHTML = '<p class="cf-map-error">Brak danych lokalizacji.</p>'; return; }
			geocodeNominatim(
				addr,
				function ( rlat, rlng ) { initLeafletMap( el, rlat, rlng, zoom ); },
				function () { el.innerHTML = '<p class="cf-map-error">Nie można zlokalizować adresu.</p>'; }
			);
		} );
	}

	function loadGoogleMaps( apiKey, callback ) {
		if ( window.google && window.google.maps && window.google.maps.Map ) { callback(); return; }
		window.__cfGoogleMapsReady = callback;
		var script    = document.createElement( 'script' );
		script.async  = true;
		script.src    = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent( apiKey ) + '&callback=__cfGoogleMapsReady';
		document.head.appendChild( script );
	}

	function initGoogleMap( el, lat, lng, zoom, mapType ) {
		var map = new google.maps.Map( el, {
			center: { lat: lat, lng: lng },
			zoom: zoom,
			mapTypeId: mapType || 'roadmap',
		} );
		new google.maps.Marker( { position: { lat: lat, lng: lng }, map: map } );
	}

	function geocodeGoogle( el, address, zoom, mapType ) {
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode( { address: address }, function ( results, status ) {
			if ( status === 'OK' && results.length ) {
				var loc = results[ 0 ].geometry.location;
				initGoogleMap( el, loc.lat(), loc.lng(), zoom, mapType );
			} else {
				el.innerHTML = '<p class="cf-map-error">Nie można zlokalizować adresu.</p>';
			}
		} );
	}

	function initGoogle( el ) {
		var lat     = parseFloat( el.getAttribute( 'data-lat' ) || '' );
		var lng     = parseFloat( el.getAttribute( 'data-lng' ) || '' );
		var zoom    = parseInt( el.getAttribute( 'data-zoom' ) || '14', 10 );
		var mapType = el.getAttribute( 'data-map-type' ) || 'roadmap';
		var apiKey  = CFM.googleApiKey || '';
		if ( ! apiKey ) {
			el.innerHTML = '<p class="cf-map-error">Brak klucza Google Maps API. Skonfiguruj go w ustawieniach wtyczki.</p>';
			return;
		}
		loadGoogleMaps( apiKey, function () {
			if ( ! isNaN( lat ) && ! isNaN( lng ) ) {
				initGoogleMap( el, lat, lng, zoom, mapType );
			} else {
				var addr = el.getAttribute( 'data-address' ) || '';
				if ( ! addr ) { el.innerHTML = '<p class="cf-map-error">Brak danych lokalizacji.</p>'; return; }
				geocodeGoogle( el, addr, zoom, mapType );
			}
		} );
	}

	function initMapElement( el ) {
		if ( el.dataset.cfMapInit ) { return; }
		el.dataset.cfMapInit = '1';
		var provider = el.getAttribute( 'data-provider' ) || 'openstreetmap';
		if ( provider === 'google' ) { initGoogle( el ); } else { initOsm( el ); }
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) { fn(); }
		else { document.addEventListener( 'DOMContentLoaded', fn ); }
	}

	ready( function () {
		document.querySelectorAll( '.cf-event-map[data-provider]' ).forEach( initMapElement );
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function ( $scope ) {
				$scope[ 0 ].querySelectorAll( '.cf-event-map[data-provider]' ).forEach( initMapElement );
			} );
		}
	} );
}() );
