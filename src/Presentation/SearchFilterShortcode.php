<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * [campsflow_search_filter fields="category,age,destination,transport,child_age,dates"]
 * Fields order follows the `fields` attribute; all shown by default.
 */
final class SearchFilterShortcode {
	use FilterRenderMethods;

	private const ALL_FIELDS = array( 'category', 'age', 'destination', 'transport', 'child_age', 'dates' );

	public function register(): void {
		add_shortcode( 'campsflow_search_filter', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array( 'fields' => implode( ',', self::ALL_FIELDS ) ),
			is_array( $atts ) ? $atts : array(),
			'campsflow_search_filter'
		);

		$fields   = array_map( 'trim', explode( ',', $atts['fields'] ) );
		$endpoint = rest_url( 'campsflow/v1/events' );

		ob_start();
		echo '<form class="cf-search-form cf-filters" method="get" action="" data-endpoint="' . esc_url( $endpoint ) . '">';

		foreach ( $fields as $field ) {
			match ( $field ) {
				'category'  => $this->renderTaxFilterSelect( 'cf_event_category', 'category', __( 'Wszystkie profile', 'campsflow' ) ),
				'age'       => $this->renderTaxFilterSelect( 'cf_age_group', 'age', __( 'Wszystkie grupy wiekowe', 'campsflow' ) ),
				'destination' => $this->renderDestinationFilterSelect( __( 'Wszystkie kierunki', 'campsflow' ) ),
				'transport' => $this->renderTaxFilterSelect( 'cf_transport_type', 'transport', __( 'Transport', 'campsflow' ) ),
				'child_age' => $this->renderChildAgeFilterSelect( __( 'Wiek', 'campsflow' ) ),
				'dates'     => $this->renderDateInputs(),
				default     => null,
			};
		}

		echo '</form>';
		return (string) ob_get_clean();
	}

	private function renderDateInputs(): void {
		$from = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$to   = sanitize_text_field( $_GET['dateTo'] ?? '' );
		echo '<input class="cf-filter" type="date" name="dateFrom" value="' . esc_attr( $from ) . '">';
		echo '<input class="cf-filter" type="date" name="dateTo" value="' . esc_attr( $to ) . '">';
	}
}
