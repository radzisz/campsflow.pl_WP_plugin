<?php
declare(strict_types=1);

namespace Campsflow\PostType;

/**
 * Registers a custom 'cf_inactive' post status for CPT records
 * that exist in WP but are no longer active in Campsflow.
 * Kept for audit/history — not shown on the frontend.
 */
final class PostStatus {

	public const INACTIVE = 'cf_inactive';

	public function register(): void {
		add_action( 'init', array( $this, 'registerStatus' ) );
	}

	public function registerStatus(): void {
		register_post_status(
			self::INACTIVE,
			array(
				'label'                     => __( 'Nieaktywny (Campsflow)', 'campsflow' ),
				'label_count'               => _n_noop(
					'Nieaktywny <span class="count">(%s)</span>',
					'Nieaktywne <span class="count">(%s)</span>',
					'campsflow',
				),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
			)
		);
	}
}
