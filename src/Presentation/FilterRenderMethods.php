<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * Shared select/input renderers for search filter widgets.
 * Each method echoes directly (no return) so it works inside ob_start contexts.
 */
trait FilterRenderMethods {

	private function renderTaxFilterSelect( string $taxonomy, string $paramName, string $emptyLabel ): void {
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);
		$selected = self::parseMultiParam( $paramName );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		echo '<select class="cf-filter" name="' . esc_attr( $paramName ) . '" multiple>';
		foreach ( $terms as $term ) {
			assert( is_object( $term ) && isset( $term->slug, $term->name ) );
			$isSel = in_array( $term->slug, $selected, true ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( $term->slug ) . '"' . $isSel . '>'
				. esc_html( $term->name ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * @return string[]
	 */
	private static function parseMultiParam( string $paramName ): array {
		$raw = sanitize_text_field( (string) ( $_GET[ $paramName ] ?? '' ) );
		if ( $raw === '' ) {
			return array();
		}
		return array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
	}

	private function renderDestinationFilterSelect( string $emptyLabel ): void {
		$rawTerms = get_terms(
			array(
				'taxonomy'   => 'cf_destination',
				'hide_empty' => true,
			)
		);
		$selected = self::parseMultiParam( 'destination' );

		if ( is_wp_error( $rawTerms ) || empty( $rawTerms ) ) {
			return;
		}

		[ $parents, $byParent ] = $this->buildDestinationTree(
			is_array( $rawTerms ) ? $rawTerms : iterator_to_array( $rawTerms )
		);

		if ( empty( $parents ) ) {
			return;
		}

		echo '<select class="cf-filter" name="destination" multiple>';

		foreach ( $parents as $pid => $parent ) {
			assert( $parent instanceof \WP_Term );
			$kids = $byParent[ (int) $pid ] ?? array();
			if ( ! empty( $kids ) ) {
				echo '<optgroup label="' . esc_attr( $parent->name ) . '">';
				$isSel = in_array( $parent->slug, $selected, true ) ? ' selected="selected"' : '';
				echo '<option value="' . esc_attr( $parent->slug ) . '"' . $isSel . '>'
					. esc_html( $parent->name ) . '</option>';
				foreach ( $kids as $child ) {
					assert( $child instanceof \WP_Term );
					$isSel = in_array( $child->slug, $selected, true ) ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $child->slug ) . '"' . $isSel . '>'
						. esc_html( $child->name ) . '</option>';
				}
				echo '</optgroup>';
			} else {
				$isSel = in_array( $parent->slug, $selected, true ) ? ' selected="selected"' : '';
				echo '<option value="' . esc_attr( $parent->slug ) . '"' . $isSel . '>'
					. esc_html( $parent->name ) . '</option>';
			}
		}

		echo '</select>';
	}

	/**
	 * @param array<mixed> $terms
	 * @return array{0: array<int, \WP_Term>, 1: array<int, list<\WP_Term>>}
	 */
	private function buildDestinationTree( array $terms ): array {
		$parents  = array();
		$children = array();

		foreach ( $terms as $t ) {
			if ( ! ( $t instanceof \WP_Term ) ) {
				continue;
			}
			if ( $t->parent === 0 ) {
				$parents[ $t->term_id ] = $t;
			} else {
				$children[] = $t;
			}
		}

		// Fetch parent terms that had count=0 so hide_empty excluded them
		$parentIds = array_unique( array_map( fn( \WP_Term $c ) => $c->parent, $children ) );
		foreach ( $parentIds as $pid ) {
			if ( $pid > 0 && ! isset( $parents[ $pid ] ) ) {
				$pt = get_term( $pid, 'cf_destination' );
				if ( $pt instanceof \WP_Term ) {
					$parents[ $pid ] = $pt;
				}
			}
		}

		uasort( $parents, fn( \WP_Term $a, \WP_Term $b ) => strcmp( $a->name, $b->name ) );

		$byParent = array();
		foreach ( $children as $child ) {
			$byParent[ $child->parent ][] = $child;
		}
		foreach ( $byParent as $pid => $kids ) {
			usort( $kids, fn( \WP_Term $a, \WP_Term $b ) => strcmp( $a->name, $b->name ) );
			$byParent[ $pid ] = $kids;
		}

		return array( $parents, $byParent );
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
