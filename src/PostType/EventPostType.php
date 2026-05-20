<?php
declare(strict_types=1);

namespace Campsflow\PostType;

final class EventPostType {

	public const SLUG = 'cf_event';

	public function register(): void {
		add_action( 'init', array( $this, 'registerPostType' ) );
		add_action( 'admin_menu', array( $this, 'removeAddNew' ) );
		add_action( 'current_screen', array( $this, 'blockNewPostScreen' ) );
	}

	public function registerPostType(): void {
		register_post_type(
			self::SLUG,
			array(
				'labels'            => array(
					'name'          => __( 'CampsFlow', 'campsflow' ),
					'singular_name' => __( 'Wydarzenie', 'campsflow' ),
					'menu_name'     => __( 'CampsFlow', 'campsflow' ),
					'all_items'     => __( 'Wydarzenia', 'campsflow' ),
					'search_items'  => __( 'Szukaj wydarzeń', 'campsflow' ),
					'not_found'     => __( 'Nie znaleziono wydarzeń. Uruchom synchronizację.', 'campsflow' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'menu_icon'         => 'dashicons-flag',
				'menu_position'     => 20,
				'supports'          => array( 'title' ),
				'has_archive'       => 'obozy',
				'rewrite'           => array(
					'slug'       => 'obozy',
					'with_front' => false,
				),
				'capability_type'   => 'post',
				'capabilities'      => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'      => true,
				'hierarchical'      => true,
			)
		);
	}

	public function removeAddNew(): void {
		remove_submenu_page(
			'edit.php?post_type=' . self::SLUG,
			'post-new.php?post_type=' . self::SLUG,
		);
	}

	public function blockNewPostScreen( \WP_Screen $screen ): void {
		if ( $screen->post_type === self::SLUG && $screen->action === 'add' ) {
			wp_redirect( admin_url( 'edit.php?post_type=' . self::SLUG ) );
			exit;
		}
	}
}
