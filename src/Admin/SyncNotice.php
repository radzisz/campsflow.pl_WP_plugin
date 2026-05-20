<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;
use Campsflow\Taxonomy\AgeGroupTaxonomy;
use Campsflow\Taxonomy\CampTagTaxonomy;

/**
 * Displays a read-only notice on all CPT and taxonomy screens.
 * Informs admin that data is managed in Campsflow, not in WordPress.
 */
final class SyncNotice {

	private const MANAGED_POST_TYPES = array( EventPostType::SLUG, SessionPostType::SLUG );
	private const MANAGED_TAXONOMIES = array( CampTagTaxonomy::SLUG, AgeGroupTaxonomy::SLUG );

	public function register(): void {
		add_action( 'current_screen', array( $this, 'maybeEnqueue' ) );
	}

	public function maybeEnqueue( \WP_Screen $screen ): void {
		$isPostType = in_array( $screen->post_type, self::MANAGED_POST_TYPES, true );
		$isTaxonomy = $screen->base === 'edit-tags'
			&& in_array( $screen->taxonomy, self::MANAGED_TAXONOMIES, true );

		if ( ! $isPostType && ! $isTaxonomy ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'admin_head', array( $this, 'inlineStyles' ) );
	}

	public function render(): void {
		$adminUrl = esc_url( get_option( 'campsflow_admin_url', 'https://admin.campsflow.pl' ) );
		$screen   = get_current_screen();
		$isEdit   = $screen && $screen->base === 'post';

		echo '<div class="cf-sync-notice">';
		echo '<span class="cf-sync-notice__icon dashicons dashicons-update"></span>';
		echo '<span class="cf-sync-notice__text">';
		echo esc_html__( 'Dane są synchronizowane z Campsflow i nie mogą być edytowane tutaj.', 'campsflow' );
		echo '</span>';
		echo '<a class="cf-sync-notice__link" href="' . $adminUrl . '" target="_blank" rel="noopener">';
		echo esc_html__( 'Zarządzaj w Campsflow →', 'campsflow' );
		echo '</a>';

		if ( $isEdit ) {
			echo '<a class="cf-sync-notice__back" href="' . esc_url( admin_url( 'edit.php?post_type=' . ( $screen->post_type ?? '' ) ) ) . '">';
			echo esc_html__( '← Wróć do listy', 'campsflow' );
			echo '</a>';
		}

		echo '</div>';
	}

	public function inlineStyles(): void {
		echo '<style>
        .cf-sync-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f0f6fc;
            border-left: 4px solid #2563eb;
            padding: 10px 14px;
            margin: 10px 0 16px;
            border-radius: 0 4px 4px 0;
            font-size: 13px;
        }
        .cf-sync-notice__icon { color: #2563eb; font-size: 18px; }
        .cf-sync-notice__text { flex: 1; }
        .cf-sync-notice__link {
            background: #2563eb;
            color: #fff;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }
        .cf-sync-notice__link:hover { background: #1d4ed8; color: #fff; }
        .cf-sync-notice__back { color: #6b7280; text-decoration: none; white-space: nowrap; }
        .cf-sync-notice__back:hover { color: #111; }

        /* CPT: hide edit, quick-edit, trash */
        .post-type-cf_event .row-actions .edit,
        .post-type-cf_event .row-actions .inline,
        .post-type-cf_event .row-actions .trash,
        .post-type-cf_session .row-actions .edit,
        .post-type-cf_session .row-actions .inline,
        .post-type-cf_session .row-actions .trash { display: none !important; }

        /* Taxonomy: hide add-new form and edit/delete row actions */
        .taxonomy-cf_tag #col-left,
        .taxonomy-cf_age_group #col-left { display: none !important; }

        .taxonomy-cf_tag #col-right,
        .taxonomy-cf_age_group #col-right { float: none; width: 100%; }

        .taxonomy-cf_tag .row-actions .edit,
        .taxonomy-cf_tag .row-actions .delete,
        .taxonomy-cf_tag .row-actions .inline,
        .taxonomy-cf_age_group .row-actions .edit,
        .taxonomy-cf_age_group .row-actions .delete,
        .taxonomy-cf_age_group .row-actions .inline { display: none !important; }
        </style>';
	}
}
