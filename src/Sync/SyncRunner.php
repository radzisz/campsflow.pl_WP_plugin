<?php
declare(strict_types=1);

namespace Campsflow\Sync;

use Campsflow\Config;
use Campsflow\PostType\EventPostType;
use Campsflow\PostType\PostStatus;
use Campsflow\PostType\SessionPostType;
use Campsflow\Taxonomy\AgeGroupTaxonomy;
use Campsflow\Taxonomy\DestinationTaxonomy;
use Campsflow\Taxonomy\EventCategoryTaxonomy;
use Campsflow\Taxonomy\EventTagTaxonomy;
use Campsflow\Taxonomy\TransportTypeTaxonomy;

final class SyncRunner {

	public function run(): SyncStats {
		$tenantSlug = (string) get_option( 'campsflow_tenant_slug', '' );
		$apiKey     = (string) get_option( 'campsflow_api_key', '' );

		$usingFixture = ! $tenantSlug || ! $apiKey;
		$events       = $usingFixture
			? $this->loadFixtureOrEmpty()
			: $this->fetchFromApi( Config::eventsEndpoint( $tenantSlug ), $apiKey );

		$transformer      = new Transformer();
		$stats            = new SyncStats();
		$stats->isFixture = $usingFixture;
		$seenEventIds     = array();
		$seenSessionIds   = array();

		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			[$eventPostId]  = $this->upsertEvent( $event, $stats );
			$seenEventIds[] = (string) $event['id'];

			foreach ( ( $event['turnusy'] ?? array() ) as $turnus ) {
				if ( ! is_array( $turnus ) ) {
					continue;
				}
				$transformed = $transformer->transformTurnus( $turnus );
				$this->upsertSession( $transformed, $eventPostId, $stats );
				$seenSessionIds[] = $transformed->turnusId;
			}
		}

		$this->inactivateMissing( EventPostType::SLUG, 'cf_event_id', $seenEventIds, $stats, true );
		$this->inactivateMissing( SessionPostType::SLUG, 'cf_session_id', $seenSessionIds, $stats, false );

		return $stats;
	}

	// ── Event ────────────────────────────────────────────────────────────────

	/**
	 * @param array<string, mixed> $event
	 * @return array{int, bool}
	 */
	private function upsertEvent( array $event, SyncStats $stats ): array {
		$cfId     = (string) $event['id'];
		$existing = $this->findByMeta( EventPostType::SLUG, 'cf_event_id', $cfId );
		$wpStatus = 'publish';

		$desc    = $event['description'] ?? array();
		$general = is_array( $desc ) ? ( $desc['general'] ?? '' ) : (string) $desc;

		$postData = array(
			'post_type'    => EventPostType::SLUG,
			'post_status'  => $wpStatus,
			'post_title'   => (string) ( $event['name'] ?? '' ),
			'post_content' => $general,
		);

		if ( $existing ) {
			$postData['ID']        = $existing;
			$postData['post_name'] = $cfId;
			wp_update_post( $postData );
			$postId = $existing;
			++$stats->eventsUpdated;
		} else {
			$postId = (int) wp_insert_post( $postData );
			wp_update_post(
				array(
					'ID'        => $postId,
					'post_name' => $cfId,
				)
			);
			++$stats->eventsAdded;
		}

		update_post_meta( $postId, 'cf_event_id', $cfId );
		$this->saveEventMeta( $postId, $event );
		$this->setEventTags( $postId, $event );
		$this->setEventProfiles( $postId, $event );
		$this->setAgeGroupTerms( $postId, $event );
		$this->setDestinationTerms( $postId, $event );
		$this->setTransportTypeTerms( $postId, $event );

		return array( $postId, ! $existing );
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function saveEventMeta( int $postId, array $event ): void {
		// Localization
		if ( isset( $event['localization'] ) && is_array( $event['localization'] ) ) {
			update_post_meta( $postId, 'cf_localization', wp_json_encode( $event['localization'], JSON_UNESCAPED_UNICODE ) );
		}

		// Multimedia
		$multimediaUrls = is_array( $event['multimediaUrls'] ?? null ) ? $event['multimediaUrls'] : array();
		update_post_meta( $postId, 'cf_multimedia_urls', wp_json_encode( $multimediaUrls, JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $postId, 'cf_lead_image_url', (string) ( $event['leadImageUrl'] ?? $multimediaUrls[0] ?? '' ) );

		$videoUrls = is_array( $event['videoUrls'] ?? null ) ? $event['videoUrls'] : array();
		update_post_meta( $postId, 'cf_video_urls', wp_json_encode( $videoUrls, JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $postId, 'cf_lead_video_url', (string) ( $event['leadVideoUrl'] ?? $videoUrls[0] ?? '' ) );

		// Description (general already in post_content; keep full object for program/priceInclude)
		if ( isset( $event['description'] ) && is_array( $event['description'] ) ) {
			update_post_meta( $postId, 'cf_description', wp_json_encode( $event['description'], JSON_UNESCAPED_UNICODE ) );
		}

		// Documents
		$documents = is_array( $event['documents'] ?? null ) ? $event['documents'] : array();
		update_post_meta( $postId, 'cf_documents', wp_json_encode( $documents, JSON_UNESCAPED_UNICODE ) );

		// General terms
		if ( isset( $event['generalTerms'] ) && is_array( $event['generalTerms'] ) ) {
			update_post_meta( $postId, 'cf_general_terms', wp_json_encode( $event['generalTerms'], JSON_UNESCAPED_UNICODE ) );
		}

		// Instructions
		if ( isset( $event['instructions'] ) && is_array( $event['instructions'] ) ) {
			update_post_meta( $postId, 'cf_instructions', wp_json_encode( $event['instructions'], JSON_UNESCAPED_UNICODE ) );
		}

		// Reservation URL
		update_post_meta( $postId, 'cf_reservation_url', (string) ( $event['reservationUrl'] ?? '' ) );

		// Contact
		if ( isset( $event['contact'] ) && is_array( $event['contact'] ) ) {
			update_post_meta( $postId, 'cf_contact', wp_json_encode( $event['contact'], JSON_UNESCAPED_UNICODE ) );
		}

		// Event class & process
		update_post_meta( $postId, 'cf_event_class', (string) ( $event['eventClass'] ?? '' ) );
		$process = $event['eventProcess'] ?? null;
		update_post_meta( $postId, 'cf_event_process_id', (string) ( is_array( $process ) ? ( $process['id'] ?? '' ) : '' ) );
		update_post_meta( $postId, 'cf_event_process_name', (string) ( is_array( $process ) ? ( $process['name'] ?? '' ) : '' ) );

		// Custom fields
		$cfFields = is_array( $event['customFields'] ?? null ) ? $event['customFields'] : array();
		update_post_meta( $postId, 'cf_custom_fields', (string) wp_json_encode( $cfFields, JSON_UNESCAPED_UNICODE ) );

		// Age + date filter meta
		if ( isset( $event['minAge'] ) ) {
			update_post_meta( $postId, 'cf_min_age', (int) $event['minAge'] );
		}
		if ( isset( $event['maxAge'] ) ) {
			update_post_meta( $postId, 'cf_max_age', (int) $event['maxAge'] );
		}
		$turnusy = is_array( $event['turnusy'] ?? null ) ? $event['turnusy'] : array();
		$dates   = array_filter( array_map( fn( mixed $t ) => is_array( $t ) ? (string) ( $t['dateFrom'] ?? '' ) : '', $turnusy ) );
		update_post_meta( $postId, 'cf_date_earliest', $dates ? min( $dates ) : '' );

		$prices = array_values(
			array_filter(
				array_map( fn( mixed $t ) => is_array( $t ) ? (int) ( $t['priceFrom'] ?? 0 ) : 0, $turnusy ),
				fn( int $p ) => $p > 0
			)
		);
		update_post_meta( $postId, 'cf_event_min_price', $prices ? min( $prices ) : 0 );
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function setEventTags( int $postId, array $event ): void {
		$raw = $event['eventTags'] ?? array();
		$this->setTermsWithMeta( $postId, is_array( $raw ) ? $raw : array(), EventTagTaxonomy::SLUG );
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function setEventProfiles( int $postId, array $event ): void {
		$raw = $event['eventCategories'] ?? array();
		$this->setTermsWithMeta( $postId, is_array( $raw ) ? $raw : array(), EventCategoryTaxonomy::SLUG );
	}

	/**
	 * @param array<mixed> $raw
	 */
	private function setTermsWithMeta( int $postId, array $raw, string $taxonomy ): void {
		$termIds = array();

		foreach ( $raw as $item ) {
			$name    = '';
			$color   = '';
			$visible = true;

			if ( is_string( $item ) ) {
				$name = $item;
			} elseif ( is_array( $item ) ) {
				$name    = (string) ( $item['name'] ?? '' );
				$color   = (string) ( $item['color'] ?? '' );
				$visible = (bool) ( $item['visible'] ?? true );
			}

			if ( $name === '' || ! $visible ) {
				continue;
			}

			$existing = term_exists( $name, $taxonomy );
			if ( $existing === null || $existing === 0 ) {
				$result = wp_insert_term( $name, $taxonomy );
				$termId = is_wp_error( $result ) ? 0 : (int) $result['term_id'];
			} else {
				$termId = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
			}

			if ( $termId === 0 ) {
				continue;
			}

			if ( $color !== '' ) {
				update_term_meta( $termId, 'cf_color', $color );
			}

			$termIds[] = $termId;
		}

		wp_set_object_terms( $postId, $termIds, $taxonomy );
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function setAgeGroupTerms( int $postId, array $event ): void {
		$minAge = isset( $event['minAge'] ) ? (int) $event['minAge'] : null;
		$maxAge = isset( $event['maxAge'] ) ? (int) $event['maxAge'] : null;

		if ( $minAge === null || $maxAge === null ) {
			return;
		}

		$childMax = (int) get_option( 'campsflow_age_child_max', 12 );
		$youthMax = (int) get_option( 'campsflow_age_youth_max', 17 );
		$groups   = array();

		if ( $minAge <= $childMax && $maxAge >= 4 ) {
			$groups[] = __( 'Dzieci', 'campsflow' );
		}
		if ( $minAge <= $youthMax && $maxAge >= ( $childMax + 1 ) ) {
			$groups[] = __( 'Młodzież', 'campsflow' );
		}
		if ( $maxAge > $youthMax ) {
			$groups[] = __( 'Dorośli', 'campsflow' );
		}

		wp_set_object_terms( $postId, $groups, AgeGroupTaxonomy::SLUG );
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function setDestinationTerms( int $postId, array $event ): void {
		$localization = is_array( $event['localization'] ?? null ) ? $event['localization'] : array();
		$country      = (string) ( ( is_array( $localization['address'] ?? null ) ? $localization['address'] : array() )['country'] ?? '' );
		$destination  = (string) ( $localization['destination'] ?? '' );

		if ( $destination === '' ) {
			wp_set_object_terms( $postId, array(), DestinationTaxonomy::SLUG );
			return;
		}

		$termId = static function ( mixed $r ): int {
			return is_wp_error( $r ) ? 0 : (int) ( is_array( $r ) ? $r['term_id'] : $r );
		};

		$slug = DestinationTaxonomy::SLUG;

		$parentId = 0;
		if ( $country !== '' ) {
			$existing = term_exists( $country, $slug );
			$parentId = $termId( $existing ?? wp_insert_term( $country, $slug ) );
		}

		$childArgs     = $parentId > 0 ? array( 'parent' => $parentId ) : array();
		$existingChild = term_exists( $destination, $slug, $parentId > 0 ? $parentId : null );
		$childId       = $termId( $existingChild ?? wp_insert_term( $destination, $slug, $childArgs ) );

		if ( $childId === 0 ) {
			return;
		}

		wp_set_object_terms( $postId, array( $childId ), DestinationTaxonomy::SLUG );
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function setTransportTypeTerms( int $postId, array $event ): void {
		$turnusy = is_array( $event['turnusy'] ?? null ) ? $event['turnusy'] : array();
		$types   = array();

		foreach ( $turnusy as $turnus ) {
			if ( ! is_array( $turnus ) ) {
				continue;
			}
			$transport = $turnus['transport'] ?? null;
			$type      = is_array( $transport ) ? (string) ( $transport['type'] ?? '' ) : '';
			if ( $type !== '' && ! in_array( $type, $types, true ) ) {
				$types[] = $type;
			}
		}

		wp_set_object_terms( $postId, $types, TransportTypeTaxonomy::SLUG );
	}

	// ── Session ──────────────────────────────────────────────────────────────

	private function upsertSession( TransformedTurnus $t, int $eventPostId, SyncStats $stats ): void {
		$existing      = $this->findByMeta( SessionPostType::SLUG, 'cf_session_id', $t->turnusId );
		$dateObj       = $t->dateFrom !== '' ? date_create( $t->dateFrom ) : false;
		$dateFormatted = $dateObj !== false ? $dateObj->format( 'd.m.Y' ) : null;
		$title         = $t->name !== '' ? $t->name : ( $t->dateFrom ? ( $dateFormatted ?? $t->dateFrom ) : $t->turnusId );

		$postData = array(
			'post_type'   => SessionPostType::SLUG,
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_parent' => $eventPostId,
		);

		if ( $existing ) {
			$postData['ID'] = $existing;
			wp_update_post( $postData );
			$postId = $existing;
			++$stats->sessionsUpdated;
		} else {
			$postId = (int) wp_insert_post( $postData );
			++$stats->sessionsAdded;
		}

		$eventCfId = (string) get_post_meta( $eventPostId, 'cf_event_id', true );

		update_post_meta( $postId, 'cf_session_id', $t->turnusId );
		update_post_meta( $postId, 'cf_event_id', $eventCfId );
		update_post_meta( $postId, 'cf_turnus_name', $t->name );
		update_post_meta( $postId, 'cf_date_from', $t->dateFrom );
		update_post_meta( $postId, 'cf_date_to', $t->dateTo );
		update_post_meta( $postId, 'cf_number_of_days', $t->numberOfDays );
		update_post_meta( $postId, 'cf_price_from', $t->priceGrosze );
		update_post_meta( $postId, 'cf_transport', $t->transport );
		update_post_meta( $postId, 'cf_meeting_points_start', $t->meetingPointsStart );
		update_post_meta( $postId, 'cf_meeting_points_return', $t->meetingPointsReturn );
		update_post_meta( $postId, 'cf_seats_available', $t->seatsAvailable );
		update_post_meta( $postId, 'cf_seats_all', $t->seatsAll );
		update_post_meta( $postId, 'cf_availability', $t->availabilityBucket->value );
		update_post_meta( $postId, 'cf_reservation_url', $t->reservationUrl );
		update_post_meta( $postId, 'cf_custom_fields', $t->customFields );
	}

	// ── Inactivation ─────────────────────────────────────────────────────────

	/**
	 * @param string[] $seenIds
	 */
	private function inactivateMissing(
		string $postType,
		string $metaKey,
		array $seenIds,
		SyncStats $stats,
		bool $isEvent,
	): void {
		$existing = get_posts(
			array(
				'post_type'   => $postType,
				'post_status' => 'publish',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);

		foreach ( $existing as $postId ) {
			$cfId = (string) get_post_meta( (int) $postId, $metaKey, true );
			if ( ! $cfId || in_array( $cfId, $seenIds, true ) ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'          => (int) $postId,
					'post_status' => PostStatus::INACTIVE,
				)
			);

			if ( $isEvent ) {
				++$stats->eventsInactivated;
			} else {
				++$stats->sessionsInactivated;
			}
		}
	}

	// ── Data fetching ─────────────────────────────────────────────────────────

	/**
	 * Returns demo data when no credentials configured.
	 * Looks first in tests/fixtures/ (dev), then assets/ (production).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function loadFixtureOrEmpty(): array {
		$candidates = array(
			CAMPSFLOW_PLUGIN_DIR . 'tests/fixtures/api-events.json',
			CAMPSFLOW_PLUGIN_DIR . 'assets/demo-events.json',
		);

		foreach ( $candidates as $path ) {
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$json = file_get_contents( $path );
			if ( $json === false ) {
				continue;
			}
			$data = json_decode( $json, true );
			if ( is_array( $data ) ) {
				return $data;
			}
		}

		return array();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchFromApi( string $url, string $apiKey ): array {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $apiKey,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( 'API request failed: ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			throw new \RuntimeException( 'API returned HTTP ' . $code );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			$preview = substr( $body, 0, 200 );
			throw new \RuntimeException(
				sprintf(
					'API response is not a valid JSON array (url: %s, json_error: %s, body: %s)',
					$url,
					json_last_error_msg(),
					$preview
				)
			);
		}

		return $data;
	}

	private function findByMeta( string $postType, string $metaKey, string $metaValue ): int {
		$posts = get_posts(
			array(
				'post_type'   => $postType,
				'post_status' => 'any',
				'meta_key'    => $metaKey,
				'meta_value'  => $metaValue,
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		return ! empty( $posts ) ? (int) $posts[0] : 0;
	}
}
