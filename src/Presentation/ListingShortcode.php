<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;
use WP_Query;

final class ListingShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_listing', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'view'    => 'events',
				'columns' => '3',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_listing'
		);

		$view     = in_array( $atts['view'], array( 'events', 'sessions' ), true ) ? $atts['view'] : 'events';
		$columns  = max( 1, min( 4, (int) $atts['columns'] ) );
		$endpoint = rest_url( 'campsflow/v1/events' );

		ob_start();
		echo '<div class="cf-listing" style="--cf-columns:' . esc_attr( (string) $columns ) . '">';
		$this->renderFilters( $endpoint );
		echo '<div class="cf-search-results" data-endpoint="' . esc_url( $endpoint ) . '">';

		if ( $view === 'events' ) {
			$this->renderEventsView();
		} else {
			$this->renderSessionsView();
		}

		echo '</div>';
		echo '</div>';
		return (string) ob_get_clean();
	}

	private function renderFilters( string $endpoint ): void {
		$tags       = get_terms(
			array(
				'taxonomy'   => 'cf_event_category',
				'hide_empty' => true,
			)
		);
		$ageGroups  = get_terms(
			array(
				'taxonomy'   => 'cf_age_group',
				'hide_empty' => true,
			)
		);
		$dests      = get_terms(
			array(
				'taxonomy'   => 'cf_destination',
				'hide_empty' => true,
			)
		);
		$transports = get_terms(
			array(
				'taxonomy'   => 'cf_transport_type',
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $tags ) || is_wp_error( $ageGroups ) || is_wp_error( $dests ) || is_wp_error( $transports ) ) {
			return;
		}

		$currentCategory  = sanitize_text_field( $_GET['category'] ?? '' );
		$currentAge       = sanitize_text_field( $_GET['age'] ?? '' );
		$currentChildAge  = absint( $_GET['childAge'] ?? 0 );
		$currentDest      = sanitize_text_field( $_GET['destination'] ?? '' );
		$currentTransport = sanitize_text_field( $_GET['transport'] ?? '' );
		$currentDateFrom  = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$currentDateTo    = sanitize_text_field( $_GET['dateTo'] ?? '' );

		echo '<form class="cf-search-form cf-filters" method="get" action="" data-endpoint="' . esc_url( $endpoint ) . '">';

		if ( ! empty( $tags ) ) {
			echo '<select class="cf-filter" name="category">';
			echo '<option value="">' . esc_html__( 'Wszystkie profile', 'campsflow' ) . '</option>';
			foreach ( $tags as $tag ) {
				assert( is_object( $tag ) && isset( $tag->slug, $tag->name ) );
				$selected = selected( $currentCategory, $tag->slug, false );
				echo '<option value="' . esc_attr( $tag->slug ) . '"' . $selected . '>'
					. esc_html( $tag->name ) . '</option>';
			}
			echo '</select>';
		}

		if ( ! empty( $ageGroups ) ) {
			echo '<select class="cf-filter" name="age">';
			echo '<option value="">' . esc_html__( 'Wszystkie grupy wiekowe', 'campsflow' ) . '</option>';
			foreach ( $ageGroups as $group ) {
				assert( is_object( $group ) && isset( $group->slug, $group->name ) );
				$selected = selected( $currentAge, $group->slug, false );
				echo '<option value="' . esc_attr( $group->slug ) . '"' . $selected . '>'
					. esc_html( $group->name ) . '</option>';
			}
			echo '</select>';
		}

		$leafDests = array_filter(
			is_array( $dests ) ? $dests : iterator_to_array( $dests ),
			static fn( $t ) => is_object( $t ) && $t->parent > 0
		);
		if ( ! empty( $leafDests ) ) {
			echo '<select class="cf-filter" name="destination">';
			echo '<option value="">' . esc_html__( 'Wszystkie kierunki', 'campsflow' ) . '</option>';
			foreach ( $leafDests as $dest ) {
				assert( is_object( $dest ) && isset( $dest->slug, $dest->name ) );
				$selected = selected( $currentDest, $dest->slug, false );
				echo '<option value="' . esc_attr( $dest->slug ) . '"' . $selected . '>'
					. esc_html( $dest->name ) . '</option>';
			}
			echo '</select>';
		}

		if ( ! empty( $transports ) ) {
			echo '<select class="cf-filter" name="transport">';
			echo '<option value="">' . esc_html__( 'Transport', 'campsflow' ) . '</option>';
			foreach ( $transports as $transport ) {
				assert( is_object( $transport ) && isset( $transport->slug, $transport->name ) );
				$selected = selected( $currentTransport, $transport->slug, false );
				echo '<option value="' . esc_attr( $transport->slug ) . '"' . $selected . '>'
					. esc_html( $transport->name ) . '</option>';
			}
			echo '</select>';
		}

		echo '<select class="cf-filter" name="childAge">';
		echo '<option value="">' . esc_html__( 'Wiek', 'campsflow' ) . '</option>';
		for ( $yr = 4; $yr <= 17; $yr++ ) {
			$selected = selected( $currentChildAge, $yr, false );
			echo '<option value="' . esc_attr( (string) $yr ) . '"' . $selected . '>'
				. esc_html( sprintf( _n( '%d rok', '%d lat', $yr, 'campsflow' ), $yr ) ) . '</option>';
		}
		echo '<option value="18"' . selected( $currentChildAge, 18, false ) . '>18+</option>';
		echo '</select>';

		echo '<input class="cf-filter" type="date" name="dateFrom" value="' . esc_attr( $currentDateFrom ) . '">';
		echo '<input class="cf-filter" type="date" name="dateTo" value="' . esc_attr( $currentDateTo ) . '">';

		echo '</form>';
	}

	private function renderEventsView(): void {
		$taxQuery  = $this->buildTaxQuery();
		$metaQuery = $this->buildMetaQuery();
		$sort      = sanitize_text_field( $_GET['sort'] ?? '' );
		$orderArgs = $this->buildOrderArgs( $sort );

		$args = array(
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

		$query    = new WP_Query( $args );
		$postIds  = array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
		$renderer = new EventCardRenderer();

		echo $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function renderSessionsView(): void {
		$taxQuery = $this->buildTaxQuery();
		$args     = array(
			'post_type'      => SessionPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'meta_key'       => 'cf_date_from',
			'order'          => 'ASC',
		);
		if ( ! empty( $taxQuery ) ) {
			$args['tax_query'] = $taxQuery;
		}

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			echo '<p class="cf-empty">' . esc_html__( 'Brak turnusów spełniających kryteria.', 'campsflow' ) . '</p>';
			return;
		}

		echo '<div class="cf-sessions-flat">';
		$currentEvent = 0;
		while ( $query->have_posts() ) {
			$query->the_post();
			$sessionId = (int) get_the_ID();
			$eventId   = (int) wp_get_post_parent_id( $sessionId );

			if ( $eventId !== $currentEvent ) {
				if ( $currentEvent !== 0 ) {
					echo '</ul>';
				}
				echo '<h3 class="cf-sessions-flat__event">' . esc_html( get_the_title( $eventId ) ) . '</h3>';
				echo '<ul class="cf-sessions">';
				$currentEvent = $eventId;
			}

			$this->renderSessionRow( $sessionId );
		}
		echo '</ul></div>';
		wp_reset_postdata();
	}

	private function renderSessionRow( int $sessionId ): void {
		echo ( new EventCardRenderer() )->renderSessionRow( $sessionId ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function buildTaxQuery(): array {
		$query = array();

		$category = sanitize_text_field( $_GET['category'] ?? '' );
		if ( $category ) {
			$query[] = array(
				'taxonomy' => 'cf_event_category',
				'field'    => 'slug',
				'terms'    => $category,
			);
		}

		$age = sanitize_text_field( $_GET['age'] ?? '' );
		if ( $age ) {
			$query[] = array(
				'taxonomy' => 'cf_age_group',
				'field'    => 'slug',
				'terms'    => $age,
			);
		}

		$destination = sanitize_text_field( $_GET['destination'] ?? '' );
		if ( $destination ) {
			$query[] = array(
				'taxonomy' => 'cf_destination',
				'field'    => 'slug',
				'terms'    => $destination,
			);
		}

		$transport = sanitize_text_field( $_GET['transport'] ?? '' );
		if ( $transport ) {
			$query[] = array(
				'taxonomy' => 'cf_transport_type',
				'field'    => 'slug',
				'terms'    => $transport,
			);
		}

		return $query;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function buildMetaQuery(): array {
		$query = array();

		$childAge = absint( $_GET['childAge'] ?? 0 );
		if ( $childAge >= 1 && $childAge <= 99 ) {
			$query[] = array(
				'key'     => 'cf_min_age',
				'value'   => $childAge,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
			$query[] = array(
				'key'     => 'cf_max_age',
				'value'   => $childAge,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		$dateFrom = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		if ( $dateFrom ) {
			$query[] = array(
				'key'     => 'cf_date_earliest',
				'value'   => $dateFrom,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		$dateTo = sanitize_text_field( $_GET['dateTo'] ?? '' );
		if ( $dateTo ) {
			$query[] = array(
				'key'     => 'cf_date_earliest',
				'value'   => $dateTo,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		return $query;
	}
}
