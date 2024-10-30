<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Copperx_API_Handler {


	// log variable function.
	public static $log;

	/**
	 * Call the $log variable function.
	 *
	 * @param string $message Log message.
	 * @param string $level   Optional. Default 'info'.
	 *     emergency|alert|critical|error|warning|notice|info|debug
	 */
	public static function log( $message, $level = 'info' ) {
		return call_user_func( self::$log, $message, $level );
	}

	/**
	 * Copperx API url.
	 *
	 * @var string Copperx API url
	*/
	public static $api_url = "https://api.copperx.io/api/v1/";


    /**
	 * Copperx API key.
	 *
	 * @var string Copperx API key.
	*/
	public static $api_key;


    /**
	 * Get the response from an API request.
     *
	 * @param string $endpoint
	 * @param array  $params
	 * @param string $method
	 *
	 * @return array
	 */
    public static function send_request( $endpoint, $params = [], $method = 'GET' ){
		if(empty($params)){
			self::log('Copperx Request => ' . $endpoint);
		}else{
			self::log( 'Copperx Request Args for ' . $endpoint . ': ' . wp_json_encode($params, 0, 10));
		}
		$args = [
			'method'  => $method,
			'timeout' => 15,
			'headers' => [
				'accept' => 'application/json',
				'authorization' => 'Bearer '.trim(self::$api_key),
				'Content-Type' => 'application/json'
			]
		];

		$url = self::$api_url . $endpoint;

		if ( in_array( $method, [ 'POST', 'PUT' ] ) ) {
			$args['body'] = wp_json_encode( $params );
		} else {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_request( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			self::log( 'WP response error: ' . $response->get_error_message() );
			return [ false, $response->get_error_message() ];
		} else {
			$result = json_decode( $response['body'], true );
			if ( ! empty( $result['warnings'] ) ) {
				foreach ( $result['warnings'] as $warning ) {
					self::log( 'API Warning: ' . $warning );
				}
			}

			$code = $response['response']['code'];

			if ( in_array( $code, [ 200, 201 ], true ) ) {
				return [ true, $result ];
			} else {
				$errorMsg      = empty( $result['message'] ) ? '' : $result['message'];
				$errors = [
					400 => 'Error response from API: ' . $errorMsg,
					401 => 'Authentication error, please check your Copperx API key.',
					422 => 'Copperx Server is unable to process request. => ' . $result['error'] . ' ' . wp_json_encode($result),
					500 => 'Internal server error from Copperx.'
				];

				if ( array_key_exists( $code, $errors ) ) {
					$msg = $errors[ $code ];
				} else {
					$msg = 'Unknown response from API: ' . $code . ' ==> '. $result['error'];
				}
				self::log( $msg );

				return [ false, $code ];
			}
		}
    }


    /**
	 * Create a new checkout request.
     *
	 * @param string  $order_id
	 * @param array   $order_items_data -- (Line Items data)
	 * @param object  $metadata
	 * @param string  $redirect
	 * @param string  $cancel
	 *
	 * @return
	 *                                          array
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 * @SuppressWarnings(PHPMD.LongVariable)
	 */
	public static function create_checkout($order_id, $order_items_data, $metadata, $nameCollection, $emailCollection, $phoneNumberCollection, $billingAddressCollection, $shippingAddressCollection, $customerData, $redirect = null, $cancel = null ) {

		if(is_null($order_items_data) || is_null($redirect)){
			self::log('Error: Missing Order data (in create_checkout()).', 'error');
			return [false, 'Missing Order data.'];
		}else{
			$args = [
				'submitType' => 'pay',
				'clientReferenceId' => $order_id,
				'lineItems' => (object) [
					'data' => $order_items_data
				],
				'afterCompletion' => 'redirect',
				'nameCollection' => $nameCollection,
				'emailCollection' => $emailCollection,
				'phoneNumberCollection' => $phoneNumberCollection,
				'billingAddressCollection' => $billingAddressCollection,
				'shippingAddressCollection' => $shippingAddressCollection,
			];
		}

		if(!empty($customerData)){
			$args['customerData'] = $customerData;
		}
		if ( ! is_null( $metadata ) ) {
			$args['metadata'] = $metadata;
		}
		if ( ! is_null( $redirect ) ) {
			$args['successUrl'] = $redirect;
		}
		if ( ! is_null( $cancel ) ) {
			$args['cancelUrl'] = $cancel;
		}

		$result = self::send_request( 'checkout/sessions', $args, 'POST' );

		return $result;
	}


	/**
	 * Get the checkoutSession.
     *
	 * @param string $checkoutSession_id
	 *
	 * @return array
	 */
	public static function get_checkoutSession($checkoutSession_id) {

		$result = self::send_request( 'checkout/sessions/'.$checkoutSession_id );

		return $result;
	}


    /**
	 * Get the checkoutSession Status.
     *
	 * @param string $checkoutSession_id
	 *
	 * @return array
	 */
	public static function get_checkoutSessionStatus($checkoutSession_id) {

		$result = self::send_request( 'checkout/sessions/'.$checkoutSession_id.'/completed_webhook_delivered' );

		return $result;
	}


	/**
	 * Get the cryptocurrency Prices for copperx supported fiat currency.
     *
	 * @param string $currency
	 *
	 * @return array
	 */
	public static function get_currencyPrices($currency) {

		$result = self::send_request( 'constants/prices', ['currency' => $currency] );

		return $result;
	}

}
