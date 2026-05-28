<?php
declare(strict_types=1);

namespace Campsflow\Taxonomy;

use Campsflow\PostType\EventPostType;

final class TransportTypeTaxonomy {

	public const SLUG = 'cf_transport_type';

	/** @var array<string, string> Maps API transport.type codes to Polish display labels. */
	public const TYPE_LABELS = array(
		'own'     => 'Własny dojazd',
		'bus'     => 'Autokar',
		'minibus' => 'Minibus',
		'train'   => 'Pociąg',
		'plane'   => 'Samolot',
		'ferry'   => 'Prom',
	);

	/** @var array<string, string> Emoji icons per transport type code. */
	public const TYPE_ICONS = array(
		'own'     => '🚗',
		'bus'     => '🚌',
		'minibus' => '🚐',
		'train'   => '🚂',
		'plane'   => '✈️',
		'ferry'   => '⛴️',
	);

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
