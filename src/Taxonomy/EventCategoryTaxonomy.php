<?php
declare(strict_types=1);

namespace Campsflow\Taxonomy;

use Campsflow\PostType\EventPostType;

final class EventCategoryTaxonomy {

	public const SLUG = 'cf_event_category';

	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomy' ) );
	}

	public function registerTaxonomy(): void {
		register_taxonomy(
			self::SLUG,
			array( EventPostType::SLUG ),
			array(
				'labels'            => array(
					'name'          => __( 'Kategorie wydarzeń', 'campsflow' ),
					'singular_name' => __( 'Kategoria wydarzenia', 'campsflow' ),
					'all_items'     => __( 'Wszystkie kategorie', 'campsflow' ),
					'add_new_item'  => __( 'Dodaj kategorię', 'campsflow' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'kategoria' ),
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
