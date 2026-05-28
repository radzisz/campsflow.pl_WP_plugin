<?php
declare(strict_types=1);

namespace Campsflow;

final class CurrencyFormatter {

	public static function symbol( string $currency ): string {
		return match ( strtoupper( $currency ) ) {
			'PLN' => 'zł',
			'EUR' => '€',
			'USD' => '$',
			'GBP' => '£',
			'CZK' => 'Kč',
			'HUF' => 'Ft',
			default => $currency,
		};
	}

	public static function format( int $grosze, string $currency ): string {
		if ( $grosze <= 0 ) {
			return '';
		}
		return number_format( $grosze / 100, 0, ',', ' ' ) . ' ' . self::symbol( $currency );
	}
}
