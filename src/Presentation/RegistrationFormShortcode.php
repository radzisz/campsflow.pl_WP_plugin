<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\Config;

final class RegistrationFormShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_registration_form', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, mixed>|string $atts
	 */
	public function render( array|string $atts ): string {
		$sessionId  = sanitize_text_field( (string) ( $_GET['session'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tenantSlug = (string) get_option( 'campsflow_tenant_slug', '' );

		if ( ! $this->isValidUuid( $sessionId ) || $tenantSlug === '' ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$iframeUrl = Config::embedRegistrationUrl( $tenantSlug, $sessionId );
		$appOrigin = Config::appOrigin();

		wp_enqueue_script(
			'campsflow-registration',
			CAMPSFLOW_PLUGIN_URL . 'assets/js/registration.js',
			array(),
			CAMPSFLOW_VERSION,
			true
		);

		wp_localize_script(
			'campsflow-registration',
			'CampsflowRegistration',
			array( 'iframeOrigin' => $appOrigin )
		);

		return sprintf(
			'<div class="cf-registration-wrap">'
			. '<iframe id="campsflow-registration-iframe" src="%s" '
			. 'style="width:100%%;border:none;display:block;" height="600" '
			. 'loading="lazy" title="%s"></iframe>'
			. '</div>',
			esc_url( $iframeUrl ),
			esc_attr__( 'Formularz rejestracji', 'campsflow' ),
		);
	}

	public static function createPageIfMissing(): void {
		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'meta_key'       => '_campsflow_registration_page',
				'meta_value'     => '1',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			return;
		}

		$postId = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Rejestracja', 'campsflow' ),
				'post_name'    => 'rejestracja',
				'post_content' => '[campsflow_registration_form]',
			)
		);

		if ( is_int( $postId ) && $postId > 0 ) {
			update_post_meta( $postId, '_campsflow_registration_page', '1' );
		}
	}

	public static function registrationUrl( int $sessionPostId ): string {
		$cfSessionId = (string) get_post_meta( $sessionPostId, 'cf_session_id', true );
		if ( $cfSessionId === '' ) {
			return '';
		}
		return add_query_arg( 'session', $cfSessionId, self::pageUrl() );
	}

	private static function pageUrl(): string {
		static $cached = null;
		if ( $cached !== null ) {
			return $cached;
		}
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'meta_key'       => '_campsflow_registration_page',
				'meta_value'     => '1',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$cached = ! empty( $pages ) ? (string) get_permalink( $pages[0] ) : home_url( '/rejestracja/' );
		return $cached;
	}

	private function isValidUuid( string $value ): bool {
		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
			$value
		);
	}
}
