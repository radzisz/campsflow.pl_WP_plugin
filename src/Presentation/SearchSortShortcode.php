<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * [campsflow_search_sort header="" placeholder="" default=""]
 */
final class SearchSortShortcode {

	/** @var array<string, string> */
	private const SORT_OPTIONS = array(
		'title_asc'  => 'Nazwa A-Z',
		'title_desc' => 'Nazwa Z-A',
		'date_asc'   => 'Termin: najwcześniejszy',
		'date_desc'  => 'Termin: najpóźniejszy',
		'price_asc'  => 'Cena: od najtańszej',
		'price_desc' => 'Cena: od najdroższej',
	);

	public function register(): void {
		add_shortcode( 'campsflow_search_sort', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'header'      => '',
				'placeholder' => '',
				'default'     => '',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_search_sort'
		);

		$header      = sanitize_text_field( (string) $atts['header'] );
		$placeholder = sanitize_text_field( (string) $atts['placeholder'] );
		$default     = sanitize_key( (string) $atts['default'] );
		$current     = sanitize_text_field( $_GET['sort'] ?? $default );

		ob_start();

		if ( $header !== '' ) {
			echo '<label class="cf-filter-label">' . esc_html( $header ) . '</label>';
		}

		$emptyLabel = $placeholder !== '' ? $placeholder : __( 'Sortuj według', 'campsflow' );
		echo '<select class="cf-filter" name="sort">';
		echo '<option value="">' . esc_html( $emptyLabel ) . '</option>';
		foreach ( self::SORT_OPTIONS as $value => $label ) {
			$selected = selected( $current, $value, false );
			echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		return (string) ob_get_clean();
	}
}
