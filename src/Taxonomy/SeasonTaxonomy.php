<?php
declare(strict_types=1);

namespace Campsflow\Taxonomy;

use Campsflow\PostType\EventPostType;

final class SeasonTaxonomy {

	public const SLUG = 'cf_season';

	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomy' ) );
	}

	public function registerTaxonomy(): void {
		register_taxonomy(
			self::SLUG,
			array( EventPostType::SLUG ),
			array(
				'labels'            => array(
					'name'          => __( 'Sezony', 'campsflow' ),
					'singular_name' => __( 'Sezon', 'campsflow' ),
					'all_items'     => __( 'Wszystkie sezony', 'campsflow' ),
					'add_new_item'  => __( 'Dodaj sezon', 'campsflow' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'sezon' ),
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
