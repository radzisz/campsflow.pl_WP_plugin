<?php
declare(strict_types=1);

namespace Campsflow\Api;

use Campsflow\Presentation\EventCardRenderer;
use Campsflow\PostType\EventPostType;
use Campsflow\Taxonomy\AgeGroupTaxonomy;
use Campsflow\Taxonomy\DestinationTaxonomy;
use Campsflow\Taxonomy\EventCategoryTaxonomy;
use Campsflow\Taxonomy\TransportTypeTaxonomy;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

final class EventsEndpoint {

	private const NAMESPACE = 'campsflow/v1';
	private const ROUTE     = '/events';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoute' ) );
	}

	public function registerRoute(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$categories = self::parseSlugs( (string) $request->get_param( 'category' ) );
		$ages       = self::parseSlugs( (string) $request->get_param( 'age' ) );
		$childAges  = self::parseAges( sanitize_text_field( (string) $request->get_param( 'childAge' ) ) );
		$dests      = self::parseSlugs( (string) $request->get_param( 'destination' ) );
		$transports = self::parseSlugs( (string) $request->get_param( 'transport' ) );
		$eventClass = sanitize_text_field( (string) $request->get_param( 'eventClass' ) );
		$dateFrom   = sanitize_text_field( (string) $request->get_param( 'dateFrom' ) );
		$dateTo     = sanitize_text_field( (string) $request->get_param( 'dateTo' ) );
		$sort       = sanitize_text_field( (string) $request->get_param( 'sort' ) );
		$lm         = sanitize_text_field( (string) $request->get_param( 'locationMode' ) );
		$config     = array(
			'location_mode'      => $lm === 'country_dest_city' ? 'country_dest_city' : 'country_dest',
			'show_profile_tags'  => $request->get_param( 'showProfileTags' ) !== '0',
			'profile_tags_label' => sanitize_text_field( (string) $request->get_param( 'profileTagsLabel' ) ),
			'show_event_tags'    => $request->get_param( 'showEventTags' ) !== '0',
			'event_tags_label'   => sanitize_text_field( (string) $request->get_param( 'eventTagsLabel' ) ),
			'show_age_tags'      => $request->get_param( 'showAgeTags' ) !== '0',
			'age_tags_label'     => sanitize_text_field( (string) $request->get_param( 'ageTagsLabel' ) ),
			'show_date'          => $request->get_param( 'showDate' ) !== '0',
			'date_label'         => sanitize_text_field( (string) $request->get_param( 'dateLabel' ) ),
			'show_location'      => $request->get_param( 'showLocation' ) !== '0',
			'location_label'     => sanitize_text_field( (string) $request->get_param( 'locationLabel' ) ),
			'button_text'        => sanitize_text_field( (string) $request->get_param( 'buttonText' ) ),
		);

		$taxQuery = array( 'relation' => 'AND' );
		if ( ! empty( $categories ) ) {
			$taxQuery[] = array(
				'taxonomy' => EventCategoryTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $categories,
				'operator' => 'IN',
			);
		}
		if ( ! empty( $ages ) ) {
			$taxQuery[] = array(
				'taxonomy' => AgeGroupTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $ages,
				'operator' => 'IN',
			);
		}
		if ( ! empty( $dests ) ) {
			$taxQuery[] = array(
				'taxonomy'         => DestinationTaxonomy::SLUG,
				'field'            => 'slug',
				'terms'            => $dests,
				'operator'         => 'IN',
				'include_children' => true,
			);
		}
		if ( ! empty( $transports ) ) {
			$taxQuery[] = array(
				'taxonomy' => TransportTypeTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $transports,
				'operator' => 'IN',
			);
		}

		$metaQuery = array();
		if ( ! empty( $childAges ) ) {
			$ageOr = array( 'relation' => 'OR' );
			foreach ( $childAges as $age ) {
				$ageOr[] = array(
					'relation' => 'AND',
					array(
						'key'     => 'cf_min_age',
						'value'   => $age,
						'compare' => '<=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'cf_max_age',
						'value'   => $age,
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				);
			}
			$metaQuery[] = $ageOr;
		}
		if ( $eventClass ) {
			$metaQuery[] = array(
				'key'     => 'cf_event_class',
				'value'   => $eventClass,
				'compare' => '=',
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
			'posts_per_page' => 50,
			'orderby'        => $orderArgs['orderby'],
			'order'          => $orderArgs['order'],
			'fields'         => 'ids',
		);
		if ( isset( $orderArgs['meta_key'] ) ) {
			$args['meta_key'] = $orderArgs['meta_key'];
		}

		if ( count( $taxQuery ) > 1 ) {
			$args['tax_query'] = $taxQuery;
		}
		if ( ! empty( $metaQuery ) ) {
			$args['meta_query'] = $metaQuery;
		}

		$query    = new WP_Query( $args );
		$postIds  = array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
		$renderer = new EventCardRenderer( $config );
		$html     = $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty();

		return new WP_REST_Response( array( 'html' => $html ), 200 );
	}

	/**
	 * @return int[]
	 */
	private static function parseAges( string $raw ): array {
		if ( $raw === '' ) {
			return array();
		}
		return array_values(
			array_filter(
				array_map( 'absint', explode( ',', $raw ) ),
				fn( int $a ) => $a >= 1 && $a <= 99
			)
		);
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
