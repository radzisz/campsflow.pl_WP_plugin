<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use WP_Query;

final class SearchResultsWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_search_results';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Wyniki wyszukiwania', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Układ', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'cf_results_tip',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Reaguje na każdą zmianę URL wywoływaną przez widgety <strong>Pole filtru</strong>. Umieść go w dowolnym miejscu strony — nie musi być obok filtrów.', 'campsflow' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Liczba kolumn', 'campsflow' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 4,
				'step'    => 1,
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$columns  = max( 1, min( 4, (int) ( $this->get_settings_for_display()['columns'] ?? 3 ) ) );
		$endpoint = rest_url( 'campsflow/v1/events' );
		$postIds  = $this->queryEventIds();
		$renderer = new EventCardRenderer();

		echo '<div class="cf-search-results" data-endpoint="' . esc_url( $endpoint ) . '" style="--cf-columns:' . esc_attr( (string) $columns ) . '">';
		echo $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * @return int[]
	 */
	private function queryEventIds(): array {
		$childAge  = absint( $_GET['childAge'] ?? 0 );
		$dateFrom  = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$dateTo    = sanitize_text_field( $_GET['dateTo'] ?? '' );
		$category  = sanitize_text_field( $_GET['category'] ?? '' );
		$age       = sanitize_text_field( $_GET['age'] ?? '' );
		$dest      = sanitize_text_field( $_GET['destination'] ?? '' );
		$transport = sanitize_text_field( $_GET['transport'] ?? '' );
		$sort      = sanitize_text_field( $_GET['sort'] ?? '' );

		$taxQuery = array();
		if ( $category ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_event_category',
				'field'    => 'slug',
				'terms'    => $category,
			);
		}
		if ( $age ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_age_group',
				'field'    => 'slug',
				'terms'    => $age,
			);
		}
		if ( $dest ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_destination',
				'field'    => 'slug',
				'terms'    => $dest,
			);
		}
		if ( $transport ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_transport_type',
				'field'    => 'slug',
				'terms'    => $transport,
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
