<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

final class EventDocumentsShortcode {

	public function register(): void {
		add_shortcode( 'campsflow_event_documents', array( $this, 'render' ) );
	}

	/** @param array<string,string>|string $atts */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'show_label'   => '',
				'label'        => __( 'Dokumenty', 'campsflow' ),
				'open_new_tab' => 'yes',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_documents'
		);

		$showLabel  = sanitize_key( $atts['show_label'] ) === 'yes';
		$labelText  = sanitize_text_field( $atts['label'] );
		$openNewTab = sanitize_key( $atts['open_new_tab'] ) === 'yes';
		$postId     = (int) get_the_ID();

		if ( ! $postId ) {
			return '';
		}

		$decoded   = json_decode( (string) get_post_meta( $postId, 'cf_documents', true ), true );
		$documents = is_array( $decoded ) ? $decoded : array();

		if ( empty( $documents ) ) {
			return '';
		}

		$target = $openNewTab ? ' target="_blank" rel="noopener"' : '';

		ob_start();
		echo '<div class="cf-docs-list">';
		if ( $showLabel && '' !== $labelText ) {
			echo '<div class="cf-docs-list__label">' . esc_html( $labelText ) . '</div>';
		}
		echo '<ul>';
		foreach ( $documents as $doc ) {
			if ( ! is_array( $doc ) ) {
				continue;
			}
			$url  = esc_url( (string) ( $doc['url'] ?? '' ) );
			$name = esc_html( sanitize_text_field( (string) ( $doc['name'] ?? $doc['url'] ?? '' ) ) );
			if ( '' === $url ) {
				continue;
			}
			echo '<li><a class="cf-doc-link" href="' . $url . '"' . $target . '>' . $name . '</a></li>';
		}
		echo '</ul></div>';
		return (string) ob_get_clean();
	}
}
