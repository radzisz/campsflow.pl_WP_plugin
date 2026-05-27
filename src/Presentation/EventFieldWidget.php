<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\Widget\FieldValueRenderer;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class EventFieldWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_field';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Pole wydarzenia', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-text';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	public function get_keywords(): array {
		return array( 'pole', 'wydarzenie', 'campsflow', 'event', 'field' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleLabelSection();
		$this->registerStyleValueSection();
	}

	private function registerContentSection(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Zawartość', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'field',
			array(
				'label'   => __( 'Pole', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post_title',
				'options' => array(
					'post_title'            => __( 'Tytuł wydarzenia', 'campsflow' ),
					'cf_reservation_url'    => __( 'URL rezerwacji', 'campsflow' ),
					'cf_lead_image_url'     => __( 'URL zdjęcia głównego (surowy)', 'campsflow' ),
					'cf_lead_video_url'     => __( 'URL wideo głównego (surowy)', 'campsflow' ),
					'cf_desc_general'       => __( 'Opis ogólny', 'campsflow' ),
					'cf_desc_program'       => __( 'Program', 'campsflow' ),
					'cf_desc_price_include' => __( 'Co w cenie', 'campsflow' ),
					'cf_instr_prepare'      => __( 'Jak się przygotować', 'campsflow' ),
					'cf_instr_take'         => __( 'Co zabrać', 'campsflow' ),
					'cf_loc_name'           => __( 'Lokalizacja: nazwa miejsca', 'campsflow' ),
					'cf_loc_destination'    => __( 'Lokalizacja: miejscowość docelowa', 'campsflow' ),
					'cf_loc_city'           => __( 'Lokalizacja: miasto', 'campsflow' ),
					'cf_loc_street'         => __( 'Lokalizacja: ulica', 'campsflow' ),
					'cf_loc_code'           => __( 'Lokalizacja: kod pocztowy', 'campsflow' ),
					'cf_loc_phone'          => __( 'Lokalizacja: telefon', 'campsflow' ),
					'cf_loc_email'          => __( 'Lokalizacja: e-mail', 'campsflow' ),
					'cf_loc_webpage'        => __( 'Lokalizacja: strona www', 'campsflow' ),
					'cf_terms_insurance'    => __( 'Warunki: ubezpieczenie', 'campsflow' ),
					'cf_terms_drug'         => __( 'Warunki: zamawianie leków', 'campsflow' ),
					'cf_terms_diet'         => __( 'Warunki: dieta specjalna', 'campsflow' ),
					'cf_terms_deadlines'    => __( 'Warunki: terminy i dokumenty', 'campsflow' ),
					'custom'                => __( 'Pole własne', 'campsflow' ),
				),
			)
		);

		$this->add_control(
			'custom_key',
			array(
				'label'     => __( 'Wybierz pole własne', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $this->buildCustomFieldOptions(),
				'condition' => array( 'field' => 'custom' ),
			)
		);

		$this->add_control(
			'render_mode',
			array(
				'label'     => __( 'Tryb renderowania', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'auto',
				'options'   => array(
					'auto' => __( 'Auto (wykryj HTML)', 'campsflow' ),
					'text' => __( 'Tekst (uciecz HTML)', 'campsflow' ),
					'html' => __( 'HTML (renderuj znaczniki)', 'campsflow' ),
				),
				'condition' => array( 'field!' => 'custom' ),
			)
		);

		$this->add_control(
			'show_label',
			array(
				'label'     => __( 'Pokaż nagłówek', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);

		$this->add_control(
			'label_text',
			array(
				'label'     => __( 'Tekst nagłówka', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => array( 'show_label' => 'yes' ),
			)
		);

		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy pole jest puste.', 'campsflow' ),
			)
		);

		$this->end_controls_section();
	}

	private function registerStyleLabelSection(): void {
		$this->start_controls_section(
			'section_style_label',
			array(
				'label'     => __( 'Nagłówek pola', 'campsflow' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_label' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .cf-field__label',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Kolor nagłówka', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-field__label' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	private function registerStyleValueSection(): void {
		$this->start_controls_section(
			'section_style_value',
			array(
				'label' => __( 'Wartość pola', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typography',
				'selector' => '{{WRAPPER}} .cf-field__value',
			)
		);

		$this->add_control(
			'value_color',
			array(
				'label'     => __( 'Kolor wartości', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-field__value' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$postId      = (int) get_the_ID();
		$field       = (string) ( $s['field'] ?? 'post_title' );
		$customKey   = (string) ( $s['custom_key'] ?? '' );
		$renderMode  = (string) ( $s['render_mode'] ?? 'auto' );
		$showLabel   = ( $s['show_label'] ?? '' ) === 'yes';
		$labelText   = sanitize_text_field( (string) ( $s['label_text'] ?? '' ) );
		$placeholder = sanitize_text_field( (string) ( $s['editor_placeholder'] ?? '' ) );
		$isEdit      = \Elementor\Plugin::$instance->editor->is_edit_mode();

		$value = $postId ? $this->resolveFieldValue( $postId, $field, $customKey ) : '';

		if ( '' === $value ) {
			if ( $isEdit && '' !== $placeholder ) {
				echo '<div class="cf-field">';
				if ( $showLabel && '' !== $labelText ) {
					echo '<span class="cf-field__label">' . esc_html( $labelText ) . '</span>';
				}
				echo '<p class="cf-field__value cf-field--placeholder">' . esc_html( $placeholder ) . '</p>';
				echo '</div>';
			}
			return;
		}

		echo '<div class="cf-field">';
		if ( $showLabel && '' !== $labelText ) {
			echo '<span class="cf-field__label">' . esc_html( $labelText ) . '</span>';
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="cf-field__value">' . $this->renderValue( $value, $renderMode ) . '</div>';
		echo '</div>';
	}

	/** @return array<string,string> */
	private function buildCustomFieldOptions(): array {
		$postId = (int) get_the_ID();
		if ( ! $postId ) {
			return array( '' => __( '(otwórz na stronie wydarzenia)', 'campsflow' ) );
		}
		$raw    = (string) get_post_meta( $postId, 'cf_custom_fields', true );
		$fields = json_decode( $raw, true );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return array( '' => __( '(brak pól własnych w tym wydarzeniu)', 'campsflow' ) );
		}
		$options = array( '' => __( '— wybierz pole —', 'campsflow' ) );
		foreach ( $fields as $item ) {
			if ( ! is_array( $item ) || empty( $item['key'] ) ) {
				continue;
			}
			$label                            = sanitize_text_field( (string) ( $item['label'] ?? $item['key'] ) );
			$options[ (string) $item['key'] ] = $label;
		}
		return $options;
	}

	private function resolveFieldValue( int $postId, string $field, string $customKey ): string {
		if ( 'post_title' === $field ) {
			return (string) get_the_title( $postId );
		}
		if ( in_array( $field, array( 'cf_reservation_url', 'cf_lead_image_url', 'cf_lead_video_url' ), true ) ) {
			return (string) get_post_meta( $postId, $field, true );
		}
		if ( str_starts_with( $field, 'cf_desc_' ) ) {
			return $this->resolveDescriptionSubfield( $postId, $field );
		}
		if ( str_starts_with( $field, 'cf_instr_' ) ) {
			return $this->resolveInstructionSubfield( $postId, $field );
		}
		if ( str_starts_with( $field, 'cf_loc_' ) ) {
			return $this->resolveLocalizationSubfield( $postId, $field );
		}
		if ( str_starts_with( $field, 'cf_terms_' ) ) {
			return $this->resolveGeneralTermsSubfield( $postId, $field );
		}
		if ( 'custom' === $field ) {
			return $this->resolveCustomField( $postId, $customKey );
		}
		return '';
	}

	private function resolveDescriptionSubfield( int $postId, string $fieldKey ): string {
		$raw  = (string) get_post_meta( $postId, 'cf_description', true );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return '';
		}
		$map = array(
			'cf_desc_general'       => 'general',
			'cf_desc_program'       => 'program',
			'cf_desc_price_include' => 'priceInclude',
		);
		return (string) ( $data[ $map[ $fieldKey ] ?? '' ] ?? '' );
	}

	private function resolveInstructionSubfield( int $postId, string $fieldKey ): string {
		$raw  = (string) get_post_meta( $postId, 'cf_instructions', true );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return '';
		}
		$map = array(
			'cf_instr_prepare' => 'howToPrepare',
			'cf_instr_take'    => 'whatToTake',
		);
		return (string) ( $data[ $map[ $fieldKey ] ?? '' ] ?? '' );
	}

	private function resolveLocalizationSubfield( int $postId, string $fieldKey ): string {
		$raw  = (string) get_post_meta( $postId, 'cf_localization', true );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return '';
		}
		$addr = is_array( $data['address'] ?? null ) ? $data['address'] : array();
		$map  = array(
			'cf_loc_name'        => (string) ( $data['name'] ?? '' ),
			'cf_loc_destination' => (string) ( $data['destination'] ?? '' ),
			'cf_loc_city'        => (string) ( $addr['city'] ?? '' ),
			'cf_loc_street'      => (string) ( $addr['street'] ?? '' ),
			'cf_loc_code'        => (string) ( $addr['code'] ?? '' ),
			'cf_loc_phone'       => (string) ( $data['phone'] ?? '' ),
			'cf_loc_email'       => (string) ( $data['email'] ?? '' ),
			'cf_loc_webpage'     => (string) ( $data['webpage'] ?? '' ),
		);
		return $map[ $fieldKey ] ?? '';
	}

	private function resolveGeneralTermsSubfield( int $postId, string $fieldKey ): string {
		$raw  = (string) get_post_meta( $postId, 'cf_general_terms', true );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return '';
		}
		$map = array(
			'cf_terms_insurance' => 'insurance',
			'cf_terms_drug'      => 'drugOrdering',
			'cf_terms_diet'      => 'specialDiet',
			'cf_terms_deadlines' => 'deadlinesAndDocumentsInfo',
		);
		return (string) ( $data[ $map[ $fieldKey ] ?? '' ] ?? '' );
	}

	private function resolveCustomField( int $postId, string $customKey ): string {
		if ( '' === $customKey ) {
			return '';
		}
		$raw    = (string) get_post_meta( $postId, 'cf_custom_fields', true );
		$fields = json_decode( $raw, true );
		if ( ! is_array( $fields ) ) {
			return '';
		}
		foreach ( $fields as $item ) {
			if ( ! is_array( $item ) || ( $item['key'] ?? '' ) !== $customKey ) {
				continue;
			}
			return (string) ( $item['value'] ?? '' );
		}
		return '';
	}

	private function renderValue( string $value, string $mode ): string {
		return ( new FieldValueRenderer() )->applyRenderMode( $value, $mode );
	}
}
