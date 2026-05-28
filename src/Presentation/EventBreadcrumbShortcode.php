<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

final class EventBreadcrumbShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_event_breadcrumb', array( $this, 'render' ) );
	}

	/** @param array<string,string>|string $atts */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'mode'       => 'localization',
				'depth'      => '2',
				'separator'  => '›',
				'show_home'  => 'yes',
				'home_label' => __( 'Główna', 'campsflow' ),
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_breadcrumb'
		);

		$mode      = sanitize_key( $atts['mode'] );
		$depth     = sanitize_key( $atts['depth'] );
		$separator = sanitize_text_field( $atts['separator'] );
		$showHome  = sanitize_key( $atts['show_home'] ) === 'yes';
		$homeLabel = sanitize_text_field( $atts['home_label'] );
		$postId    = (int) get_the_ID();

		if ( ! $postId ) {
			return '';
		}

		ob_start();
		if ( 'season_class' === $mode ) {
			$this->renderSeasonClass( $postId, $separator, $showHome, $homeLabel );
		} else {
			$this->renderLocalization( $postId, $separator, $depth, $showHome, $homeLabel );
		}
		return (string) ob_get_clean();
	}

	private function renderLocalization( int $postId, string $separator, string $depth, bool $showHome, string $homeLabel ): void {
		$raw = (string) get_post_meta( $postId, 'cf_localization', true );
		$loc = json_decode( $raw, true );

		if ( ! is_array( $loc ) ) {
			return;
		}

		$countryCode = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['country'] ?? '' ) : '' );
		$countryName = $countryCode !== '' ? self::resolveCountryName( $countryCode ) : '';
		$destination = (string) ( $loc['destination'] ?? '' );
		$city        = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['city'] ?? '' ) : '' );

		$searchUrl   = SearchPage::pageUrl();
		$countrySlug = '';
		$destSlug    = '';

		$destTerms = get_the_terms( $postId, 'cf_destination' );
		if ( is_array( $destTerms ) && ! empty( $destTerms ) && $destTerms[0] instanceof \WP_Term ) {
			$child    = $destTerms[0];
			$destSlug = $child->slug;
			if ( $child->parent > 0 ) {
				$parent = get_term( $child->parent, 'cf_destination' );
				if ( $parent instanceof \WP_Term ) {
					$countrySlug = $parent->slug;
				}
			}
		}

		$items = array();
		if ( $countryName !== '' ) {
			$url     = $countrySlug !== '' ? add_query_arg( 'destination', $countrySlug, $searchUrl ) : $searchUrl;
			$items[] = array( $countryName, $url );
		}
		if ( $destination !== '' ) {
			$url     = $destSlug !== '' ? add_query_arg( 'destination', $destSlug, $searchUrl ) : $searchUrl;
			$items[] = array( $destination, $url );
		}
		if ( '3' === $depth && $city !== '' ) {
			$url     = $destSlug !== '' ? add_query_arg( 'destination', $destSlug, $searchUrl ) : $searchUrl;
			$items[] = array( $city, $url );
		}

		$this->echoItems( $items, $separator, $showHome, $homeLabel );
	}

	private function renderSeasonClass( int $postId, string $separator, bool $showHome, string $homeLabel ): void {
		$searchUrl = SearchPage::pageUrl();

		$seasons = wp_get_post_terms( $postId, 'cf_season' );
		$season  = ( is_array( $seasons ) && ! empty( $seasons ) && $seasons[0] instanceof \WP_Term )
			? $seasons[0]->name
			: '';

		$cats         = wp_get_post_terms( $postId, 'cf_event_category' );
		$categoryTerm = ( is_array( $cats ) && ! empty( $cats ) && $cats[0] instanceof \WP_Term )
			? $cats[0]
			: null;
		$category     = $categoryTerm ? $categoryTerm->name : '';

		$items = array();
		if ( $season !== '' ) {
			$items[] = array( $season, $searchUrl );
		}
		if ( $category !== '' ) {
			$url     = $categoryTerm ? add_query_arg( 'category', $categoryTerm->slug, $searchUrl ) : $searchUrl;
			$items[] = array( $category, $url );
		}

		$this->echoItems( $items, $separator, $showHome, $homeLabel );
	}

	/**
	 * @param array<int, array{string, string}> $items [label, url] pairs
	 */
	private function echoItems( array $items, string $separator, bool $showHome, string $homeLabel ): void {
		if ( empty( $items ) && ! $showHome ) {
			return;
		}

		$sep = $separator !== '' ? $separator : '›';

		echo '<nav class="cf-breadcrumb" aria-label="' . esc_attr__( 'Ścieżka nawigacji', 'campsflow' ) . '">';

		if ( $showHome ) {
			$homeUrl = SearchPage::pageUrl();
			echo '<a class="cf-breadcrumb__link cf-breadcrumb__home" href="' . esc_url( $homeUrl ) . '">';
			if ( $homeLabel !== '' ) {
				echo esc_html( $homeLabel );
			} else {
				echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>';
			}
			echo '</a>';
			if ( ! empty( $items ) ) {
				echo '<span class="cf-breadcrumb__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
			}
		}

		$last = array_key_last( $items );
		foreach ( $items as $idx => [ $label, $url ] ) {
			if ( $idx === $last ) {
				echo '<span class="cf-breadcrumb__item" aria-current="page">' . esc_html( $label ) . '</span>';
			} else {
				echo '<a class="cf-breadcrumb__link cf-breadcrumb__item" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
			if ( $idx !== $last ) {
				echo '<span class="cf-breadcrumb__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
			}
		}

		echo '</nav>';
	}

	private static function resolveCountryName( string $code ): string {
		$map = array(
			'PL' => 'Polska',
			'DE' => 'Niemcy',
			'CZ' => 'Czechy',
			'SK' => 'Słowacja',
			'AT' => 'Austria',
			'CH' => 'Szwajcaria',
			'IT' => 'Włochy',
			'FR' => 'Francja',
			'ES' => 'Hiszpania',
			'PT' => 'Portugalia',
			'GR' => 'Grecja',
			'HR' => 'Chorwacja',
			'SI' => 'Słowenia',
			'HU' => 'Węgry',
			'RO' => 'Rumunia',
			'BG' => 'Bułgaria',
			'TR' => 'Turcja',
			'MT' => 'Malta',
			'GB' => 'Wielka Brytania',
			'IE' => 'Irlandia',
			'NL' => 'Holandia',
			'BE' => 'Belgia',
			'DK' => 'Dania',
			'SE' => 'Szwecja',
			'NO' => 'Norwegia',
			'FI' => 'Finlandia',
			'ME' => 'Czarnogóra',
			'RS' => 'Serbia',
			'BA' => 'Bośnia i Hercegowina',
			'AL' => 'Albania',
			'MK' => 'Macedonia Północna',
		);
		return $map[ strtoupper( $code ) ] ?? $code;
	}
}
