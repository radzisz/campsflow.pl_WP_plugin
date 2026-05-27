<?php
declare(strict_types=1);

namespace Campsflow;

/**
 * Resolves runtime configuration values.
 *
 * Priority (highest first):
 *   1. PHP constant  — define('CAMPSFLOW_API_URL', '...')  in wp-config.php
 *   2. Env variable  — CAMPSFLOW_API_URL=...               in OS / Docker env
 *   3. Built-in default
 *
 * For wp-env, set constants in .wp-env.json → env → development → config:
 *   "CAMPSFLOW_API_URL": "http://localhost:3000"
 */
final class Config {

	private const DEFAULT_API_URL   = 'https://api.ukryteskarby.pl';
	private const DEFAULT_ADMIN_URL = 'https://admin.ukryteskarby.pl';
	private const DEFAULT_APP_URL   = 'https://ukryteskarby.pl';

	public static function apiUrl(): string {
		return self::resolve( 'CAMPSFLOW_API_URL', self::DEFAULT_API_URL );
	}

	public static function adminUrl(): string {
		return self::resolve( 'CAMPSFLOW_ADMIN_URL', self::DEFAULT_ADMIN_URL );
	}

	public static function eventsEndpoint( string $tenantSlug ): string {
		assert( $tenantSlug !== '', 'Tenant slug must not be empty' );
		return rtrim( self::apiUrl(), '/' ) . '/api/v1/public/' . rawurlencode( $tenantSlug ) . '/events';
	}

	public static function adminEventUrl( string $tenantSlug, string $cfEventId ): string {
		$base = rtrim( self::adminUrl(), '/' );
		return $base . '/' . rawurlencode( $tenantSlug ) . '/catalog/events/' . rawurlencode( $cfEventId );
	}

	public static function adminSessionUrl( string $tenantSlug, string $cfEventId, string $cfSessionId ): string {
		$base = rtrim( self::adminUrl(), '/' );
		return $base . '/' . rawurlencode( $tenantSlug ) . '/catalog/events/' . rawurlencode( $cfEventId )
			. '/sessions/' . rawurlencode( $cfSessionId );
	}

	/** Returns true if the value is overridden (constant or env var). */
	public static function isOverridden( string $constant ): bool {
		return defined( $constant ) || getenv( $constant ) !== false;
	}

	private static function resolve( string $constant, string $fallback ): string {
		if ( defined( $constant ) ) {
			return (string) constant( $constant );
		}

		$env = getenv( $constant );
		if ( $env !== false && $env !== '' ) {
			return $env;
		}

		if ( function_exists( 'get_option' ) ) {
			$optionKey = strtolower( str_replace( 'CAMPSFLOW_', 'campsflow_', $constant ) );
			$option    = get_option( $optionKey, '' );
			if ( is_string( $option ) && $option !== '' ) {
				return $option;
			}
		}

		return $fallback;
	}
}
