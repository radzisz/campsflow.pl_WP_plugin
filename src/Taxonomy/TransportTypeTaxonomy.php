<?php
declare(strict_types=1);

namespace Campsflow\Taxonomy;

use Campsflow\PostType\EventPostType;

final class TransportTypeTaxonomy {

	public const SLUG = 'cf_transport_type';

	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomy' ) );
	}

	public function registerTaxonomy(): void {
		register_taxonomy(
			self::SLUG,
			array( EventPostType::SLUG ),
			array(
				'labels'            => array(
					'name'          => __( 'Typy transportu', 'campsflow' ),
					'singular_name' => __( 'Typ transportu', 'campsflow' ),
					'all_items'     => __( 'Wszystkie typy transportu', 'campsflow' ),
					'add_new_item'  => __( 'Dodaj typ transportu', 'campsflow' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'transport' ),
				'show_admin_column' => true,
				'capabilities'      => array(
					'manage_terms' => 'do_not_allow',
					'edit_terms'   => 'do_not_allow',
					'delete_terms' => 'do_not_allow',
					'assign_terms' => 'edit_posts',
				),
			)
		);
	}
}
