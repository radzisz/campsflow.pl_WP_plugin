<?php
declare(strict_types=1);

namespace Campsflow\Taxonomy;

use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;

final class CampTagTaxonomy {

	public const SLUG = 'cf_tag';

	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomy' ) );
	}

	public function registerTaxonomy(): void {
		register_taxonomy(
			self::SLUG,
			array( EventPostType::SLUG, SessionPostType::SLUG ),
			array(
				'labels'            => array(
					'name'          => __( 'Tagi obozów', 'campsflow' ),
					'singular_name' => __( 'Tag obozu', 'campsflow' ),
					'all_items'     => __( 'Wszystkie tagi', 'campsflow' ),
					'add_new_item'  => __( 'Dodaj tag', 'campsflow' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'oboz-tag' ),
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
