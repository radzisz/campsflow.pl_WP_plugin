<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use WP_Query;

final class EventCardRenderer {

	public function renderCard( int $eventId ): string {
		$locRaw  = (string) get_post_meta( $eventId, 'cf_localization', true );
		$loc     = $locRaw ? ( json_decode( $locRaw, true ) ?? array() ) : array();
		$city    = is_array( $loc['address'] ?? null ) ? ( $loc['address']['city'] ?? '' ) : '';
		$dest    = (string) ( $loc['destination'] ?? '' );
		$leadImg = (string) get_post_meta( $eventId, 'cf_lead_image_url', true );
		$titleRaw = get_the_title( $eventId );
		$title    = $titleRaw ? (string) $titleRaw : '';

		$sessions = new WP_Query(
			array(
				'post_type'      => SessionPostType::SLUG,
				'post_status'    => 'publish',
				'post_parent'    => $eventId,
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => 'cf_date_from',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		ob_start();

		echo '<article class="cf-card">';

		if ( $leadImg ) {
			echo '<img class="cf-card__image" src="' . esc_url( $leadImg ) . '" alt="' . esc_attr( $title ) . '" loading="lazy">';
		}

		echo '<div class="cf-card__body">';
		echo '<h3 class="cf-card__title">' . esc_html( $title ) . '</h3>';

		if ( $city || $dest ) {
			echo '<p class="cf-card__location">';
			if ( $dest ) {
				echo esc_html( $dest );
			}
			if ( $dest && $city ) {
				echo ' · ';
			}
			if ( $city ) {
				echo esc_html( $city );
			}
			echo '</p>';
		}

		if ( $sessions->post_count > 0 ) {
			echo '<ul class="cf-sessions">';
			foreach ( array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $sessions->posts ) as $sessionId ) {
				echo $this->renderSessionRow( $sessionId ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</ul>';
		}

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
