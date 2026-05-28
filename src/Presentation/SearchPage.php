<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

final class SearchPage {

	private const META_KEY = '_campsflow_search_page';

	public static function createPageIfMissing(): void {
		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'meta_key'       => self::META_KEY,
				'meta_value'     => '1',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			return;
		}

		self::insertPage();
	}

	public static function restoreOrCreatePage(): void {
		$trashed = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'trash',
				'posts_per_page' => 1,
				'meta_key'       => self::META_KEY,
				'meta_value'     => '1',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $trashed ) ) {
			wp_untrash_post( (int) $trashed[0] );
			return;
		}

		self::insertPage();
	}

	public static function findPage(): int {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => 1,
				'meta_key'       => self::META_KEY,
				'meta_value'     => '1',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return ! empty( $pages ) ? (int) $pages[0] : 0;
	}

	public static function pageUrl(): string {
		static $cached = null;
		if ( $cached !== null ) {
			return $cached;
		}
		$pages  = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'meta_key'       => self::META_KEY,
				'meta_value'     => '1',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$cached = ! empty( $pages ) ? (string) get_permalink( $pages[0] ) : home_url( '/cf-search/' );
		return $cached;
	}

	private static function insertPage(): void {
		$postId = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Szukaj obozów', 'campsflow' ),
				'post_name'    => 'cf-search',
				'post_content' => '[campsflow_search_filter][campsflow_search_results]',
			)
		);

		if ( is_int( $postId ) && $postId > 0 ) {
			update_post_meta( $postId, self::META_KEY, '1' );
		}
	}
}
