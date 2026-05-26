<?php
declare(strict_types=1);

namespace Campsflow\Widget;

final class FieldValueRenderer {

	public function applyRenderMode( string $value, string $mode ): string {
		if ( 'text' === $mode ) {
			return esc_html( $value );
		}
		if ( 'html' === $mode ) {
			return wp_kses_post( $value );
		}
		if ( strip_tags( $value ) !== $value ) {
			return wp_kses_post( $value );
		}
		return esc_html( $value );
	}
}
