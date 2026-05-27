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

		$view    = in_array( $atts['view'], array( 'events', 'sessions' ), true ) ? $atts['view'] : 'events';
		$columns = max( 1, min( 4, (int) $atts['columns'] ) );

		ob_start();
		echo '<div class="cf-listing" style="--cf-columns:' . esc_attr( (string) $columns ) . '">';
		$this->renderFilters();
		echo '<div class="cf-search-results">';

		if ( $view === 'events' ) {
			$this->renderEventsView();
		} else {
			$this->renderSessionsView();
		}

		echo '</div>';
		echo '</div>';
		return (string) ob_get_clean();
	}

	private function renderFilters(): void {
		$tags      = get_terms(
			array(
				'taxonomy'   => 'cf_event_category',
				'hide_empty' => true,
			)
		);
		$ageGroups = get_terms(
			array(
				'taxonomy'   => 'cf_age_group',
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $tags ) || is_wp_error( $ageGroups ) ) {
			return;
		}

		$currentTag = sanitize_text_field( $_GET['cf_category'] ?? '' );
		$currentAge = sanitize_text_field( $_GET['cf_age'] ?? '' );

		echo '<form class="cf-search-form cf-filters" method="get" action="" data-endpoint="' . esc_url( rest_url( 'campsflow/v1/events' ) ) . '">';

		if ( ! empty( $tags ) ) {
			echo '<select class="cf-filter" name="cf_category" onchange="this.form.submit()">';
			echo '<option value="">' . esc_html__( 'Wszystkie kategorie', 'campsflow' ) . '</option>';
			foreach ( $tags as $tag ) {
				assert( is_object( $tag ) && isset( $tag->slug, $tag->name ) );
				$selected = selected( $currentTag, $tag->slug, false );
				echo '<option value="' . esc_attr( $tag->slug ) . '"' . $selected . '>'
					. esc_html( $tag->name ) . '</option>';
			}
			echo '</select>';
		}

		if ( ! empty( $ageGroups ) ) {
			echo '<select class="cf-filter" name="cf_age" onchange="this.form.submit()">';
			echo '<option value="">' . esc_html__( 'Wszystkie grupy wiekowe', 'campsflow' ) . '</option>';
			foreach ( $ageGroups as $group ) {
				assert( is_object( $group ) && isset( $group->slug, $group->name ) );
				$selected = selected( $currentAge, $group->slug, false );
				echo '<option value="' . esc_attr( $group->slug ) . '"' . $selected . '>'
					. esc_html( $group->name ) . '</option>';
			}
			echo '</select>';
		}

		echo '</form>';
	}

	private function renderEventsView(): void {
		$taxQuery = $this->buildTaxQuery();
		$query    = new WP_Query(
			array(
				'post_type'      => EventPostType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'tax_query'      => $taxQuery,
				'fields'         => 'ids',
			)
		);

		$postIds  = array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
		$renderer = new EventCardRenderer();

		echo $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function renderSessionsView(): void {
		$taxQuery = $this->buildTaxQuery();
		$query    = new WP_Query(
			array(
				'post_type'      => SessionPostType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'meta_value',
				'meta_key'       => 'cf_date_from',
				'order'          => 'ASC',
				'tax_query'      => $taxQuery,
			)
		);

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
	 * @return array<int, array<string, mixed>>
	 */
	private function buildTaxQuery(): array {
		$query = array();

		$tag = sanitize_text_field( $_GET['cf_category'] ?? '' );
		if ( $tag ) {
			$query[] = array(
				'taxonomy' => 'cf_event_category',
				'field'    => 'slug',
				'terms'    => $tag,
			);
		}

		$age = sanitize_text_field( $_GET['cf_age'] ?? '' );
		if ( $age ) {
			$query[] = array(
				'taxonomy' => 'cf_age_group',
				'field'    => 'slug',
				'terms'    => $age,
			);
		}

		return $query;
	}
}
