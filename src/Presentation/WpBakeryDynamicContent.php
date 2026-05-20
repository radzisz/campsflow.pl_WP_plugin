<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use Campsflow\Sync\AvailabilityBucket;

/**
 * Registers CampsFlow post meta fields as WPBakery 7+ Dynamic Content sources.
 * Allows using {cf_location_city}, {cf_price_min}, {cf_availability} etc.
 * in any WPBakery text/heading element via the Dynamic Content picker.
 */
final class WpBakeryDynamicContent {

	public function register(): void {
		// WPBakery 7+ Dynamic Content API
		add_filter( 'vc_dynamic_content_field_list', array( $this, 'registerFields' ) );
		add_filter( 'vc_dynamic_content_field_value', array( $this, 'resolveValue' ), 10, 3 );
	}

	/**
	 * @param array<string, array<string, string>> $fields
	 * @return array<string, array<string, string>>
	 */
	public function registerFields( array $fields ): array {
		$fields['cf_location_city']        = array(
			'label'     => __( 'CampsFlow: Miasto', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_location_address']     = array(
			'label'     => __( 'CampsFlow: Adres ulicy', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_location_destination'] = array(
			'label'     => __( 'CampsFlow: Destinacja (region)', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_location_name']        = array(
			'label'     => __( 'CampsFlow: Nazwa obiektu', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_contact_email']        = array(
			'label'     => __( 'CampsFlow: Email kontaktowy', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_contact_phone']        = array(
			'label'     => __( 'CampsFlow: Telefon kontaktowy', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_price_min']            = array(
			'label'     => __( 'CampsFlow: Cena (najniższa)', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_date_first']           = array(
			'label'     => __( 'CampsFlow: Pierwsza data (turnus)', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_availability_label']   = array(
			'label'     => __( 'CampsFlow: Dostępność (etykieta)', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_tags']                 = array(
			'label'     => __( 'CampsFlow: Tagi (przecinkami)', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);
		$fields['cf_age_groups']           = array(
			'label'     => __( 'CampsFlow: Grupy wiekowe', 'campsflow' ),
			'group'     => 'CampsFlow',
			'post_type' => EventPostType::SLUG,
		);

		return $fields;
	}

	/**
	 * @param mixed $value
	 */
	public function resolveValue( $value, string $field, int $postId ): mixed {
		if ( ! str_starts_with( $field, 'cf_' ) ) {
			return $value;
		}

		$loc  = json_decode( (string) get_post_meta( $postId, 'cf_localization', true ), true );
		$addr = is_array( $loc ) && is_array( $loc['address'] ?? null ) ? $loc['address'] : array();

		return match ( $field ) {
			'cf_location_city'        => (string) ( $addr['city'] ?? '' ),
			'cf_location_address'     => (string) ( $addr['address'] ?? '' ),
			'cf_location_destination' => is_array( $loc ) ? (string) ( $loc['destination'] ?? '' ) : '',
			'cf_location_name'        => is_array( $loc ) ? (string) ( $loc['name'] ?? '' ) : '',
			'cf_contact_email'        => is_array( $loc ) ? (string) ( $loc['email'] ?? '' ) : '',
			'cf_contact_phone'        => is_array( $loc ) ? (string) ( $loc['phone'] ?? '' ) : '',
			'cf_price_min'            => $this->resolveMinPrice( $postId ),
			'cf_date_first'           => $this->resolveFirstDate( $postId ),
			'cf_availability_label'   => $this->resolveAvailabilityLabel( $postId ),
			'cf_tags'                 => $this->resolveTermNames( $postId, 'cf_tag' ),
			'cf_age_groups'           => $this->resolveTermNames( $postId, 'cf_age_group' ),
			default                   => $value,
		};
	}

	private function resolveMinPrice( int $postId ): string {
		$sessions = get_posts(
			array(
				'post_type'   => 'cf_session',
				'post_status' => 'publish',
				'post_parent' => $postId,
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);

		$prices = array_filter(
			array_map(
				static fn( int $id ) => (int) get_post_meta( $id, 'cf_price_from', true ),
				$sessions
			)
		);

		if ( empty( $prices ) ) {
			return '';
		}

		return number_format( min( $prices ) / 100, 0, ',', ' ' ) . ' zł';
	}

	private function resolveFirstDate( int $postId ): string {
		$sessions = get_posts(
			array(
				'post_type'   => 'cf_session',
				'post_status' => 'publish',
				'post_parent' => $postId,
				'numberposts' => 1,
				'orderby'     => 'meta_value',
				'meta_key'    => 'cf_date_from',
				'order'       => 'ASC',
				'fields'      => 'ids',
			)
		);

		if ( empty( $sessions ) ) {
			return '';
		}

		$date = (string) get_post_meta( $sessions[0], 'cf_date_from', true );
		$dt   = $date ? date_create( $date ) : null;
		return $dt ? $dt->format( 'j M Y' ) : '';
	}

	private function resolveAvailabilityLabel( int $postId ): string {
		$sessions = get_posts(
			array(
				'post_type'   => 'cf_session',
				'post_status' => 'publish',
				'post_parent' => $postId,
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);

		$buckets = array_map(
			static fn( int $id ) => AvailabilityBucket::tryFrom(
				(string) get_post_meta( $id, 'cf_availability', true )
			) ?? AvailabilityBucket::Full,
			$sessions
		);

		// Best available bucket across all sessions
		foreach ( array( AvailabilityBucket::Available, AvailabilityBucket::FewLeft, AvailabilityBucket::AlmostFull ) as $check ) {
			if ( in_array( $check, $buckets, true ) ) {
				return $check->label();
			}
		}

		return AvailabilityBucket::Full->label();
	}

	private function resolveTermNames( int $postId, string $taxonomy ): string {
		$terms = get_the_terms( $postId, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		return implode( ', ', array_map( static fn( $t ) => $t->name, $terms ) );
	}
}
