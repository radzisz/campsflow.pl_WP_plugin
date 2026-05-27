<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * [campsflow_search_sort header="" separator="/" show="title,date,price" label_title="" label_date="" label_price=""]
 */
final class SearchSortShortcode {

	/** @var array<string, array{asc: string, desc: string, default_label: string}> */
	private const SORT_GROUPS = array(
		'title' => array(
			'asc'           => 'title_asc',
			'desc'          => 'title_desc',
			'default_label' => 'Nazwa',
		),
		'date'  => array(
			'asc'           => 'date_asc',
			'desc'          => 'date_desc',
			'default_label' => 'Termin',
		),
		'price' => array(
			'asc'           => 'price_asc',
			'desc'          => 'price_desc',
			'default_label' => 'Cena',
		),
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
				'separator'   => '/',
				'show'        => 'title,date,price',
				'label_title' => '',
				'label_date'  => '',
				'label_price' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_search_sort'
		);

		$header      = sanitize_text_field( (string) $atts['header'] );
		$separator   = sanitize_text_field( (string) $atts['separator'] );
		$show        = array_map( 'trim', explode( ',', (string) $atts['show'] ) );
		$currentSort = sanitize_text_field( $_GET['sort'] ?? '' );

		$buttons = array();
		foreach ( self::SORT_GROUPS as $key => $group ) {
			if ( ! in_array( $key, $show, true ) ) {
				continue;
			}
			$custom    = sanitize_text_field( (string) $atts[ 'label_' . $key ] );
			$buttons[] = array(
				'asc'   => $group['asc'],
				'desc'  => $group['desc'],
				'label' => $custom !== '' ? $custom : $group['default_label'],
			);
		}

		if ( empty( $buttons ) ) {
			return '';
		}

		ob_start();

		echo '<div class="cf-sort-wrap">';

		if ( $header !== '' ) {
			echo '<label class="cf-filter-label">' . esc_html( $header ) . '</label>';
		}

		echo '<div class="cf-sort-bar">';

		$last = count( $buttons ) - 1;
		foreach ( $buttons as $i => $btn ) {
			$isAsc   = $currentSort === $btn['asc'];
			$isDesc  = $currentSort === $btn['desc'];
			$classes = 'cf-sort-btn' . ( $isAsc ? ' is-active is-asc' : ( $isDesc ? ' is-active is-desc' : '' ) );
			$arrow   = $isAsc ? '▲' : ( $isDesc ? '▼' : '' );

			echo '<button type="button" class="' . esc_attr( $classes ) . '"'
				. ' data-asc="' . esc_attr( $btn['asc'] ) . '"'
				. ' data-desc="' . esc_attr( $btn['desc'] ) . '">'
				. esc_html( $btn['label'] )
				. ' <span class="cf-sort-btn__arrow" aria-hidden="true">' . esc_html( $arrow ) . '</span>'
				. '</button>';

			if ( $i < $last && $separator !== '' ) {
				echo '<span class="cf-sort-bar__sep" aria-hidden="true">' . esc_html( $separator ) . '</span>';
			}
		}

		echo '<input type="hidden" class="cf-filter" name="sort" value="' . esc_attr( $currentSort ) . '">';
		echo '</div>';

		echo '</div>';

		return (string) ob_get_clean();
	}
}
