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
		$category    = sanitize_text_field( (string) $request->get_param( 'category' ) );
		$age         = sanitize_text_field( (string) $request->get_param( 'age' ) );
		$destination = sanitize_text_field( (string) $request->get_param( 'destination' ) );
		$transport   = sanitize_text_field( (string) $request->get_param( 'transport' ) );
		$eventClass  = sanitize_text_field( (string) $request->get_param( 'eventClass' ) );
		$dateFrom    = sanitize_text_field( (string) $request->get_param( 'dateFrom' ) );
		$dateTo      = sanitize_text_field( (string) $request->get_param( 'dateTo' ) );

		$taxQuery = array( 'relation' => 'AND' );
		if ( $category ) {
			$taxQuery[] = array(
				'taxonomy' => EventCategoryTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $category,
			);
		}
		if ( $age ) {
			$taxQuery[] = array(
				'taxonomy' => AgeGroupTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $age,
			);
		}
		if ( $destination ) {
			$taxQuery[] = array(
				'taxonomy' => DestinationTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $destination,
			);
		}
		if ( $transport ) {
			$taxQuery[] = array(
				'taxonomy' => TransportTypeTaxonomy::SLUG,
				'field'    => 'slug',
				'terms'    => $transport,
			);
		}

		$metaQuery = array();
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

		$args = array(
			'post_type'      => EventPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);

		if ( count( $taxQuery ) > 1 ) {
			$args['tax_query'] = $taxQuery;
		}
		if ( ! empty( $metaQuery ) ) {
			$args['meta_query'] = $metaQuery;
		}

		$query    = new WP_Query( $args );
		$postIds  = array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
		$renderer = new EventCardRenderer();
		$html     = $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty();

		return new WP_REST_Response( array( 'html' => $html ), 200 );
	}
}
