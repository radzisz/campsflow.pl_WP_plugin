<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Elementor widget: event meta (location, tags, age groups, description + rich sections).
 * Works on any page where a cf_event post is the context.
 */
final class EventMetaWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_meta';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Szczegóły wydarzenia', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-info-circle';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleSection();
	}

	private function registerContentSection(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Widoczność sekcji', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$yes      = __( 'Tak', 'campsflow' );
		$no       = __( 'Nie', 'campsflow' );
		$switcher = Controls_Manager::SWITCHER;
		$this->add_control(
			'show_photos',
			array(
				'label'     => __( 'Zdjęcia', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_location',
			array(
				'label'     => __( 'Lokalizacja', 'campsflow' ),
				'type'      => $switcher,
				'default'   => 'yes',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_tags',
			array(
				'label'     => __( 'Tagi', 'campsflow' ),
				'type'      => $switcher,
				'default'   => 'yes',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_description',
			array(
				'label'     => __( 'Opis ogólny', 'campsflow' ),
				'type'      => $switcher,
				'default'   => 'yes',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_program',
			array(
				'label'     => __( 'Program', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_price_include',
			array(
				'label'     => __( 'Co zawiera cena', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_documents',
			array(
				'label'     => __( 'Dokumenty', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_terms',
			array(
				'label'     => __( 'Warunki ogólne', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_instructions',
			array(
				'label'     => __( 'Inf. praktyczne', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_contact',
			array(
				'label'     => __( 'Kontakt', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->add_control(
			'show_custom_fields',
			array(
				'label'     => __( 'Pola własne', 'campsflow' ),
				'type'      => $switcher,
				'default'   => '',
				'label_on'  => $yes,
				'label_off' => $no,
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleSection(): void {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Styl', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'accent_color',
			array(
				'label'     => __( 'Kolor akcentu (tagi)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array( '{{WRAPPER}} .cf-tag' => 'background: {{VALUE}}20; color: {{VALUE}}' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'location_typography',
				'label'    => __( 'Lokalizacja', 'campsflow' ),
				'selector' => '{{WRAPPER}} .cf-event-body__location',
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'label'    => __( 'Opis', 'campsflow' ),
				'selector' => '{{WRAPPER}} .cf-event-body__description',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s      = $this->get_settings_for_display();
		$postId = (int) get_the_ID();

		if ( ! $postId ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p style="color:#999">' . esc_html__( '[Podgląd — otwórz na stronie wydarzenia]', 'campsflow' ) . '</p>';
			}
			return;
		}

		$loc  = json_decode( (string) get_post_meta( $postId, 'cf_localization', true ), true );
		$desc = json_decode( (string) get_post_meta( $postId, 'cf_description', true ), true );

		echo '<div class="cf-event-body">';
		if ( $s['show_photos'] === 'yes' ) {
			$this->echoPhotos( $postId );
		}
		if ( $s['show_location'] === 'yes' ) {
			$this->echoLocation( $loc );
		}
		if ( $s['show_tags'] === 'yes' ) {
			$this->echoTags( $postId );
		}
		if ( $s['show_description'] === 'yes' ) {
			$this->echoDescription( $postId );
		}
		if ( $s['show_program'] === 'yes' ) {
			$this->echoProgram( $desc );
		}
		if ( $s['show_price_include'] === 'yes' ) {
			$this->echoPriceInclude( $desc );
		}
		if ( $s['show_documents'] === 'yes' ) {
			$this->echoDocuments( $postId );
		}
		if ( $s['show_terms'] === 'yes' ) {
			$this->echoTerms( $postId );
		}
		if ( $s['show_instructions'] === 'yes' ) {
			$this->echoInstructions( $postId );
		}
		if ( $s['show_contact'] === 'yes' ) {
			$this->echoContact( $postId );
		}
		if ( $s['show_custom_fields'] === 'yes' ) {
			$this->echoCustomFields( $postId );
		}
		echo '</div>';
	}

	// ── Render helpers ────────────────────────────────────────────────────────

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
		echo '<div class="cf-event-body__description">' . wp_kses_post( apply_filters( 'the_content', $content ) ) . '</div>';
	}

	/** @param array<string,mixed>|null $desc */
	private function echoProgram( ?array $desc ): void {
		$html = is_array( $desc ) ? (string) ( $desc['program'] ?? '' ) : '';
		if ( ! $html ) {
			return;
		}
		echo '<div class="cf-event-body__program"><h3>' . esc_html__( 'Program', 'campsflow' ) . '</h3>' . wp_kses_post( $html ) . '</div>';
	}

	/** @param array<string,mixed>|null $desc */
	private function echoPriceInclude( ?array $desc ): void {
		$html = is_array( $desc ) ? (string) ( $desc['priceInclude'] ?? '' ) : '';
		if ( ! $html ) {
			return;
		}
		echo '<div class="cf-event-body__price-include"><h3>' . esc_html__( 'Co zawiera cena', 'campsflow' ) . '</h3>' . wp_kses_post( $html ) . '</div>';
	}

	private function echoDocuments( int $postId ): void {
		$docs = json_decode( (string) get_post_meta( $postId, 'cf_documents', true ), true );
		if ( ! is_array( $docs ) || empty( $docs ) ) {
			return;
		}
		echo '<div class="cf-event-body__documents"><h3>' . esc_html__( 'Dokumenty', 'campsflow' ) . '</h3><ul>';
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
		echo '<div class="cf-event-body__terms"><h3>' . esc_html__( 'Warunki ogólne', 'campsflow' ) . '</h3><dl>' . implode( '', $parts ) . '</dl></div>';
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
		echo '<div class="cf-event-body__instructions"><h3>' . esc_html__( 'Informacje praktyczne', 'campsflow' ) . '</h3>';
		if ( $howTo ) {
			echo '<h4>' . esc_html__( 'Jak się przygotować', 'campsflow' ) . '</h4>' . wp_kses_post( $howTo );
		}
		if ( $what ) {
			echo '<h4>' . esc_html__( 'Co zabrać', 'campsflow' ) . '</h4>' . wp_kses_post( $what );
		}
		echo '</div>';
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
		echo '<div class="cf-event-body__contact"><h3>' . esc_html__( 'Kontakt', 'campsflow' ) . '</h3>';
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
}
