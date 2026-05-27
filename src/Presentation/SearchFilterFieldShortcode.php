<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * [campsflow_search_filter_field field="category" header="" placeholder=""]
 */
final class SearchFilterFieldShortcode {
	use FilterRenderMethods;

	public function register(): void {
		add_shortcode( 'campsflow_search_filter_field', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'field'       => 'category',
				'header'      => '',
				'placeholder' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_search_filter_field'
		);

		$field       = sanitize_key( (string) $atts['field'] );
		$header      = sanitize_text_field( (string) $atts['header'] );
		$placeholder = sanitize_text_field( (string) $atts['placeholder'] );

		ob_start();

		if ( $header !== '' ) {
			echo '<label class="cf-filter-label">' . esc_html( $header ) . '</label>';
		}

		switch ( $field ) {
			case 'category':
				$label = $placeholder !== '' ? $placeholder : __( 'Wszystkie profile', 'campsflow' );
				$this->renderTaxFilterSelect( 'cf_event_category', 'category', $label );
				break;
			case 'age':
				$label = $placeholder !== '' ? $placeholder : __( 'Wszystkie grupy wiekowe', 'campsflow' );
				$this->renderTaxFilterSelect( 'cf_age_group', 'age', $label );
				break;
			case 'child_age':
				$label = $placeholder !== '' ? $placeholder : __( 'Wiek', 'campsflow' );
				$this->renderChildAgeFilterSelect( $label );
				break;
			case 'destination':
				$label = $placeholder !== '' ? $placeholder : __( 'Wszystkie kierunki', 'campsflow' );
				$this->renderDestinationFilterSelect( $label );
				break;
			case 'transport':
				$label = $placeholder !== '' ? $placeholder : __( 'Transport', 'campsflow' );
				$this->renderTaxFilterSelect( 'cf_transport_type', 'transport', $label );
				break;
			case 'dates':
				$this->renderDateRangePicker( __( 'Termin', 'campsflow' ) );
				break;
		}

		return (string) ob_get_clean();
	}
}
