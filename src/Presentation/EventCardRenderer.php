<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\CurrencyFormatter;
use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use WP_Query;
use WP_Term;

final class EventCardRenderer {

	private string $locationMode;
	private bool $showTitle;
	private bool $showProfileTags;
	private string $profileTagsLabel;
	private string $profileTagsStyle;
	private bool $showEventTags;
	private string $eventTagsLabel;
	private bool $showAgeTags;
	private string $ageTagsLabel;
	private string $ageTagsStyle;
	private bool $showDate;
	private string $dateLabel;
	private bool $showLocation;
	private string $locationLabel;
	private string $buttonText;
	private string $priceSuffix;
	private string $priceEmpty;

	/** @var array<string, array{placement: string, order: int}> */
	private array $elementLayout;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct( array $config = array() ) {
		$lm                     = (string) ( $config['location_mode'] ?? '' );
		$this->locationMode     = $lm === 'country_dest_city' ? 'country_dest_city' : 'country_dest';
		$this->showTitle        = (bool) ( $config['show_title'] ?? true );
		$this->showProfileTags  = (bool) ( $config['show_profile_tags'] ?? true );
		$this->profileTagsLabel = (string) ( $config['profile_tags_label'] ?? '' );
		$rawPts                 = (string) ( $config['profile_tags_style'] ?? 'badge' );
		$this->profileTagsStyle = in_array( $rawPts, array( 'badge', 'text' ), true ) ? $rawPts : 'badge';
		$this->showEventTags    = (bool) ( $config['show_event_tags'] ?? true );
		$this->eventTagsLabel   = (string) ( $config['event_tags_label'] ?? '' );
		$this->showAgeTags      = (bool) ( $config['show_age_tags'] ?? true );
		$this->ageTagsLabel     = (string) ( $config['age_tags_label'] ?? '' );
		$rawAts                 = (string) ( $config['age_tags_style'] ?? 'badge' );
		$this->ageTagsStyle     = in_array( $rawAts, array( 'badge', 'text' ), true ) ? $rawAts : 'badge';
		$this->showDate         = (bool) ( $config['show_date'] ?? true );
		$this->dateLabel        = (string) ( $config['date_label'] ?? '' );
		$this->showLocation     = (bool) ( $config['show_location'] ?? true );
		$this->locationLabel    = (string) ( $config['location_label'] ?? '' );
		$this->buttonText       = (string) ( $config['button_text'] ?? '' );
		$this->priceSuffix      = (string) ( $config['price_suffix'] ?? '/os.' );
		$this->priceEmpty       = (string) ( $config['price_empty'] ?? 'na zapytanie' );
		$this->elementLayout    = array(
			'title'        => $this->readLayout( $config, 'title', 5 ),
			'profile_tags' => $this->readLayout( $config, 'profile_tags', 10 ),
			'event_tags'   => $this->readLayout( $config, 'event_tags', 20 ),
			'age_tags'     => $this->readLayout( $config, 'age_tags', 30 ),
			'date'         => $this->readLayout( $config, 'date', 40 ),
			'location'     => $this->readLayout( $config, 'location', 50 ),
			'button'       => $this->readLayout( $config, 'button', 60 ),
		);
	}

	/**
	 * @param array<string, mixed> $config
	 * @return array{placement: string, order: int}
	 */
	private function readLayout( array $config, string $key, int $defaultOrder ): array {
		$pl    = (string) ( $config[ $key . '_placement' ] ?? '' );
		$valid = array( 'below', 'on_image_top_left', 'on_image_top_right', 'on_image_bottom_left', 'on_image_bottom_right' );
		return array(
			'placement' => in_array( $pl, $valid, true ) ? $pl : 'below',
			'order'     => max( 1, (int) ( $config[ $key . '_order' ] ?? $defaultOrder ) ),
		);
	}

	public function renderCard( int $eventId ): string {
		$leadImg     = (string) get_post_meta( $eventId, 'cf_lead_image_url', true );
		$titleRaw    = get_the_title( $eventId );
		$title       = $titleRaw ? (string) $titleRaw : '';
		$permalink   = get_permalink( $eventId );
		$link        = $permalink ? (string) $permalink : '#';
		$minPrice    = (int) get_post_meta( $eventId, 'cf_event_min_price', true );
		$rawCurrency = get_post_meta( $eventId, 'cf_currency', true );
		$currency    = $rawCurrency !== '' && $rawCurrency !== false ? (string) $rawCurrency : 'PLN';
		$sessionId   = $this->nearestMatchingSession( $eventId );
		$btnText     = $this->buttonText !== '' ? $this->buttonText : __( 'Szczegóły', 'campsflow' );

		$elements = $this->buildElementDescriptors( $eventId, $sessionId, $link, $btnText, $minPrice, $currency );
		$onImage  = array_values( array_filter( $elements, fn( $e ) => str_starts_with( (string) $e['placement'], 'on_image' ) ) );
		$below    = array_values( array_filter( $elements, fn( $e ) => ! str_starts_with( (string) $e['placement'], 'on_image' ) ) );
		usort( $below, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );

		$btnOnImage = str_starts_with( $this->elementLayout['button']['placement'], 'on_image' );

		ob_start();
		echo '<article class="cf-card">';
		$this->renderImageArea( $leadImg, $title, $link, $onImage );
		echo '<div class="cf-card__body">';
		foreach ( $below as $elem ) {
			( $elem['render'] )();
		}
		if ( $btnOnImage ) {
			$priceHtml = $this->renderPriceSpan( $minPrice, $currency );
			if ( $priceHtml !== '' ) {
				echo '<div class="cf-card__footer">' . $priceHtml . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '</div></article>';
		return (string) ob_get_clean();
	}

	/**
	 * @return list<array{placement: string, order: int, render: \Closure(): void}>
	 */
	private function buildElementDescriptors( int $eventId, ?int $sessionId, string $link, string $btnText, int $minPrice, string $currency ): array {
		$items = array();
		if ( $this->showTitle ) {
			$l       = $this->elementLayout['title'];
			$items[] = array(
				'placement' => $l['placement'],
				'order'     => $l['order'],
				'render'    => fn() => $this->renderTitleEl( $link, get_the_title( $eventId ) ),
			);
		}
		if ( $this->showProfileTags ) {
			$l       = $this->elementLayout['profile_tags'];
			$items[] = array(
				'placement' => $l['placement'],
				'order'     => $l['order'],
				'render'    => fn() => $this->renderProfileTagsEl( $eventId ),
			);
		}
		if ( $this->showEventTags ) {
			$l       = $this->elementLayout['event_tags'];
			$items[] = array(
				'placement' => $l['placement'],
				'order'     => $l['order'],
				'render'    => fn() => $this->renderEventTagsEl( $eventId ),
			);
		}
		if ( $this->showAgeTags ) {
			$l       = $this->elementLayout['age_tags'];
			$items[] = array(
				'placement' => $l['placement'],
				'order'     => $l['order'],
				'render'    => fn() => $this->renderAgeTagsEl( $eventId ),
			);
		}
		if ( $this->showDate ) {
			$l       = $this->elementLayout['date'];
			$items[] = array(
				'placement' => $l['placement'],
				'order'     => $l['order'],
				'render'    => fn() => $this->renderCardDate( $sessionId ),
			);
		}
		if ( $this->showLocation ) {
			$l       = $this->elementLayout['location'];
			$items[] = array(
				'placement' => $l['placement'],
				'order'     => $l['order'],
				'render'    => fn() => $this->renderCardLocation( $eventId ),
			);
		}
		$bl      = $this->elementLayout['button'];
		$onImg   = str_starts_with( $bl['placement'], 'on_image' );
		$items[] = array(
			'placement' => $bl['placement'],
			'order'     => $bl['order'],
			'render'    => $onImg
				? fn() => $this->renderButtonEl( $link, $btnText )
				: fn() => $this->renderCardFooter( $minPrice, $currency, $link, $btnText ),
		);
		return $items;
	}

	/**
	 * @param list<array{placement: string, order: int, render: \Closure(): void}> $onImage
	 */
	private function renderImageArea( string $leadImg, string $title, string $link, array $onImage ): void {
		if ( ! $leadImg ) {
			foreach ( $onImage as $elem ) {
				( $elem['render'] )();
			}
			return;
		}
		$hasOverlays = ! empty( $onImage );
		if ( $hasOverlays ) {
			echo '<div class="cf-card__image-wrap">';
		}
		echo '<a href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">';
		echo '<img class="cf-card__image" src="' . esc_url( $leadImg ) . '" alt="' . esc_attr( $title ) . '" loading="lazy">';
		echo '</a>';
		if ( $hasOverlays ) {
			$byPlacement = array();
			foreach ( $onImage as $elem ) {
				$byPlacement[ (string) $elem['placement'] ][] = $elem;
			}
			foreach ( $byPlacement as $placement => $elems ) {
				usort( $elems, fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );
				$corner = str_replace( '_', '-', (string) substr( $placement, strlen( 'on_image_' ) ) );
				echo '<div class="cf-card__overlay cf-card__overlay--' . esc_attr( $corner ) . '">';
				foreach ( $elems as $e ) {
					( $e['render'] )();
				}
				echo '</div>';
			}
			echo '</div>';
		}
	}

	private function renderTitleEl( string $link, mixed $rawTitle ): void {
		$title = $rawTitle ? (string) $rawTitle : '';
		echo '<h3 class="cf-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';
	}

	private function renderButtonEl( string $link, string $btnText ): void {
		echo '<a class="cf-btn" href="' . esc_url( $link ) . '">' . esc_html( $btnText ) . '</a>';
	}

	private function renderCardFooter( int $minPrice, string $currency, string $link, string $btnText ): void {
		$priceHtml = $this->renderPriceSpan( $minPrice, $currency );
		echo '<div class="cf-card__footer">';
		if ( $priceHtml !== '' ) {
			echo $priceHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<a class="cf-btn" href="' . esc_url( $link ) . '">' . esc_html( $btnText ) . '</a>';
		echo '</div>';
	}

	private function renderPriceSpan( int $minPrice, string $currency ): string {
		if ( $minPrice > 0 ) {
			$sfx = $this->priceSuffix !== '' ? ' ' . esc_html( $this->priceSuffix ) : '';
			return '<span class="cf-card__price">' . esc_html( $this->formatPrice( $minPrice, $currency ) ) . $sfx . '</span>';
		}
		if ( $this->priceEmpty !== '' ) {
			return '<span class="cf-card__price cf-card__price--empty">' . esc_html( $this->priceEmpty ) . '</span>';
		}
		return '';
	}

	private function renderProfileTagsEl( int $eventId ): void {
		$raw = wp_get_post_terms( $eventId, 'cf_event_category' );
		if ( ! is_wp_error( $raw ) ) {
			$this->renderTermGroup( $raw, 'cf-tag', $this->profileTagsLabel, $this->profileTagsStyle );
		}
	}

	private function renderEventTagsEl( int $eventId ): void {
		$raw = wp_get_post_terms( $eventId, 'cf_event_tag' );
		if ( ! is_wp_error( $raw ) ) {
			$this->renderTermGroup( $raw, 'cf-tag cf-tag--event', $this->eventTagsLabel, 'badge' );
		}
	}

	private function renderAgeTagsEl( int $eventId ): void {
		$raw = wp_get_post_terms( $eventId, 'cf_age_group' );
		if ( ! is_wp_error( $raw ) ) {
			$this->renderTermGroup( $raw, 'cf-tag cf-tag--age', $this->ageTagsLabel, $this->ageTagsStyle );
		}
	}

	public function renderSessionRow( int $sessionId ): string {
		$dateFrom    = (string) get_post_meta( $sessionId, 'cf_date_from', true );
		$dateTo      = (string) get_post_meta( $sessionId, 'cf_date_to', true );
		$price       = (int) get_post_meta( $sessionId, 'cf_price_from', true );
		$rawCurrency = get_post_meta( $sessionId, 'cf_currency', true );
		$currency    = $rawCurrency !== '' && $rawCurrency !== false ? (string) $rawCurrency : 'PLN';
		$turnusName  = (string) get_post_meta( $sessionId, 'cf_turnus_name', true );
		$bucket      = AvailabilityBucket::tryFrom(
			(string) get_post_meta( $sessionId, 'cf_availability', true )
		) ?? AvailabilityBucket::Available;
		$reservUrl   = RegistrationFormShortcode::registrationUrl( $sessionId );
		$isFull      = $bucket === AvailabilityBucket::Full;

		ob_start();

		echo '<li class="cf-session">';
		if ( $turnusName ) {
			echo '<span class="cf-session__name">' . esc_html( $turnusName ) . '</span>';
		}
		echo '<span class="cf-session__dates">' . esc_html( $this->formatDateRange( $dateFrom, $dateTo ) ) . '</span>';
		echo '<span class="cf-session__price">' . esc_html( $this->formatPrice( $price, $currency ) ) . '</span>';

		if ( $bucket !== AvailabilityBucket::Available && $bucket->label() ) {
			echo '<span class="cf-badge cf-badge--' . esc_attr( $bucket->value ) . '">'
				. esc_html( $bucket->label() ) . '</span>';
		}

		if ( $isFull ) {
			echo '<span class="cf-btn cf-btn--disabled">' . esc_html__( 'Brak miejsc', 'campsflow' ) . '</span>';
		} elseif ( $reservUrl ) {
			echo '<a class="cf-btn" href="' . esc_url( $reservUrl ) . '">' . esc_html__( 'Zapisz się', 'campsflow' ) . '</a>';
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

	/**
	 * @param WP_Term[]|int[] $terms
	 */
	private function renderTermGroup( array $terms, string $tagClass, string $label, string $displayStyle = 'badge' ): void {
		$filtered = array_filter( $terms, static fn( mixed $t ) => $t instanceof WP_Term );
		if ( empty( $filtered ) ) {
			return;
		}
		if ( $label !== '' ) {
			echo '<p class="cf-card__section-label">' . esc_html( $label ) . '</p>';
		}
		$isText    = $displayStyle === 'text';
		$wrapClass = $isText ? 'cf-card__tags cf-card__tags--text' : 'cf-card__tags';
		echo '<div class="' . esc_attr( $wrapClass ) . '">';
		foreach ( $filtered as $term ) {
			assert( $term instanceof WP_Term );
			$inlineStyle = '';
			if ( ! $isText ) {
				$color       = (string) get_term_meta( $term->term_id, 'cf_color', true );
				$inlineStyle = $color !== '' ? ' style="background-color:' . esc_attr( $color ) . '"' : '';
			}
			echo '<span class="' . esc_attr( $tagClass ) . '"' . $inlineStyle . '>' . esc_html( $term->name ) . '</span>';
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

		if ( $this->dateLabel !== '' ) {
			echo '<p class="cf-card__section-label">' . esc_html( $this->dateLabel ) . '</p>';
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

		if ( $this->locationLabel !== '' ) {
			echo '<p class="cf-card__section-label">' . esc_html( $this->locationLabel ) . '</p>';
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

	private function formatPrice( int $grosze, string $currency ): string {
		return CurrencyFormatter::format( $grosze, $currency );
	}
}
