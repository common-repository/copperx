<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Copperx_Currencies
 *
 * Contains a list of supported currencies.
 */
class WC_Copperx_Currencies {
	const CURRENCIES = [
		'usd',
		'cad',
		'eur',
		'gbp',
		'aud',
		'sgd',
		'inr',
	];

	/**
	 * Checks if the given currency is a supported.
	 *
	 * @param string $currency  The currency to be evaluated.
	 *
	 * @return bool  True if the provided currency is valid, false otherwise.
	 */
	public static function is_valid_currency( $currency ) {
		return in_array( $currency, self::CURRENCIES, true );
	}
}
