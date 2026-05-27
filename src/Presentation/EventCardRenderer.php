<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use WP_Query;

final class EventCardRenderer {

	public function __construct( private readonly string $locationMode = 'country_dest' ) {}

	public function renderCard( int $eventId ): string {
		$leadImg   = (string) get_post_meta( $eventId, 'cf_lead_image_url', true );
		$titleRaw  = get_the_title( $eventId );
		$title     = $titleRaw ? (string) $titleRaw : '';
		$permalink = get_permalink( $eventId );
		$link      = $permalink ? (string) $permalink : '#';
		$minPrice  = (int) get_post_meta( $eventId, 'cf_event_min_price', true );
		$sessionId = $this->nearestMatchingSession( $eventId );

		ob_start();

		echo '<article class="cf-card">';

		if ( $leadImg ) {
			echo '<a href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">';
			echo '<img class="cf-card__image" src="' . esc_url( $leadImg ) . '" alt="' . esc_attr( $title ) . '" loading="lazy">';
			echo '</a>';
		}

		echo '<div class="cf-card__body">';
		echo '<h3 class="cf-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';

		$this->renderCardTerms( $eventId );
		$this->renderCardDate( $sessionId );
		$this->renderCardLocation( $eventId );

		echo '<div class="cf-card__footer">';
		if ( $minPrice > 0 ) {
			echo '<span class="cf-card__price">' . esc_html( $this->formatPrice( $minPrice ) ) . ' /os.</span>';
		}
		echo '<a class="cf-btn" href="' . esc_url( $link ) . '">' . esc_html__( 'Szczegóły', 'campsflow' ) . '</a>';
		echo '</div>';

		echo '</div></article>';

		return (string) ob_get_clean();
	}

	public function renderSessionRow( int $sessionId ): string {
		$dateFrom   = (string) get_post_meta( $sessionId, 'cf_date_from', true );
		$dateTo     = (string) get_post_meta( $sessionId, 'cf_date_to', true );
		$price      = (int) get_post_meta( $sessionId, 'cf_price_from', true );
		$turnusName = (string) get_post_meta( $sessionId, 'cf_turnus_name', true );
		$bucket     = AvailabilityBucket::tryFrom(
			(string) get_post_meta( $sessionId, 'cf_availability', true )
		) ?? AvailabilityBucket::Available;
		$reservUrl  = (string) get_post_meta( $sessionId, 'cf_reservation_url', true );
		$isFull     = $bucket === AvailabilityBucket::Full;

		ob_start();

		echo '<li class="cf-session">';
		if ( $turnusName ) {
			echo '<span class="cf-session__name">' . esc_html( $turnusName ) . '</span>';
		}
		echo '<span class="cf-session__dates">' . esc_html( $this->formatDateRange( $dateFrom, $dateTo ) ) . '</span>';
		echo '<span class="cf-session__price">' . esc_html( $this->formatPrice( $price ) ) . '</span>';

		if ( $bucket !== AvailabilityBucket::Available && $bucket->label() ) {
			echo '<span class="cf-badge cf-badge--' . esc_attr( $bucket->value ) . '">'
				. esc_html( $bucket->label() ) . '</span>';
		}

		if ( $isFull ) {
			echo '<span class="cf-btn cf-btn--disabled">' . esc_html__( 'Brak miejsc', 'campsflow' ) . '</span>';
		} elseif ( $reservUrl ) {
			echo '<a class="cf-btn" href="' . esc_url( $reservUrl ) . '" target="_blank" rel="noopener">' . esc_html__( 'Zapisz się', 'campsflow' ) . '</a>';
		}

		echo '</li>';

		return (string) ob_get_clean();
	}

	/**
	 * @param int[] $postIds
	 */
	public function renderGrid( array $postIds ): string {
		$html = '<div class="cf-grid">';
		foreach ( $postIds as $id ) {
			$html .= $this->renderCard( $id );
		}
		$html .= '</div>';
		return $html;
	}

	public function renderEmpty(): string {
		return '<p class="cf-empty">' . esc_html__( 'Brak wydarzeń spełniających kryteria.', 'campsflow' ) . '</p>';
	}

	private function nearestMatchingSession( int $eventId ): ?int {
		$today    = gmdate( 'Y-m-d' );
		$dateFrom = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$dateTo   = sanitize_text_field( $_GET['dateTo'] ?? '' );
		$minDate  = ( $dateFrom && $dateFrom >= $today ) ? $dateFrom : $today;

		$metaQuery = array(
			array(
				'key'     => 'cf_date_from',
				'value'   => $minDate,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);

		if ( $dateTo ) {
			$metaQuery[] = array(
				'key'     => 'cf_date_from',
				'value'   => $dateTo,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => SessionPostType::SLUG,
				'post_parent'    => $eventId,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'meta_value',
				'meta_key'       => 'cf_date_from',
				'order'          => 'ASC',
				'meta_query'     => $metaQuery,
				'fields'         => 'ids',
			)
		);

		$ids = array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
		return $ids[0] ?? null;
	}

	private function renderCardTerms( int $eventId ): void {
		$profiles  = wp_get_post_terms( $eventId, 'cf_event_category' );
		$ageGroups = wp_get_post_terms( $eventId, 'cf_age_group' );

		if ( is_wp_error( $profiles ) ) {
			$profiles = array();
		}
		if ( is_wp_error( $ageGroups ) ) {
			$ageGroups = array();
		}

		if ( empty( $profiles ) && empty( $ageGroups ) ) {
			return;
		}

		echo '<div class="cf-card__tags">';
		foreach ( $profiles as $term ) {
			assert( is_object( $term ) && isset( $term->name ) );
			echo '<span class="cf-tag">' . esc_html( $term->name ) . '</span>';
		}
		foreach ( $ageGroups as $term ) {
			assert( is_object( $term ) && isset( $term->name ) );
			echo '<span class="cf-tag cf-tag--age">' . esc_html( $term->name ) . '</span>';
		}
		echo '</div>';
	}

	private function renderCardDate( ?int $sessionId ): void {
		if ( $sessionId === null ) {
			return;
		}

		$dateFrom = (string) get_post_meta( $sessionId, 'cf_date_from', true );
		$dateTo   = (string) get_post_meta( $sessionId, 'cf_date_to', true );

		if ( ! $dateFrom ) {
			return;
		}

		$f = date_create( $dateFrom );
		$t = $dateTo ? date_create( $dateTo ) : null;

		if ( ! $f ) {
			return;
		}

		$label = $f->format( 'd.m.Y' );

		if ( $t ) {
			$diffDays = $f->diff( $t )->days;
			$days     = is_int( $diffDays ) ? $diffDays + 1 : 0;
			$unit     = $days === 1 ? __( 'dzień', 'campsflow' ) : __( 'dni', 'campsflow' );
			$label   .= ' – ' . $t->format( 'd.m.Y' ) . ' / ' . $days . ' ' . $unit;
		}

		echo '<p class="cf-card__date">' . esc_html( $label ) . '</p>';
	}

	private function renderCardLocation( int $eventId ): void {
		$locRaw = (string) get_post_meta( $eventId, 'cf_localization', true );
		if ( ! $locRaw ) {
			return;
		}

		$loc = json_decode( $locRaw, true );
		if ( ! is_array( $loc ) ) {
			return;
		}

		$address = is_array( $loc['address'] ?? null ) ? $loc['address'] : array();
		$country = (string) ( $address['country'] ?? '' );
		$dest    = (string) ( $loc['destination'] ?? '' );
		$city    = (string) ( $address['city'] ?? '' );

		$raw = array( $country, $dest );
		if ( $this->locationMode === 'country_dest_city' ) {
			$raw[] = $city;
		}
		$parts = array_values( array_filter( $raw ) );
		if ( empty( $parts ) ) {
			return;
		}

		echo '<p class="cf-card__location">' . esc_html( implode( ' / ', $parts ) ) . '</p>';
	}

	private function formatDateRange( string $from, string $to ): string {
		if ( ! $from ) {
			return '';
		}
		$f = date_create( $from );
		$t = $to ? date_create( $to ) : null;
		if ( ! $f ) {
			return $from;
		}
		return $t
			? $f->format( 'j M' ) . '–' . $t->format( 'j M Y' )
			: $f->format( 'j M Y' );
	}

	private function formatPrice( int $grosze ): string {
		if ( $grosze <= 0 ) {
			return '';
		}
		return number_format( $grosze / 100, 0, ',', ' ' ) . ' zł';
	}
}
