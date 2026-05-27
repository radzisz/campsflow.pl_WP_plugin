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

		$labelText = empty( $selected )
			? esc_html( $emptyLabel )
			: ( count( $selected ) === 1
				? esc_html( self::findTermLabel( $terms, $selected[0] ) )
				: esc_html( (string) count( $selected ) ) );

		echo '<div class="cf-multi" data-name="' . esc_attr( $paramName ) . '" data-empty-label="' . esc_attr( $emptyLabel ) . '">';
		echo '<button type="button" class="cf-multi__toggle" aria-haspopup="listbox" aria-expanded="false">';
		echo '<span class="cf-multi__label">' . $labelText . '</span>';
		if ( count( $selected ) > 1 ) {
			echo '<span class="cf-multi__count">' . esc_html( (string) count( $selected ) ) . '</span>';
		}
		echo '<span class="cf-multi__arrow" aria-hidden="true">&#9662;</span>';
		echo '</button>';
		echo '<div class="cf-multi__dropdown" role="listbox" aria-multiselectable="true">';

		foreach ( $terms as $term ) {
			assert( is_object( $term ) && isset( $term->slug, $term->name ) );
			$checked = in_array( $term->slug, $selected, true ) ? ' checked' : '';
			echo '<label class="cf-multi__option">'
				. '<input type="checkbox" value="' . esc_attr( $term->slug ) . '" data-label="' . esc_attr( $term->name ) . '"' . $checked . '>'
				. esc_html( $term->name )
				. '</label>';
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * @param \WP_Term[]|\WP_Error $terms
	 */
	private static function findTermLabel( $terms, string $slug ): string {
		if ( is_wp_error( $terms ) ) {
			return $slug;
		}
		foreach ( $terms as $t ) {
			if ( ( $t instanceof \WP_Term ) && $t->slug === $slug ) {
				return $t->name;
			}
		}
		return $slug;
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

		$labelText = empty( $selected )
			? esc_html( $emptyLabel )
			: ( count( $selected ) === 1
				? esc_html( self::findDestinationLabel( $parents, $byParent, $selected[0] ) )
				: esc_html( (string) count( $selected ) ) );

		echo '<div class="cf-multi" data-name="destination" data-empty-label="' . esc_attr( $emptyLabel ) . '">';
		echo '<button type="button" class="cf-multi__toggle" aria-haspopup="listbox" aria-expanded="false">';
		echo '<span class="cf-multi__label">' . $labelText . '</span>';
		if ( count( $selected ) > 1 ) {
			echo '<span class="cf-multi__count">' . esc_html( (string) count( $selected ) ) . '</span>';
		}
		echo '<span class="cf-multi__arrow" aria-hidden="true">&#9662;</span>';
		echo '</button>';
		echo '<div class="cf-multi__dropdown" role="listbox" aria-multiselectable="true">';

		foreach ( $parents as $pid => $parent ) {
			assert( $parent instanceof \WP_Term );
			$kids = $byParent[ (int) $pid ] ?? array();

			echo '<div class="cf-multi__group">';
			$checked = in_array( $parent->slug, $selected, true ) ? ' checked' : '';
			echo '<label class="cf-multi__option cf-multi__option--parent">'
				. '<input type="checkbox" value="' . esc_attr( $parent->slug ) . '" data-label="' . esc_attr( $parent->name ) . '"' . $checked . '>'
				. esc_html( $parent->name )
				. '</label>';

			foreach ( $kids as $child ) {
				assert( $child instanceof \WP_Term );
				$checked = in_array( $child->slug, $selected, true ) ? ' checked' : '';
				echo '<label class="cf-multi__option cf-multi__option--child">'
					. '<input type="checkbox" value="' . esc_attr( $child->slug ) . '" data-label="' . esc_attr( $child->name ) . '"' . $checked . '>'
					. esc_html( $child->name )
					. '</label>';
			}

			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * @param array<int, \WP_Term> $parents
	 * @param array<int, list<\WP_Term>> $byParent
	 */
	private static function findDestinationLabel( array $parents, array $byParent, string $slug ): string {
		foreach ( $parents as $pid => $parent ) {
			if ( $parent->slug === $slug ) {
				return $parent->name;
			}
			foreach ( $byParent[ $pid ] ?? array() as $child ) {
				if ( $child->slug === $slug ) {
					return $child->name;
				}
			}
		}
		return $slug;
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
		$selected = self::parseMultiParam( 'childAge' );

		$labelText = empty( $selected )
			? esc_html( $emptyLabel )
			: ( count( $selected ) === 1
				? esc_html( self::ageLabel( (int) $selected[0] ) )
				: esc_html( (string) count( $selected ) ) );

		echo '<div class="cf-multi" data-name="childAge" data-empty-label="' . esc_attr( $emptyLabel ) . '">';
		echo '<button type="button" class="cf-multi__toggle" aria-haspopup="listbox" aria-expanded="false">';
		echo '<span class="cf-multi__label">' . $labelText . '</span>';
		if ( count( $selected ) > 1 ) {
			echo '<span class="cf-multi__count">' . esc_html( (string) count( $selected ) ) . '</span>';
		}
		echo '<span class="cf-multi__arrow" aria-hidden="true">&#9662;</span>';
		echo '</button>';
		echo '<div class="cf-multi__dropdown" role="listbox" aria-multiselectable="true">';

		for ( $yr = 4; $yr <= 17; $yr++ ) {
			$checked = in_array( (string) $yr, $selected, true ) ? ' checked' : '';
			$label   = self::ageLabel( $yr );
			echo '<label class="cf-multi__option">'
				. '<input type="checkbox" value="' . esc_attr( (string) $yr ) . '" data-label="' . esc_attr( $label ) . '"' . $checked . '>'
				. esc_html( $label )
				. '</label>';
		}
		$checked = in_array( '18', $selected, true ) ? ' checked' : '';
		echo '<label class="cf-multi__option">'
			. '<input type="checkbox" value="18" data-label="18+"' . $checked . '>18+'
			. '</label>';

		echo '</div>';
		echo '</div>';
	}

	private static function ageLabel( int $yr ): string {
		if ( $yr >= 18 ) {
			return '18+';
		}
		/* translators: %d: child age in years */
		return sprintf( _n( '%d rok', '%d lat', $yr, 'campsflow' ), $yr );
	}

	private function renderSeasonFilterSelect( string $emptyLabel ): void {
		$this->renderTaxFilterSelect( 'cf_season', 'season', $emptyLabel );
	}

	private function renderDateRangePicker( string $emptyLabel ): void {
		$from = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$to   = sanitize_text_field( $_GET['dateTo'] ?? '' );

		$displayFrom = self::formatDateDisplay( $from );
		$displayTo   = self::formatDateDisplay( $to );

		if ( $from !== '' && $to !== '' ) {
			$toggleLabel = $displayFrom . ' – ' . $displayTo;
		} elseif ( $from !== '' ) {
			$toggleLabel = $displayFrom . ' – ?';
		} else {
			$toggleLabel = $emptyLabel;
		}

		echo '<div class="cf-daterange" data-name-from="dateFrom" data-name-to="dateTo" data-empty-label="' . esc_attr( $emptyLabel ) . '">';
		echo '<button type="button" class="cf-daterange__toggle" aria-haspopup="dialog" aria-expanded="false">';
		echo '<span class="cf-daterange__label">' . esc_html( $toggleLabel ) . '</span>';
		echo '<span class="cf-daterange__arrow" aria-hidden="true">&#9662;</span>';
		echo '</button>';

		echo '<div class="cf-daterange__dropdown" role="dialog">';
		echo '<div class="cf-daterange__inputs">';
		echo '<label class="cf-daterange__field" data-role="from">';
		echo '<span class="cf-daterange__field-label">' . esc_html__( 'Wyjazd po', 'campsflow' ) . '</span>';
		echo '<input type="text" class="cf-daterange__text" data-role="from" readonly placeholder="DD.MM.RRRR" value="' . esc_attr( $displayFrom ) . '">';
		echo '</label>';
		echo '<span class="cf-daterange__field-sep" aria-hidden="true">—</span>';
		echo '<label class="cf-daterange__field" data-role="to">';
		echo '<span class="cf-daterange__field-label">' . esc_html__( 'powrót przed', 'campsflow' ) . '</span>';
		echo '<input type="text" class="cf-daterange__text" data-role="to" readonly placeholder="DD.MM.RRRR" value="' . esc_attr( $displayTo ) . '">';
		echo '</label>';
		echo '</div>';
		echo '<div class="cf-daterange__calendars"></div>';
		echo '<div class="cf-daterange__actions">';
		echo '<button type="button" class="cf-daterange__clear">' . esc_html__( 'wyczyść wszystko', 'campsflow' ) . '</button>';
		echo '<button type="button" class="cf-daterange__confirm">' . esc_html__( 'Wybierz', 'campsflow' ) . '</button>';
		echo '</div>';
		echo '</div>';

		echo '<input type="hidden" class="cf-filter" name="dateFrom" value="' . esc_attr( $from ) . '">';
		echo '<input type="hidden" class="cf-filter" name="dateTo" value="' . esc_attr( $to ) . '">';
		echo '</div>';
	}

	private static function formatDateDisplay( string $ymd ): string {
		if ( $ymd === '' ) {
			return '';
		}
		$parts = explode( '-', $ymd );
		if ( count( $parts ) !== 3 ) {
			return $ymd;
		}
		return $parts[2] . '.' . $parts[1] . '.' . $parts[0];
	}
}
