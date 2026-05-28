<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

final class EventContactShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_event_contact', array( $this, 'render' ) );
	}

	/** @param array<string,string>|string $atts */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'show_label' => '',
				'label'      => __( 'Kontakt', 'campsflow' ),
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_contact'
		);

		$showLabel = sanitize_key( $atts['show_label'] ) === 'yes';
		$labelText = sanitize_text_field( $atts['label'] );
		$postId    = (int) get_the_ID();

		if ( ! $postId ) {
			return '';
		}

		$decoded = json_decode( (string) get_post_meta( $postId, 'cf_contact', true ), true );
		$contact = is_array( $decoded ) ? $decoded : array();

		if ( empty( $contact ) ) {
			return '';
		}

		$name  = trim( sanitize_text_field( (string) ( $contact['firstname'] ?? '' ) ) . ' ' . sanitize_text_field( (string) ( $contact['lastname'] ?? '' ) ) );
		$email = sanitize_email( (string) ( $contact['email'] ?? '' ) );
		$phone = sanitize_text_field( (string) ( $contact['phone'] ?? '' ) );

		if ( '' === $name && '' === $email && '' === $phone ) {
			return '';
		}

		ob_start();
		echo '<div class="cf-contact-box">';
		if ( $showLabel && '' !== $labelText ) {
			echo '<div class="cf-contact-box__label">' . esc_html( $labelText ) . '</div>';
		}
		if ( '' !== $name ) {
			echo '<div class="cf-contact-box__name">' . esc_html( $name ) . '</div>';
		}
		echo '<div class="cf-contact-box__links">';
		if ( '' !== $email ) {
			echo '<a class="cf-contact-link" href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
		if ( '' !== $phone ) {
			$tel = preg_replace( '/[^+\d]/', '', $phone ) ?? '';
			echo '<a class="cf-contact-link" href="tel:' . esc_attr( $tel ) . '">' . esc_html( $phone ) . '</a>';
		}
		echo '</div></div>';
		return (string) ob_get_clean();
	}
}
