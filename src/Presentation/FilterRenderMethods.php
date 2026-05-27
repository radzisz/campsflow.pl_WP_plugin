<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * Shared select/input renderers for search filter widgets.
 * Each method echoes directly (no return) so it works inside ob_start contexts.
 */
trait FilterRenderMethods {

	private function renderTaxFilterSelect( string $taxonomy, string $paramName, string $emptyLabel ): void {
		$terms   = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);
		$current = sanitize_text_field( $_GET[ $paramName ] ?? '' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		echo '<select class="cf-filter" name="' . esc_attr( $paramName ) . '">';
		echo '<option value="">' . esc_html( $emptyLabel ) . '</option>';
		foreach ( $terms as $term ) {
			assert( is_object( $term ) && isset( $term->slug, $term->name ) );
			$selected = selected( $current, $term->slug, false );
			echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>'
				. esc_html( $term->name ) . '</option>';
		}
		echo '</select>';
	}

	private function renderDestinationFilterSelect( string $emptyLabel ): void {
		$terms   = get_terms(
			array(
				'taxonomy'   => 'cf_destination',
				'hide_empty' => true,
			)
		);
		$current = sanitize_text_field( $_GET['destination'] ?? '' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$leaves = array_filter(
			is_array( $terms ) ? $terms : iterator_to_array( $terms ),
			static fn( $t ) => is_object( $t ) && $t->parent > 0
		);

		if ( empty( $leaves ) ) {
			return;
		}

		echo '<select class="cf-filter" name="destination">';
		echo '<option value="">' . esc_html( $emptyLabel ) . '</option>';
		foreach ( $leaves as $dest ) {
			assert( is_object( $dest ) && isset( $dest->slug, $dest->name ) );
			$selected = selected( $current, $dest->slug, false );
			echo '<option value="' . esc_attr( $dest->slug ) . '"' . $selected . '>'
				. esc_html( $dest->name ) . '</option>';
		}
		echo '</select>';
	}

	private function renderChildAgeFilterSelect( string $emptyLabel ): void {
		$current = absint( $_GET['childAge'] ?? 0 );

		echo '<select class="cf-filter" name="childAge">';
		echo '<option value="">' . esc_html( $emptyLabel ) . '</option>';
		for ( $yr = 4; $yr <= 17; $yr++ ) {
			$selected = selected( $current, $yr, false );
			echo '<option value="' . esc_attr( (string) $yr ) . '"' . $selected . '>'
				. esc_html( sprintf( _n( '%d rok', '%d lat', $yr, 'campsflow' ), $yr ) ) . '</option>';
		}
		echo '<option value="18"' . selected( $current, 18, false ) . '>18+</option>';
		echo '</select>';
	}
}
