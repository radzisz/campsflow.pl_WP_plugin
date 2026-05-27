<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

trait EventMapRenderMethods {

	/**
	 * @return array{0: float|null, 1: float|null, 2: string}
	 */
	private static function extractMapLocation( int $postId ): array {
		$raw = (string) get_post_meta( $postId, 'cf_localization', true );
		if ( $raw === '' ) {
			return array( null, null, '' );
		}
		$loc = json_decode( $raw, true );
		if ( ! is_array( $loc ) ) {
			return array( null, null, '' );
		}

		$lat = null;
		$lng = null;
		if ( is_array( $loc['gps'] ?? null ) && isset( $loc['gps']['lat'], $loc['gps']['lng'] ) ) {
			$lat = (float) $loc['gps']['lat'];
			$lng = (float) $loc['gps']['lng'];
		}

		$addr  = is_array( $loc['address'] ?? null ) ? $loc['address'] : array();
		$parts = array_filter(
			array(
				(string) ( $addr['address'] ?? '' ),
				(string) ( $addr['city'] ?? '' ),
				(string) ( $loc['destination'] ?? '' ),
			)
		);
		return array( $lat, $lng, implode( ', ', $parts ) );
	}

	private function echoMapDiv(
		int $postId,
		string $provider,
		int $zoom,
		string $mapType,
		int $height
	): void {
		[ $lat, $lng, $address ] = self::extractMapLocation( $postId );

		$attrs  = ' data-provider="' . esc_attr( $provider ) . '"';
		$attrs .= ' data-zoom="' . esc_attr( (string) $zoom ) . '"';
		$attrs .= ' data-map-type="' . esc_attr( $mapType ) . '"';
		if ( $lat !== null && $lng !== null ) {
			$attrs .= ' data-lat="' . esc_attr( (string) $lat ) . '" data-lng="' . esc_attr( (string) $lng ) . '"';
		}
		if ( $address !== '' ) {
			$attrs .= ' data-address="' . esc_attr( $address ) . '"';
		}
		echo '<div class="cf-event-map"' . $attrs . ' style="height:' . $height . 'px"></div>';
	}
}
