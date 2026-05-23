<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use WP_Query;

/**
 * [campsflow_event_meta show="location,tags,description,photos,program,price_include,documents,terms,instructions,contact"]
 * [campsflow_event_sessions title="..." button_label="..." show_meeting_points="0"]
 */
final class EventShortcodes {

	public function register(): void {
		add_shortcode( 'campsflow_event_meta', array( $this, 'renderMeta' ) );
		add_shortcode( 'campsflow_event_sessions', array( $this, 'renderSessions' ) );
	}

	// ── Shortcode: event meta ─────────────────────────────────────────────────

	/** @param array<string,string>|string $atts */
	public function renderMeta( array|string $atts ): string {
		$atts   = shortcode_atts(
			array( 'show' => 'location,tags,description' ),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_meta'
		);
		$show   = array_map( 'trim', explode( ',', $atts['show'] ) );
		$postId = (int) get_the_ID();
		if ( ! $postId ) {
			return '';
		}

		$loc  = json_decode( (string) get_post_meta( $postId, 'cf_localization', true ), true );
		$desc = json_decode( (string) get_post_meta( $postId, 'cf_description', true ), true );

		ob_start();
		echo '<div class="cf-event-body">';
		if ( in_array( 'photos', $show, true ) ) {
			$this->echoPhotos( $postId );
		}
		if ( in_array( 'location', $show, true ) ) {
			$this->echoLocation( $loc );
		}
		if ( in_array( 'tags', $show, true ) ) {
			$this->echoTags( $postId );
		}
		if ( in_array( 'description', $show, true ) ) {
			$this->echoDescription( $postId );
		}
		if ( in_array( 'program', $show, true ) ) {
			$this->echoProgram( $desc );
		}
		if ( in_array( 'price_include', $show, true ) ) {
			$this->echoPriceInclude( $desc );
		}
		if ( in_array( 'documents', $show, true ) ) {
			$this->echoDocuments( $postId );
		}
		if ( in_array( 'terms', $show, true ) ) {
			$this->echoTerms( $postId );
		}
		if ( in_array( 'instructions', $show, true ) ) {
			$this->echoInstructions( $postId );
		}
		if ( in_array( 'contact', $show, true ) ) {
			$this->echoContact( $postId );
		}
		if ( in_array( 'custom_fields', $show, true ) ) {
			$this->echoCustomFields( $postId );
		}
		echo '</div>';
		return (string) ob_get_clean();
	}

	// ── Shortcode: sessions list ──────────────────────────────────────────────

	/** @param array<string,string>|string $atts */
	public function renderSessions( array|string $atts ): string {
		$atts              = shortcode_atts(
			array(
				'title'               => __( 'Dostępne terminy', 'campsflow' ),
				'button_label'        => __( 'Zapisz się', 'campsflow' ),
				'show_meeting_points' => '0',
				'show_custom_fields'  => '0',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_sessions'
		);
		$postId            = (int) get_the_ID();
		$buttonLabel       = sanitize_text_field( $atts['button_label'] );
		$title             = sanitize_text_field( $atts['title'] );
		$showMeetingPoints = $atts['show_meeting_points'] === '1';
		$showCustomFields  = $atts['show_custom_fields'] === '1';
		if ( ! $postId ) {
			return '';
		}

		$sessions = new WP_Query(
			array(
				'post_type'      => SessionPostType::SLUG,
				'post_status'    => 'publish',
				'post_parent'    => $postId,
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => 'cf_date_from',
				'order'          => 'ASC',
			)
		);

		ob_start();
		echo '<div class="cf-sessions-box">';
		if ( $title ) {
			echo '<h2 class="cf-sessions-box__title">' . esc_html( $title ) . '</h2>';
		}
		if ( ! $sessions->have_posts() ) {
			echo '<p class="cf-empty">' . esc_html__( 'Brak dostępnych terminów.', 'campsflow' ) . '</p>';
			echo '</div>';
			return (string) ob_get_clean();
		}
		echo '<ul class="cf-sessions-box__list">';
		while ( $sessions->have_posts() ) {
			$sessions->the_post();
			$this->echoSessionItem( (int) get_the_ID(), $buttonLabel, $showMeetingPoints, $showCustomFields );
		}
		wp_reset_postdata();
		echo '</ul></div>';
		return (string) ob_get_clean();
	}

	// ── Event meta helpers ────────────────────────────────────────────────────

	private function echoPhotos( int $postId ): void {
		$urls = json_decode( (string) get_post_meta( $postId, 'cf_multimedia_urls', true ), true );
		if ( ! is_array( $urls ) || empty( $urls ) ) {
			return;
		}
		echo '<div class="cf-event-body__photos">';
		foreach ( array_slice( $urls, 0, 10 ) as $url ) {
			if ( ! is_string( $url ) || ! $url ) {
				continue;
			}
			echo '<img src="' . esc_url( $url ) . '" alt="" loading="lazy" class="cf-event-body__photo">';
		}
		echo '</div>';
	}

	/** @param array<string,mixed>|null $loc */
	private function echoLocation( ?array $loc ): void {
		if ( ! is_array( $loc ) ) {
			return;
		}
		$addr    = is_array( $loc['address'] ?? null ) ? $loc['address'] : array();
		$city    = (string) ( $addr['city'] ?? '' );
		$street  = (string) ( $addr['address'] ?? '' );
		$dest    = (string) ( $loc['destination'] ?? '' );
		$locName = (string) ( $loc['name'] ?? '' );
		$gps     = is_array( $loc['gps'] ?? null ) ? $loc['gps'] : null;
		$phone   = (string) ( $loc['phone'] ?? '' );
		$email   = (string) ( $loc['email'] ?? '' );
		$webpage = (string) ( $loc['webpage'] ?? '' );
		if ( ! $city && ! $dest && ! $locName ) {
			return;
		}

		echo '<div class="cf-event-body__location">';
		echo '<span class="dashicons dashicons-location"></span>';
		$titleParts = array_filter( array( $dest, $locName ) );
		if ( $titleParts ) {
			echo '<strong>' . esc_html( implode( ' — ', $titleParts ) ) . '</strong>';
		}
		$addrParts = array_filter( array( $street, $city ) );
		if ( $addrParts ) {
			echo '<span>' . esc_html( implode( ', ', $addrParts ) ) . '</span>';
		}
		if ( $gps && isset( $gps['lat'], $gps['lng'] ) ) {
			$url = 'https://www.google.com/maps?q=' . $gps['lat'] . ',' . $gps['lng'];
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Pokaż na mapie', 'campsflow' ) . '</a>';
		}
		if ( $phone ) {
			echo '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
		}
		if ( $email ) {
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
		if ( $webpage ) {
			echo '<a href="' . esc_url( $webpage ) . '" target="_blank" rel="noopener">' . esc_html( $webpage ) . '</a>';
		}
		echo '</div>';
	}

	private function echoTags( int $postId ): void {
		$tags = get_the_terms( $postId, 'cf_tag' );
		$ages = get_the_terms( $postId, 'cf_age_group' );
		if ( ( ! $tags || is_wp_error( $tags ) ) && ( ! $ages || is_wp_error( $ages ) ) ) {
			return;
		}
		echo '<div class="cf-event-body__tags">';
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				echo '<span class="cf-tag">' . esc_html( $tag->name ) . '</span>';
			}
		}
		if ( $ages && ! is_wp_error( $ages ) ) {
			foreach ( $ages as $age ) {
				echo '<span class="cf-tag cf-tag--age">' . esc_html( $age->name ) . '</span>';
			}
		}
		echo '</div>';
	}

	private function echoDescription( int $postId ): void {
		$content = get_post_field( 'post_content', $postId );
		if ( ! $content ) {
			return;
		}
		echo '<div class="cf-event-body__description">';
		echo wp_kses_post( apply_filters( 'the_content', $content ) );
		echo '</div>';
	}

	/** @param array<string,mixed>|null $desc */
	private function echoProgram( ?array $desc ): void {
		$program = is_array( $desc ) ? (string) ( $desc['program'] ?? '' ) : '';
		if ( ! $program ) {
			return;
		}
		echo '<div class="cf-event-body__program">';
		echo '<h3>' . esc_html__( 'Program', 'campsflow' ) . '</h3>';
		echo wp_kses_post( $program );
		echo '</div>';
	}

	/** @param array<string,mixed>|null $desc */
	private function echoPriceInclude( ?array $desc ): void {
		$html = is_array( $desc ) ? (string) ( $desc['priceInclude'] ?? '' ) : '';
		if ( ! $html ) {
			return;
		}
		echo '<div class="cf-event-body__price-include">';
		echo '<h3>' . esc_html__( 'Co zawiera cena', 'campsflow' ) . '</h3>';
		echo wp_kses_post( $html );
		echo '</div>';
	}

	private function echoDocuments( int $postId ): void {
		$docs = json_decode( (string) get_post_meta( $postId, 'cf_documents', true ), true );
		if ( ! is_array( $docs ) || empty( $docs ) ) {
			return;
		}
		echo '<div class="cf-event-body__documents">';
		echo '<h3>' . esc_html__( 'Dokumenty', 'campsflow' ) . '</h3><ul>';
		foreach ( $docs as $doc ) {
			if ( ! is_array( $doc ) || empty( $doc['url'] ) ) {
				continue;
			}
			$name = esc_html( (string) ( $doc['name'] ?? $doc['url'] ) );
			echo '<li><a href="' . esc_url( (string) $doc['url'] ) . '" target="_blank" rel="noopener">';
			echo '<span class="dashicons dashicons-media-document"></span> ' . $name . '</a></li>';
		}
		echo '</ul></div>';
	}

	private function echoTerms( int $postId ): void {
		$terms = json_decode( (string) get_post_meta( $postId, 'cf_general_terms', true ), true );
		if ( ! is_array( $terms ) ) {
			return;
		}
		$fields = array(
			'insurance'                 => __( 'Ubezpieczenie', 'campsflow' ),
			'drugOrdering'              => __( 'Zamawianie leków', 'campsflow' ),
			'specialDiet'               => __( 'Dieta specjalna', 'campsflow' ),
			'deadlinesAndDocumentsInfo' => __( 'Terminy i dokumenty', 'campsflow' ),
		);
		$parts  = array();
		foreach ( $fields as $key => $label ) {
			if ( ! empty( $terms[ $key ] ) ) {
				$parts[] = '<dt>' . esc_html( $label ) . '</dt><dd>' . wp_kses_post( (string) $terms[ $key ] ) . '</dd>';
			}
		}
		if ( ! $parts ) {
			return;
		}
		echo '<div class="cf-event-body__terms"><h3>' . esc_html__( 'Warunki ogólne', 'campsflow' ) . '</h3>';
		echo '<dl>' . implode( '', $parts ) . '</dl></div>';
	}

	private function echoInstructions( int $postId ): void {
		$inst = json_decode( (string) get_post_meta( $postId, 'cf_instructions', true ), true );
		if ( ! is_array( $inst ) ) {
			return;
		}
		$howTo = is_string( $inst['howToPrepare'] ?? null ) ? $inst['howToPrepare'] : '';
		$what  = is_string( $inst['whatToTake'] ?? null ) ? $inst['whatToTake'] : '';
		if ( ! $howTo && ! $what ) {
			return;
		}
		echo '<div class="cf-event-body__instructions">';
		echo '<h3>' . esc_html__( 'Informacje praktyczne', 'campsflow' ) . '</h3>';
		if ( $howTo ) {
			echo '<h4>' . esc_html__( 'Jak się przygotować', 'campsflow' ) . '</h4>' . wp_kses_post( $howTo );
		}
		if ( $what ) {
			echo '<h4>' . esc_html__( 'Co zabrać', 'campsflow' ) . '</h4>' . wp_kses_post( $what );
		}
		echo '</div>';
	}

	private function echoContact( int $postId ): void {
		$c = json_decode( (string) get_post_meta( $postId, 'cf_contact', true ), true );
		if ( ! is_array( $c ) ) {
			return;
		}
		$name  = trim( (string) ( $c['firstname'] ?? '' ) . ' ' . (string) ( $c['lastname'] ?? '' ) );
		$email = (string) ( $c['email'] ?? '' );
		$phone = (string) ( $c['phone'] ?? '' );
		if ( ! $name && ! $email && ! $phone ) {
			return;
		}
		echo '<div class="cf-event-body__contact">';
		echo '<h3>' . esc_html__( 'Kontakt', 'campsflow' ) . '</h3>';
		if ( $name ) {
			echo '<div class="cf-contact__name">' . esc_html( $name ) . '</div>';
		}
		if ( $email ) {
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
		if ( $phone ) {
			echo '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
		}
		echo '</div>';
	}

	// ── Session helpers ───────────────────────────────────────────────────────

	private function echoSessionItem( int $sId, string $buttonLabel, bool $showMeetingPoints, bool $showCustomFields = false ): void {
		$dateFrom   = (string) get_post_meta( $sId, 'cf_date_from', true );
		$dateTo     = (string) get_post_meta( $sId, 'cf_date_to', true );
		$price      = (int) get_post_meta( $sId, 'cf_price_from', true );
		$days       = (int) get_post_meta( $sId, 'cf_number_of_days', true );
		$name       = (string) get_post_meta( $sId, 'cf_turnus_name', true );
		$reservUrl  = (string) get_post_meta( $sId, 'cf_reservation_url', true );
		$transport  = json_decode( (string) get_post_meta( $sId, 'cf_transport', true ), true );
		$bucket     = AvailabilityBucket::tryFrom( (string) get_post_meta( $sId, 'cf_availability', true ) )
						?? AvailabilityBucket::Available;
		$isFull     = $bucket === AvailabilityBucket::Full;
		$f          = $dateFrom ? date_create( $dateFrom ) : null;
		$t          = $dateTo ? date_create( $dateTo ) : null;
		$dateLabel  = $f ? ( $f->format( 'j M' ) . ( $t ? '–' . $t->format( 'j M Y' ) : '' ) ) : '';
		$priceLabel = $price ? 'od ' . number_format( $price / 100, 0, ',', ' ' ) . ' zł' : '';
		$tDesc      = is_array( $transport ) ? (string) ( $transport['description'] ?? '' ) : '';
		$tType      = is_array( $transport ) ? (string) ( $transport['type'] ?? 'own' ) : 'own';

		echo '<li class="cf-sessions-box__item' . ( $isFull ? ' cf-sessions-box__item--full' : '' ) . '">';
		if ( $name ) {
			echo '<div class="cf-sessions-box__name">' . esc_html( $name ) . '</div>';
		}
		echo '<div class="cf-sessions-box__dates">' . esc_html( $dateLabel );
		if ( $days > 0 ) {
			echo ' <span class="cf-sessions-box__days">(' . esc_html( (string) $days ) . ' ' . esc_html__( 'dni', 'campsflow' ) . ')</span>';
		}
		echo '</div>';
		if ( $tType !== 'own' && $tDesc ) {
			echo '<div class="cf-sessions-box__transport"><span class="dashicons dashicons-car"></span> ' . esc_html( $tDesc ) . '</div>';
		}
		if ( $showMeetingPoints ) {
			$this->echoMeetingPoints( $sId );
		}
		if ( $showCustomFields ) {
			$this->echoCustomFields( $sId );
		}
		echo '<div class="cf-sessions-box__meta">';
		if ( $priceLabel ) {
			echo '<span class="cf-sessions-box__price">' . esc_html( $priceLabel ) . '</span>';
		}
		if ( $bucket !== AvailabilityBucket::Available && $bucket->label() ) {
			echo '<span class="cf-badge cf-badge--' . esc_attr( $bucket->value ) . '">' . esc_html( $bucket->label() ) . '</span>';
		}
		echo '</div>';
		if ( $isFull ) {
			echo '<span class="cf-btn cf-btn--disabled">' . esc_html__( 'Brak miejsc', 'campsflow' ) . '</span>';
		} elseif ( $reservUrl ) {
			echo '<a class="cf-btn" href="' . esc_url( $reservUrl ) . '" target="_blank" rel="noopener">' . esc_html( $buttonLabel ) . '</a>';
		}
		echo '</li>';
	}

	private function echoCustomFields( int $postId ): void {
		$fields = json_decode( (string) get_post_meta( $postId, 'cf_custom_fields', true ), true );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return;
		}
		echo '<dl class="cf-custom-fields">';
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['key'] ) ) {
				continue;
			}
			echo '<dt class="cf-custom-fields__label">' . esc_html( (string) ( $field['label'] ?? $field['key'] ) ) . '</dt>';
			echo '<dd class="cf-custom-fields__value">' . $this->renderCustomFieldValue( $field ) . '</dd>';
		}
		echo '</dl>';
	}

	/** @param array<string,mixed> $field */
	private function renderCustomFieldValue( array $field ): string {
		$type  = (string) ( $field['type'] ?? 'text' );
		$value = $field['value'] ?? null;
		switch ( $type ) {
			case 'html':
				return wp_kses_post( (string) $value );
			case 'number':
				return esc_html( is_numeric( $value ) ? number_format( (float) $value, 2, ',', ' ' ) : '' );
			case 'date':
				$d = $value ? date_create( (string) $value ) : null;
				return esc_html( $d ? $d->format( 'd.m.Y' ) : (string) $value );
			case 'boolean':
				return esc_html( $value ? __( 'Tak', 'campsflow' ) : __( 'Nie', 'campsflow' ) );
			default:
				return esc_html( (string) $value );
		}
	}

	private function echoMeetingPoints( int $sId ): void {
		$start = json_decode( (string) get_post_meta( $sId, 'cf_meeting_points_start', true ), true );
		if ( ! is_array( $start ) || empty( $start ) ) {
			return;
		}
		echo '<div class="cf-meeting-points">';
		echo '<strong class="cf-meeting-points__label">' . esc_html__( 'Zbiórka', 'campsflow' ) . ':</strong><ul>';
		foreach ( array_slice( $start, 0, 5 ) as $mp ) {
			if ( ! is_array( $mp ) ) {
				continue;
			}
			$mpName    = (string) ( $mp['name'] ?? '' );
			$mpAddress = (string) ( $mp['address'] ?? '' );
			$mpDate    = (string) ( $mp['date'] ?? '' );
			$mpHour    = (string) ( $mp['hour'] ?? '' );
			$mpGps     = is_array( $mp['gps'] ?? null ) ? $mp['gps'] : null;
			$addrLabel = implode( ', ', array_filter( array( $mpName, $mpAddress ) ) );
			$timeLabel = implode( ' ', array_filter( array( $mpDate, $mpHour ) ) );
			echo '<li class="cf-mp__item">';
			if ( $timeLabel ) {
				echo '<span class="cf-mp__time">' . esc_html( $timeLabel ) . '</span> ';
			}
			echo '<span class="cf-mp__place">' . esc_html( $addrLabel ) . '</span>';
			if ( $mpGps && isset( $mpGps['lat'], $mpGps['lng'] ) ) {
				$url = 'https://www.google.com/maps?q=' . $mpGps['lat'] . ',' . $mpGps['lng'];
				echo ' <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Mapa', 'campsflow' ) . '</a>';
			}
			echo '</li>';
		}
		echo '</ul></div>';
	}
}
