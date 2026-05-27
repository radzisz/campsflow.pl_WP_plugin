<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use WP_Query;

/**
 * [campsflow_search_results columns="3"]
 */
final class SearchResultsShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_search_results', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public function render( array|string $atts ): string {
		$atts     = shortcode_atts(
			array( 'columns' => '3' ),
			is_array( $atts ) ? $atts : array(),
			'campsflow_search_results'
		);
		$columns  = max( 1, min( 4, (int) $atts['columns'] ) );
		$endpoint = rest_url( 'campsflow/v1/events' );
		$postIds  = $this->queryEventIds();
		$renderer = new EventCardRenderer();

		ob_start();
		echo '<div class="cf-search-results" data-endpoint="' . esc_url( $endpoint ) . '" style="--cf-columns:' . esc_attr( (string) $columns ) . '">';
		echo $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * @return string[]
	 */
	private static function parseSlugs( string $raw ): array {
		if ( $raw === '' ) {
			return array();
		}
		return array_values( array_filter( array_map( 'sanitize_text_field', explode( ',', $raw ) ) ) );
	}

	/**
	 * @return int[]
	 */
	private function queryEventIds(): array {
		$dateFrom   = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$dateTo     = sanitize_text_field( $_GET['dateTo'] ?? '' );
		$categories = self::parseSlugs( (string) ( $_GET['category'] ?? '' ) );
		$ages       = self::parseSlugs( (string) ( $_GET['age'] ?? '' ) );
		$dests      = self::parseSlugs( (string) ( $_GET['destination'] ?? '' ) );
		$transports = self::parseSlugs( (string) ( $_GET['transport'] ?? '' ) );
		$childAge   = absint( $_GET['childAge'] ?? 0 );
		$sort       = sanitize_text_field( $_GET['sort'] ?? '' );

		$taxQuery = array();
		if ( ! empty( $categories ) ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_event_category',
				'field'    => 'slug',
				'terms'    => $categories,
				'operator' => 'IN',
			);
		}
		if ( ! empty( $ages ) ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_age_group',
				'field'    => 'slug',
				'terms'    => $ages,
				'operator' => 'IN',
			);
		}
		if ( ! empty( $dests ) ) {
			$taxQuery[] = array(
				'taxonomy'         => 'cf_destination',
				'field'            => 'slug',
				'terms'            => $dests,
				'operator'         => 'IN',
				'include_children' => true,
			);
		}
		if ( ! empty( $transports ) ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_transport_type',
				'field'    => 'slug',
				'terms'    => $transports,
				'operator' => 'IN',
			);
		}

		$metaQuery = array();
		if ( $childAge >= 1 && $childAge <= 99 ) {
			$metaQuery[] = array(
				'key'     => 'cf_min_age',
				'value'   => $childAge,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
			$metaQuery[] = array(
				'key'     => 'cf_max_age',
				'value'   => $childAge,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $dateFrom ) {
			$metaQuery[] = array(
				'key'     => 'cf_date_earliest',
				'value'   => $dateFrom,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}
		if ( $dateTo ) {
			$metaQuery[] = array(
				'key'     => 'cf_date_earliest',
				'value'   => $dateTo,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		$orderArgs = $this->buildOrderArgs( $sort );
		$args      = array(
			'post_type'      => EventPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 24,
			'orderby'        => $orderArgs['orderby'],
			'order'          => $orderArgs['order'],
			'fields'         => 'ids',
		);
		if ( isset( $orderArgs['meta_key'] ) ) {
			$args['meta_key'] = $orderArgs['meta_key'];
		}
		if ( ! empty( $taxQuery ) ) {
			$args['tax_query'] = $taxQuery;
		}
		if ( ! empty( $metaQuery ) ) {
			$args['meta_query'] = $metaQuery;
		}

		$query = new WP_Query( $args );
		return array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
	}

	/**
	 * @return array{orderby: string, order: string, meta_key?: string}
	 */
	private function buildOrderArgs( string $sort ): array {
		if ( $sort === 'title_desc' ) {
			return array(
				'orderby' => 'title',
				'order'   => 'DESC',
			);
		}
		if ( $sort === 'date_asc' ) {
			return array(
				'orderby'  => 'meta_value',
				'meta_key' => 'cf_date_earliest',
				'order'    => 'ASC',
			);
		}
		if ( $sort === 'date_desc' ) {
			return array(
				'orderby'  => 'meta_value',
				'meta_key' => 'cf_date_earliest',
				'order'    => 'DESC',
			);
		}
		if ( $sort === 'price_asc' ) {
			return array(
				'orderby'  => 'meta_value_num',
				'meta_key' => 'cf_event_min_price',
				'order'    => 'ASC',
			);
		}
		if ( $sort === 'price_desc' ) {
			return array(
				'orderby'  => 'meta_value_num',
				'meta_key' => 'cf_event_min_price',
				'order'    => 'DESC',
			);
		}
		return array(
			'orderby' => 'title',
			'order'   => 'ASC',
		);
	}
}
