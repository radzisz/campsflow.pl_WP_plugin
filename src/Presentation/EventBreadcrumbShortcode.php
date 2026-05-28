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
				'mode'      => 'localization',
				'depth'     => '2',
				'separator' => '›',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_breadcrumb'
		);

		$mode      = sanitize_key( $atts['mode'] );
		$depth     = sanitize_key( $atts['depth'] );
		$separator = sanitize_text_field( $atts['separator'] );
		$postId    = (int) get_the_ID();

		if ( ! $postId ) {
			return '';
		}

		ob_start();
		if ( 'season_class' === $mode ) {
			$this->renderSeasonClass( $postId, $separator );
		} else {
			$this->renderLocalization( $postId, $separator, $depth );
		}
		return (string) ob_get_clean();
	}

	private function renderLocalization( int $postId, string $separator, string $depth ): void {
		$raw = (string) get_post_meta( $postId, 'cf_localization', true );
		$loc = json_decode( $raw, true );

		if ( ! is_array( $loc ) ) {
			return;
		}

		$countryCode = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['country'] ?? '' ) : '' );
		$countryName = $countryCode !== '' ? self::resolveCountryName( $countryCode ) : '';
		$destination = (string) ( $loc['destination'] ?? '' );
		$city        = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['city'] ?? '' ) : '' );

		$items = array_values( array_filter( array( $countryName, $destination ) ) );
		if ( '3' === $depth && $city !== '' ) {
			$items[] = $city;
		}

		$this->echoItems( $items, $separator );
	}

	private function renderSeasonClass( int $postId, string $separator ): void {
		$seasons = wp_get_post_terms( $postId, 'cf_season' );
		$season  = ( is_array( $seasons ) && ! empty( $seasons ) && $seasons[0] instanceof \WP_Term )
			? $seasons[0]->name
			: '';

		$process = (string) get_post_meta( $postId, 'cf_event_process_name', true );
		if ( $process === '' ) {
			$process = self::resolveClassLabel( (string) get_post_meta( $postId, 'cf_event_class', true ) );
		}

		$items = array_values( array_filter( array( $season, $process ) ) );
		$this->echoItems( $items, $separator );
	}

	/**
	 * @param string[] $items
	 */
	private function echoItems( array $items, string $separator ): void {
		if ( empty( $items ) ) {
			return;
		}

		$sep  = $separator !== '' ? $separator : '›';
		$last = array_key_last( $items );

		echo '<nav class="cf-breadcrumb" aria-label="' . esc_attr__( 'Ścieżka nawigacji', 'campsflow' ) . '">';
		foreach ( $items as $idx => $item ) {
			echo '<span class="cf-breadcrumb__item">' . esc_html( $item ) . '</span>';
			if ( $idx !== $last ) {
				echo '<span class="cf-breadcrumb__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
			}
		}
		echo '</nav>';
	}

	private static function resolveClassLabel( string $code ): string {
		$map = array(
			'YOUTH_CAMP'    => 'Obóz młodzieżowy',
			'KIDS_CAMP'     => 'Obóz dla dzieci',
			'FAMILY_CAMP'   => 'Obóz rodzinny',
			'LANGUAGE_CAMP' => 'Obóz językowy',
			'SPORTS_CAMP'   => 'Obóz sportowy',
			'SCHOOL_TRIP'   => 'Wycieczka szkolna',
			'DAY_CAMP'      => 'Półkolonie',
		);
		return $map[ $code ] ?? '';
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
