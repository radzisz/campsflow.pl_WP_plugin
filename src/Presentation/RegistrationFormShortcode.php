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
			return '';
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

	private function isValidUuid( string $value ): bool {
		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
			$value
		);
	}
}
