/* global AbortController */
( function () {
	'use strict';

	var DEBOUNCE_MS = 300;
	var CF_EVENT    = 'cf:filter-change';

	var MONTHS_PL = [ 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
	                  'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień' ];
	var DAYS_PL   = [ 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'Nd' ];

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
	   Reads .cf-filter[name] (native inputs/selects) and
	   .cf-multi[data-name] (custom checkbox dropdowns) within
	   the given root element (or document if omitted).
	   Multi-selects are joined with commas in the URL.
	   ──────────────────────────────────────────────────────────── */
	function collectFilters( root ) {
		var params = new URLSearchParams();
		var seen   = {};
		var scope  = root || document;

		scope.querySelectorAll( '.cf-filter[name]' ).forEach( function ( el ) {
			var name = el.getAttribute( 'name' );
			if ( ! name || seen[ name ] ) {
				return;
			}
			seen[ name ] = true;
			if ( el.tagName === 'SELECT' || el.tagName === 'INPUT' ) {
				if ( el.value !== '' ) {
					params.append( name, el.value );
				}
			}
		} );

		scope.querySelectorAll( '.cf-multi[data-name]' ).forEach( function ( widget ) {
			var name = widget.getAttribute( 'data-name' );
			if ( ! name || seen[ name ] ) {
				return;
			}
			seen[ name ] = true;
			var vals = Array.from( widget.querySelectorAll( '.cf-multi__dropdown input[type="checkbox"]:checked' ) )
				.map( function ( cb ) { return cb.value; } )
				.filter( Boolean );
			if ( vals.length ) {
				params.append( name, vals.join( ',' ) );
			}
		} );

		return params;
	}

	/* ── Sort bar ─────────────────────────────────────────────────
	   Clickable labels: inactive → asc (▲) → desc (▼) → asc…
	   Writes to a hidden <input name="sort"> which collectFilters
	   picks up just like any other .cf-filter element.
	   ──────────────────────────────────────────────────────────── */
	function syncSortBtns( bar, currentSort ) {
		bar.querySelectorAll( '.cf-sort-btn' ).forEach( function ( btn ) {
			var asc   = btn.getAttribute( 'data-asc' );
			var desc  = btn.getAttribute( 'data-desc' );
			var arrow = btn.querySelector( '.cf-sort-btn__arrow' );

			btn.classList.remove( 'is-active', 'is-asc', 'is-desc' );
			if ( arrow ) {
				arrow.textContent = '';
			}
			if ( currentSort === asc ) {
				btn.classList.add( 'is-active', 'is-asc' );
				if ( arrow ) { arrow.textContent = '▲'; }
			} else if ( currentSort === desc ) {
				btn.classList.add( 'is-active', 'is-desc' );
				if ( arrow ) { arrow.textContent = '▼'; }
			}
		} );
	}

	function initSortBar( bar ) {
		if ( bar.dataset.cfInit ) {
			return;
		}
		bar.dataset.cfInit = '1';

		var hiddenInput = bar.querySelector( 'input[type="hidden"][name="sort"]' );
		if ( ! hiddenInput ) {
			return;
		}

		syncSortBtns( bar, hiddenInput.value );

		bar.querySelectorAll( '.cf-sort-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var asc  = btn.getAttribute( 'data-asc' );
				var desc = btn.getAttribute( 'data-desc' );
				var cur  = hiddenInput.value;
				hiddenInput.value = cur === asc ? desc : asc;
				syncSortBtns( bar, hiddenInput.value );
				hiddenInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		} );

		function syncFromUrl() {
			var sort = new URLSearchParams( window.location.search ).get( 'sort' ) || '';
			hiddenInput.value = sort;
			syncSortBtns( bar, sort );
		}
		window.addEventListener( CF_EVENT, syncFromUrl );
		window.addEventListener( 'popstate', syncFromUrl );
	}

	/* ── Custom multi-select dropdown ─────────────────────────────
	   Toggles .is-open on click, closes on outside click,
	   updates the toggle label to reflect selection state.
	   ──────────────────────────────────────────────────────────── */
	function updateMultiToggle( widget ) {
		var emptyLabel = widget.getAttribute( 'data-empty-label' ) || '';
		var checked    = Array.from( widget.querySelectorAll( '.cf-multi__dropdown input[type="checkbox"]:checked' ) );
		var labelEl    = widget.querySelector( '.cf-multi__label' );
		var countEl    = widget.querySelector( '.cf-multi__count' );
		var toggleEl   = widget.querySelector( '.cf-multi__toggle' );

		if ( ! labelEl || ! countEl || ! toggleEl ) {
			return;
		}

		if ( checked.length === 0 ) {
			labelEl.textContent = emptyLabel;
			countEl.textContent = '';
			countEl.style.display = 'none';
		} else if ( checked.length === 1 ) {
			labelEl.textContent = checked[0].getAttribute( 'data-label' ) || checked[0].value;
			countEl.textContent = '';
			countEl.style.display = 'none';
		} else {
			labelEl.textContent = emptyLabel;
			countEl.textContent = String( checked.length );
			countEl.style.display = '';
		}

		toggleEl.setAttribute( 'aria-expanded', widget.classList.contains( 'is-open' ) ? 'true' : 'false' );
	}

	function initMultiSelect( widget ) {
		if ( widget.dataset.cfInit ) {
			return;
		}
		widget.dataset.cfInit = '1';

		var toggle = widget.querySelector( '.cf-multi__toggle' );
		if ( ! toggle ) {
			return;
		}

		updateMultiToggle( widget );

		toggle.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var isOpen = widget.classList.toggle( 'is-open' );
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );

		widget.querySelectorAll( '.cf-multi__dropdown input[type="checkbox"]' ).forEach( function ( cb ) {
			cb.addEventListener( 'change', function () {
				updateMultiToggle( widget );
			} );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.cf-multi' ) ) {
			document.querySelectorAll( '.cf-multi.is-open' ).forEach( function ( w ) {
				w.classList.remove( 'is-open' );
				var t = w.querySelector( '.cf-multi__toggle' );
				if ( t ) {
					t.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		}
		if ( ! e.target.closest( '.cf-daterange' ) ) {
			document.querySelectorAll( '.cf-daterange.is-open' ).forEach( function ( w ) {
				w.classList.remove( 'is-open' );
				var t = w.querySelector( '.cf-daterange__toggle' );
				if ( t ) {
					t.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		}
	} );

	/* ── Date range picker (.cf-daterange) ─────────────────────────
	   Two-month calendar with range selection. Writes to two hidden
	   <input class="cf-filter"> elements so collectFilters() picks
	   them up alongside all other filters.
	   ─────────────────────────────────────────────────────────────── */
	function drPad( n ) {
		return String( n ).padStart( 2, '0' );
	}

	function drFmt( ymd ) {
		if ( ! ymd ) { return ''; }
		var p = ymd.split( '-' );
		return p.length === 3 ? p[ 2 ] + '.' + p[ 1 ] + '.' + p[ 0 ] : ymd;
	}

	function drBuildMonth( year, month, state ) {
		var firstDay    = ( new Date( year, month, 1 ).getDay() + 6 ) % 7;
		var daysInMonth = new Date( year, month + 1, 0 ).getDate();
		var today       = new Date(); today.setHours( 0, 0, 0, 0 );
		var todayStr    = today.getFullYear() + '-' + drPad( today.getMonth() + 1 ) + '-' + drPad( today.getDate() );
		var html = '<div class="cf-cal">';
		html += '<div class="cf-cal__header">';
		html += '<span class="cf-cal__nav cf-cal__nav--prev" role="button" tabindex="0">&#8249;</span>';
		html += '<span class="cf-cal__title">' + MONTHS_PL[ month ] + ' ' + year + '</span>';
		html += '<span class="cf-cal__nav cf-cal__nav--next" role="button" tabindex="0">&#8250;</span>';
		html += '</div><table class="cf-cal__grid" role="grid"><thead><tr>';
		DAYS_PL.forEach( function ( d ) { html += '<th scope="col">' + d + '</th>'; } );
		html += '</tr></thead><tbody>';
		var totalRows = Math.ceil( ( firstDay + daysInMonth ) / 7 );
		for ( var row = 0; row < totalRows && row < 6; row++ ) {
			html += '<tr>';
			for ( var col = 0; col < 7; col++ ) {
				var ci = row * 7 + col;
				if ( ci < firstDay || ci >= firstDay + daysInMonth ) {
					html += '<td class="cf-cal__day cf-cal__day--empty" aria-hidden="true"></td>';
				} else {
					var day = ci - firstDay + 1;
					var ds  = year + '-' + drPad( month + 1 ) + '-' + drPad( day );
					var cls = 'cf-cal__day';
					if ( ds === state.from ) { cls += ' cf-cal__day--selected cf-cal__day--from'; }
					if ( ds === state.to )   { cls += ' cf-cal__day--selected cf-cal__day--to'; }
					if ( state.from && state.to && ds > state.from && ds < state.to ) { cls += ' cf-cal__day--range'; }
					if ( ds === todayStr )   { cls += ' cf-cal__day--today'; }
					html += '<td class="' + cls + '" data-date="' + ds + '">' + day + '</td>';
				}
			}
			html += '</tr>';
		}
		html += '</tbody></table></div>';
		return html;
	}

	function drUpdateDisplay( el, state ) {
		var hiddenFrom = el.querySelector( 'input[type="hidden"][name="dateFrom"]' );
		var hiddenTo   = el.querySelector( 'input[type="hidden"][name="dateTo"]' );
		var textFrom   = el.querySelector( '.cf-daterange__text[data-role="from"]' );
		var textTo     = el.querySelector( '.cf-daterange__text[data-role="to"]' );
		var labelEl    = el.querySelector( '.cf-daterange__label' );
		var fieldFrom  = el.querySelector( '.cf-daterange__field[data-role="from"]' );
		var fieldTo    = el.querySelector( '.cf-daterange__field[data-role="to"]' );
		if ( hiddenFrom ) { hiddenFrom.value = state.from || ''; }
		if ( hiddenTo )   { hiddenTo.value   = state.to   || ''; }
		if ( textFrom )   { textFrom.value   = drFmt( state.from ); }
		if ( textTo )     { textTo.value     = drFmt( state.to ); }
		if ( fieldFrom )  { fieldFrom.classList.toggle( 'is-active', ! state.from ); }
		if ( fieldTo )    { fieldTo.classList.toggle( 'is-active', !! state.from && ! state.to ); }
		if ( ! labelEl )  { return; }
		var empty = el.getAttribute( 'data-empty-label' ) || 'Termin';
		if ( state.from && state.to ) {
			labelEl.textContent = drFmt( state.from ) + ' – ' + drFmt( state.to );
		} else if ( state.from ) {
			labelEl.textContent = drFmt( state.from ) + ' – ?';
		} else {
			labelEl.textContent = empty;
		}
	}

	function drAttachDayHandlers( calsEl, el, state ) {
		calsEl.querySelectorAll( '.cf-cal__day[data-date]' ).forEach( function ( cell ) {
			cell.addEventListener( 'mouseenter', function () {
				if ( ! state.from || state.to ) { return; }
				var hd = cell.getAttribute( 'data-date' );
				calsEl.querySelectorAll( '.cf-cal__day[data-date]' ).forEach( function ( c ) {
					var cd      = c.getAttribute( 'data-date' );
					var inRange = cd && hd && ( ( cd > state.from && cd <= hd ) || ( cd < state.from && cd >= hd ) );
					c.classList.toggle( 'cf-cal__day--hover', !! inRange );
				} );
			} );
			cell.addEventListener( 'click', function () {
				var ds = cell.getAttribute( 'data-date' );
				if ( ! ds ) { return; }
				if ( ! state.from || ( state.from && state.to ) ) {
					state.from = ds; state.to = null;
				} else if ( ds === state.from ) {
					state.from = null;
				} else if ( ds < state.from ) {
					state.to = state.from; state.from = ds;
				} else {
					state.to = ds;
				}
				drUpdateDisplay( el, state );
				drRenderCalendars( el, state );
			} );
		} );
		calsEl.addEventListener( 'mouseleave', function () {
			calsEl.querySelectorAll( '.cf-cal__day--hover' ).forEach( function ( c ) {
				c.classList.remove( 'cf-cal__day--hover' );
			} );
		} );
	}

	function drRenderCalendars( el, state ) {
		var calsEl = el.querySelector( '.cf-daterange__calendars' );
		if ( ! calsEl ) { return; }
		var ry = state.leftYear, rm = state.leftMonth + 1;
		if ( rm > 11 ) { rm = 0; ry++; }
		calsEl.innerHTML = drBuildMonth( state.leftYear, state.leftMonth, state ) + drBuildMonth( ry, rm, state );
		calsEl.querySelectorAll( '.cf-cal__nav--prev' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				state.leftMonth--;
				if ( state.leftMonth < 0 ) { state.leftMonth = 11; state.leftYear--; }
				drRenderCalendars( el, state );
			} );
		} );
		calsEl.querySelectorAll( '.cf-cal__nav--next' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				state.leftMonth++;
				if ( state.leftMonth > 11 ) { state.leftMonth = 0; state.leftYear++; }
				drRenderCalendars( el, state );
			} );
		} );
		drAttachDayHandlers( calsEl, el, state );
	}

	function initDateRange( el ) {
		if ( el.dataset.cfInit ) { return; }
		el.dataset.cfInit = '1';
		var hiddenFrom = el.querySelector( 'input[type="hidden"][name="dateFrom"]' );
		var hiddenTo   = el.querySelector( 'input[type="hidden"][name="dateTo"]' );
		var toggle     = el.querySelector( '.cf-daterange__toggle' );
		var clearBtn   = el.querySelector( '.cf-daterange__clear' );
		var confirmBtn = el.querySelector( '.cf-daterange__confirm' );
		if ( ! toggle ) { return; }
		var today = new Date();
		var state = {
			from:      hiddenFrom && hiddenFrom.value ? hiddenFrom.value : null,
			to:        hiddenTo   && hiddenTo.value   ? hiddenTo.value   : null,
			leftYear:  today.getFullYear(),
			leftMonth: today.getMonth()
		};
		if ( state.from ) {
			var fp = state.from.split( '-' );
			if ( fp.length === 3 ) { state.leftYear = parseInt( fp[ 0 ], 10 ); state.leftMonth = parseInt( fp[ 1 ], 10 ) - 1; }
		}
		toggle.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var isOpen = el.classList.toggle( 'is-open' );
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
			if ( isOpen ) { drRenderCalendars( el, state ); }
		} );
		el.addEventListener( 'click', function ( e ) { e.stopPropagation(); } );
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function () {
				state.from = null; state.to = null;
				drUpdateDisplay( el, state );
				drRenderCalendars( el, state );
				if ( hiddenFrom ) { hiddenFrom.dispatchEvent( new Event( 'change', { bubbles: true } ) ); }
			} );
		}
		if ( confirmBtn ) {
			confirmBtn.addEventListener( 'click', function () {
				el.classList.remove( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
				if ( hiddenFrom ) { hiddenFrom.dispatchEvent( new Event( 'change', { bubbles: true } ) ); }
			} );
		}
		function syncFromUrl() {
			var p = new URLSearchParams( window.location.search );
			state.from = p.get( 'dateFrom' ) || null;
			state.to   = p.get( 'dateTo' )   || null;
			drUpdateDisplay( el, state );
			if ( el.classList.contains( 'is-open' ) ) { drRenderCalendars( el, state ); }
		}
		window.addEventListener( CF_EVENT, syncFromUrl );
		window.addEventListener( 'popstate', syncFromUrl );
		drUpdateDisplay( el, state );
	}

	/* ── Filter widget ─────────────────────────────────────────────
	   Intercepts form changes → updates URL → emits CF_EVENT.
	   Does NOT touch the results container directly.
	   ──────────────────────────────────────────────────────────── */
	function initFilterForm( form ) {
		function pushUrl() {
			var clean  = collectFilters( form );
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

		var resetBtn = form.querySelector( '.cf-reset' );
		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', function () {
				form.querySelectorAll( '.cf-filter[name]' ).forEach( function ( el ) {
					if ( el.tagName === 'SELECT' ) {
						el.selectedIndex = 0;
					} else {
						el.value = '';
					}
				} );
				form.querySelectorAll( '.cf-multi[data-name]' ).forEach( function ( widget ) {
					widget.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( cb ) {
						cb.checked = false;
					} );
					updateMultiToggle( widget );
				} );
				pushUrl();
			} );
		}
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
	   Individual .cf-filter / .cf-multi elements placed outside a form
	   (e.g. independent Elementor widgets). On change they collect
	   ALL filter values on the page → update URL → emit.
	   ──────────────────────────────────────────────────────────── */
	function initStandaloneFilters() {
		var standaloneNative = document.querySelectorAll( '.cf-filter[name]' );
		var standaloneMulti  = document.querySelectorAll( '.cf-multi[data-name]' );

		if ( ! standaloneNative.length && ! standaloneMulti.length ) {
			return;
		}

		function pushFromAll() {
			var params = collectFilters( document );
			var qs     = params.toString();
			var newUrl = window.location.pathname + ( qs ? '?' + qs : '' );
			window.history.pushState( {}, '', newUrl );
			window.dispatchEvent( new Event( CF_EVENT ) );
		}

		var debouncedPush = debounce( pushFromAll, DEBOUNCE_MS );

		standaloneNative.forEach( function ( el ) {
			if ( el.closest( 'form.cf-search-form' ) ) {
				return;
			}
			el.addEventListener( 'change', debouncedPush );
			if ( el.tagName === 'INPUT' ) {
				el.addEventListener( 'input', debouncedPush );
			}
		} );

		standaloneMulti.forEach( function ( widget ) {
			if ( widget.closest( 'form.cf-search-form' ) ) {
				return;
			}
			widget.querySelectorAll( '.cf-multi__dropdown input[type="checkbox"]' ).forEach( function ( cb ) {
				cb.addEventListener( 'change', debouncedPush );
			} );
		} );
	}

	function initScope( scope ) {
		scope.querySelectorAll( '.cf-sort-bar' ).forEach( initSortBar );
		scope.querySelectorAll( '.cf-multi[data-name]' ).forEach( initMultiSelect );
		scope.querySelectorAll( '.cf-daterange[data-name-from]' ).forEach( initDateRange );
		scope.querySelectorAll( 'form.cf-search-form[data-endpoint]' ).forEach( initFilterForm );
		scope.querySelectorAll( '.cf-search-results[data-endpoint]' ).forEach( initResultsContainer );
	}

	ready( function () {
		initScope( document );
		initStandaloneFilters();

		/* Re-initialize after Elementor re-renders a widget in the editor */
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function ( $scope ) {
				initScope( $scope[0] );
			} );
		}
	} );
}() );
