<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\Widget\FieldValueRenderer;

/**
 * [campsflow_event_field field="cf_loc_city" render_mode="auto" show_label="0" label=""]
 */
final class EventFieldShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_event_field', array( $this, 'render' ) );
	}

	/** @param array<string,string>|string $atts */
	public function render( array|string $atts ): string {
		$atts   = shortcode_atts(
			array(
				'field'       => 'post_title',
				'custom_key'  => '',
				'render_mode' => 'auto',
				'show_label'  => '0',
				'label'       => '',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_field'
		);
		$postId = (int) get_the_ID();
		if ( ! $postId ) {
			return '';
		}
		$field      = sanitize_key( $atts['field'] );
		$customKey  = sanitize_key( $atts['custom_key'] );
		$renderMode = sanitize_key( $atts['render_mode'] );
		$showLabel  = '1' === $atts['show_label'];
		$labelText  = sanitize_text_field( $atts['label'] );

		$value = $this->resolveFieldValue( $postId, $field, $customKey );
		if ( '' === $value ) {
			return '';
		}

		$rendered = ( new FieldValueRenderer() )->applyRenderMode( $value, $renderMode );
		$out      = '<div class="cf-field">';
		if ( $showLabel && '' !== $labelText ) {
			$out .= '<span class="cf-field__label">' . esc_html( $labelText ) . '</span>';
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$out .= '<div class="cf-field__value">' . $rendered . '</div>';
		$out .= '</div>';
		return $out;
	}

	// ── Field resolution ──────────────────────────────────────────────────────

	private function resolveFieldValue( int $postId, string $field, string $customKey ): string {
		if ( 'post_title' === $field ) {
			return (string) get_the_title( $postId );
		}
		if ( in_array( $field, array( 'cf_reservation_url', 'cf_lead_image_url', 'cf_lead_video_url', 'cf_event_class' ), true ) ) {
			return (string) get_post_meta( $postId, $field, true );
		}
		if ( 'cf_event_process' === $field ) {
			return (string) get_post_meta( $postId, 'cf_event_process_name', true );
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
		$data = json_decode( (string) get_post_meta( $postId, 'cf_description', true ), true );
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
		$data = json_decode( (string) get_post_meta( $postId, 'cf_instructions', true ), true );
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
		$data = json_decode( (string) get_post_meta( $postId, 'cf_localization', true ), true );
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
		$data = json_decode( (string) get_post_meta( $postId, 'cf_general_terms', true ), true );
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
		$fields = json_decode( (string) get_post_meta( $postId, 'cf_custom_fields', true ), true );
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
}
