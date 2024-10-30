<?php
/**
 * Copperx Payment Gateway.
 *
 * Provides a Copperx Payment Gateway.
 *
 * @class   WC_Gateway_Copperx
 * @extends WC_Payment_Gateway
 * @since   1.0.1
 * @package WooCommerce/Classes/Payment
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WC_COPPERX_PLUGIN_PATH . '/includes/constants/class-wc-copperx-order-statuses.php';
require_once WC_COPPERX_PLUGIN_PATH . '/includes/constants/class-wc-copperx-currencies.php';

/**
 * WC_Gateway_Copperx Class.
 *
 * @SuppressWarnings("LongVariable")
 * @SuppressWarnings("TooManyPublicMethods")
 * @SuppressWarnings("ShortVariable")
 * @SuppressWarnings("ExcessiveClassComplexity")
 */
class WC_Gateway_Copperx extends WC_Payment_Gateway{

	/**
	 * Enable or disable logging.
 	 *
	 * @var bool Whether or not logging is enabled
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance.
 	 *
	 * @var WC_Logger Logger instance
	 */
	public static $log = false;

	public static $debug;

	public static $IncompletePaymentCheckingBound = 2; // Limit the number of time incomplete payment will be checked for status update.

	public static $testmode_notice_displayed = false;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct(){
		$this->id = "copperx";
		$this->order_button_text = __("Proceed with Copperx", "copperx");
		$this->method_title = __("Copperx", "copperx");
		$this->method_description = '<p>' .
		// translators: Introduction text at top of Copperx settings page.
		__( 'Accept payments in Bitcoin, ETH, and USDC seamlessly across various networks and wallets.', 'copperx' )
		. '</p><p>' .
		sprintf(
			// translators: Introduction text at top of Copperx settings page. Includes external URL.
			__( 'If you do not currently have a Copperx account, you can set one up here: %s', 'copperx' ),
			'<a target="_blank" href="https://copperx.io/">https://copperx.io/</a>'
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title       = $this->get_option( 'name' );
		$this->description = $this->get_option( 'description' );
		self::$debug       = 'yes' === $this->get_option( 'debug', 'no' );

		self::$log_enabled = self::$debug;

		add_action( 'admin_enqueue_scripts', [$this, 'copperx_admin_styles'] );
		add_action( 'admin_enqueue_scripts', [$this, 'copperx_admin_script'] );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options'] );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, '_custom_query_var' ], 10, 2 );
		add_action( 'woocommerce_api_wc_gateway_copperx', [ $this, 'handle_webhook' ] );
		add_action('admin_notices', [$this, 'copperx_testmode_notice']);
	}


	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level   Optional. Default 'info'.
	 *     emergency|alert|critical|error|warning|notice|info|debug
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, [ 'source' => 'copperx' ] );
		}
	}

	/**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @since  3.4.0
	 * @return bool
	 */
	public function needs_setup() {
		return true;
	}

	/**
	 * Register styles.
	 */
	function copperx_admin_styles() {
		wp_enqueue_style( 'copperx-admin-styles', plugin_dir_url( __FILE__ ) . 'assets/css/copperx-admin-styles.css' );
	}

	/**
	 * Register script.
	 */
	function copperx_admin_script() {
		wp_enqueue_script('copperx-admin-script', plugin_dir_url(__FILE__) . 'assets/script/copperx-admin-script.js');
	}


	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$image_path = wp_normalize_path(plugin_dir_path( __FILE__ ) . 'assets/images/tokens');
		$icon_html  = '';
		$icons = ['eth', 'usdc', 'usdt', 'btc'];

		foreach ( $icons as $icon ) {
			$path = realpath( $image_path . '/' . $icon . '.svg' );
			if ( $path && wp_normalize_path(dirname( $path )) === $image_path && is_file( $path ) ) {
				$url        = WC_HTTPS::force_https_url( plugins_url( '/assets/images/tokens/' . $icon . '.svg', __FILE__ ) );
				$icon_html .= '<img width="26" style="margin:2px; vertical-align: middle;" src="' . esc_attr( $url ) . '" alt="' . esc_attr__( $icon, 'copperx' ) . '" />';
			}
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}


	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = require dirname( __FILE__ ) . '/includes/admin/copperx-settings.php';
	}


	public function admin_options(){
		global $hide_save_button;
		$hide_save_button = true;

		$form_fields = $this->get_form_fields();
		echo '<div class="wc-copperx">';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if(isset($_GET['panel']) && $_GET['panel'] === 'settings' || $this->get_option('secret_key') !== ''){
			echo '<div class="">';
			echo '<h1 style="font-weight: bold; display: inline-block">' . esc_html( $this->get_method_title() );
			wc_back_link('', admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
			echo '</h1>';
			echo '</div>';
			$this->display_plugin_settings($form_fields);
			return;
		}

		$this->display_landing_screen();
	}


	private function display_landing_screen(){
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=copperx&panel=settings' );
		?>
			<div class="landing-screen-wrap">
				<div class="landing-logo">
					<img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) . 'assets/images/logo.svg'; ?>" alt="Logo" width="205" height="46" />
				</div>
				<div class="landing-content">
					<h2>Get Started with Copperx</h2>
					<p>Create copperx account to accept cryptocurrency payments directly onsite with just single click.</p>
					<p>We support Polygon, Ethereum, Binance Smart Chain and Solana chain.</p>
					<div class="btn-wrap">
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary">Continue</a>
					</div>
					<a href="https://docs.copperx.io/how-to-generate-an-api-key" target="_blank" class="api-btn">How to get API key?</a>
				</div>
			</div>
		<?php
	}


	/**
	 * Display the plugin settings page.
  	 *
	 * @param array $form_fields
	 *
	 * @SuppressWarnings("ExcessiveMethodLength")
	 */
	private function display_plugin_settings($form_fields){
		?>
			<div class="wc-copperx-container">
				<?php

					// Group form fields by section
					$general_settings = [
						'title' => __('General Settings', 'copperx'),
						'fields' => [
							'enabled' => $form_fields['enabled'],
							'secret_key' => $form_fields['secret_key'],
							'enabled_test' => $form_fields['enabled_test'],
							'test_secret_key' => $form_fields['test_secret_key'],
						],
						'description' =>sprintf(
							__('Enable or Disable Copperx on your store, Enter API keys, Turn on Test mode to run the transactions. %s %s %s', 'copperx'),
							'<div class="extra-info">',
							'<a target="_blank" href="https://docs.copperx.io/">View Copperx Plugin Docs</a>',
							// '<a target="_blank" href="https://copperx.io/">Watch Youtube Video</a>',
							'<a target="_blank" href="mailto:support@copperx.io">Get Support</a></div>'
						),
						'inner_title' => 'API Keys',
						'inner_description' => 'Visit Copperx Dashboard to get your API Keys'
					];

					$display_settings = [
						'title' => __('Display Settings', 'copperx'),
						'fields' => [
							'name' => $form_fields['name'],
							'description' => $form_fields['description'],
						],
						'description' =>__('This will help your customer select cryptocurrency payment option and know about which tokens and network are supported.', 'copperx'),
						'inner_title' => 'Display Setting',
						'inner_description' => 'Enter payment method details that will display on your checkout page.'
					];

					$checkout_settings = [
						'title' => __('Copperx Checkout Page Setting', 'copperx'),
						'fields' => [
							'nameCollection' => $form_fields['nameCollection'],
							'emailCollection' => $form_fields['emailCollection'],
							'phoneNumberCollection' => $form_fields['phoneNumberCollection'],
							'billingAddressCollection' => $form_fields['billingAddressCollection'],
							'shippingAddressCollection' => $form_fields['shippingAddressCollection'],
						],
						'description' =>__('Copperx checkout page comes with option to take additional data from your customer during checkout. If your website needed, you can enable this option to take data you required during transaction.', 'copperx'),
						'inner_title' => 'Take Additional Info',
						'inner_description' => 'Enter payment method details that will display on your checkout page.'
					];

					$webhook_settings = [
						'title' => __('Webhook Settings', 'copperx'),
						'fields' => [],
						'description' =>__('Copperx webhook effortlessly delivers payment confirmation messages to your website, ensuring smooth transaction management.', 'copperx'),
						'inner_title' => 'Webhook Settings',
						'inner_description' => __( ' To setup webhook follow the below steps.', 'copperx' ) . '<div class="copperx-webhook-setup-description">'.
						// translators: Instructions for setting up 'webhook' on settings page.
						'<ol><li>' .
						// translators: Step 1 of the instructions for 'webhook setup' on copperx dashboard.
						__( 'In your Copperx dashboard, go to \'Developer > Webhooks \' section.', 'copperx' )
						. '</li><li>' .
						// translators: Step 2 of the instructions to setup webhook URL.
						sprintf( __( 'Click \'Add Endpoint\' and paste the following URL: <p>( <b>%s</b> )</p>', 'copperx' ), add_query_arg( 'wc-api', 'WC_Gateway_Copperx', trailingslashit( get_home_url() )) )
						. '</li>' .
						'</ol></div>'
					];

					$additional_settings = [
						'title' => __('Additional Settings', 'copperx'),
						'fields' => [
							'debug' => $form_fields['debug'],
						],
						'description' =>__('Copperx comes with Webhook and multiple configuration settings for each payments. ', 'copperx'),
						'inner_title' => 'Debug Mode',
					];


					$sections = [$general_settings, $display_settings, $checkout_settings, $webhook_settings, $additional_settings];

					// Display fields by section
					foreach ($sections as $section) {
						?>
						<section class="setting-section">
							<div class="setting-section-info">
								<h2> <?php echo esc_html($section['title']) ?></h2>
								<p> <?php echo wp_kses($section['description'], 'post') ?> </p>
							</div>
							<div class="setting-section-form">
								<h2> <?php echo esc_html($section['inner_title']) ?></h2>
								<p> <?php echo array_key_exists("inner_description", $section) ? wp_kses($section['inner_description'], 'post') : '' ?> </p>
								<?php
									if(!empty($section['fields'])){
										$this->generate_settings_html($section['fields']);
									}
								?>
							</div>
						</section>
						<?php
					}
				?>
				<div class="submit">
					<button type="submit" class="button woocommerce-save-button copperx-save-settings-button" name="save" value="<?php esc_attr_e( 'Save Changes', 'copperx' ); ?>"><?php esc_html_e( 'Save changes', 'copperx' ); ?></button>
				</div>
			</div>
				</div>
		<?php
	}

	/**
	 * Shows and Hides the Copperx Test mode notice.
	 */
	public function copperx_testmode_notice() {
		$testmode_enabled = $this->get_option('enabled_test', 'no');

		if($testmode_enabled === 'no'){
			self::$testmode_notice_displayed = false;
		}

		if($testmode_enabled === 'yes' && !self::$testmode_notice_displayed){
			?>
			<div class="notice notice-warning copperx-testmode-notice">
				<p style="color:#CC7C20;"><?php echo wp_kses_post( '<b>Copperx is currently in test mode. Remember to disable it before going live. <a href="admin.php?page=wc-settings&tab=checkout&section=copperx">' . esc_html__( 'Click here', 'copperx' ) . '</a> to disable test mode.</b>'); ?></p>
			</div>
		<?php
			self::$testmode_notice_displayed = true;
		}
	}


	/**
	 * Process the payment and return the result.
     *
	 * @param int $order_id
	 *
	 * @return array
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$site_name = get_bloginfo('name');

		$this->init_api();

		$checkoutSession_item_names = '';
		$single_item_name = '';

		$order_items = $order->get_items();
		$numItems = count($order_items);
		$i = 0;

		foreach( $order_items as $item ) {
			if(++$i === $numItems && $numItems > 1) {
				$checkoutSession_item_names .= 'and ' . $item['quantity'] .' x '. $item['name'];
			}
			else if($numItems < 2){
				$product = wc_get_product($item['product_id']);

				if ($product && $product->get_attribute('copperx_product_name')) {
					$single_item_name = $product->get_attribute('copperx_product_name');
				} else {
					$single_item_name = $item['name'];
				}

				$checkoutSession_item_names .= $item['quantity'] .' x '. $item['name'];
			}
			else {
				$checkoutSession_item_names .= $item['quantity'] .' x '. $item['name'] . ', ';
			}
		}

		$productName = $numItems < 2 ? $single_item_name : 'Buy '. $order->get_item_count() . ' items from ' . $site_name;

		try {
			$order_items_data = [];

			array_push($order_items_data, [
						"priceData" => (object) [
							"productData" => (object) [
								"name" => $productName,
								"description" => 'Buy ' . $checkoutSession_item_names . ' from ' . $site_name . '.' . ' (#' . $order_id . ')',
								"metadata" => (object) [
									"order_id" => $order_id,
									"source" => 'woocommerce',
									"subtotal" => $order->get_subtotal(),
									"total_discount" => $order->get_total_discount(),
									"total_fees" => $order->get_total_fees(),
									"total_shipping" => $order->get_shipping_total(),
									"total_tax" => $order->get_total_tax(),
									"currency" => $order->get_currency(),
								],
							],
							"currency" => 'usdc',
							"unitAmount" => $this->getCurrencyToUSDCValue($order->get_total(), strtolower($order->get_currency())),
						],
					]);
		} catch ( Exception $e ) {
			$order_items_data = null;
		}

		// Create a new checkout.
		$metadata = (object) [
			'order_id'  => $order->get_id(),
			'order_key' => $order->get_order_key(),
			'user_id' => $order->get_user_id(),
            'source' => 'woocommerce'
		];

		$customerData = $this->get_customerData($order->data['billing']);

		$result   = Copperx_API_Handler::create_checkout(
			$order->get_id(),
			$order_items_data,
			$metadata,
			'yes' === $this->get_option( 'nameCollection', 'no' ),
			'yes' === $this->get_option( 'emailCollection', 'no' ),
			'yes' === $this->get_option( 'phoneNumberCollection', 'no' ),
			'yes' === $this->get_option( 'billingAddressCollection', 'no' ),
			'yes' === $this->get_option( 'shippingAddressCollection', 'no' ),
			$customerData,
			$this->get_return_url( $order ),
			$this->get_cancel_url( $order )
		);

		if ( ! $result[0] ) {
			return [ 'result' => 'fail' ];
		}

		$checkout = $result[1];

		$order->update_meta_data( '_copperx_checkout_id', $checkout['id'] );
		$order->update_meta_data( '_copperx_checkout_url', $checkout['url'] );
		$order->save();

		if(WC()->session->has_session()){
			WC()->session->forget_session(); // will not destory the session.
		}

		return [
			'result'   => 'success',
			'redirect' => $checkout['url'],
		];
	}


	/**
	 * Init the API class and set the API key etc.
	 */
	protected function init_api() {
		include_once dirname( __FILE__ ) . '/includes/class-copperx-api-handler.php';

		Copperx_API_Handler::$log     = get_class( $this ) . '::log'; // assigning log function to log variable function in Copperx_API_Handler.

		if($this->get_option('enabled_test', 'no') === 'yes'){
			Copperx_API_Handler::$api_url = 'https://api.copperx.dev/api/v1/';
			Copperx_API_Handler::$api_key = $this->get_option( 'test_secret_key' );
		}else{
			Copperx_API_Handler::$api_key = $this->get_option( 'secret_key' );
		}

	}


	/**
	 * Get the USDC value for fiat currency (Supported CAD, USD only).
  *
	 * @param float $amount
	 * @param string $currency
	 *
	 * @return string
	 */
	protected function getCurrencyToUSDCValue($amount, $currency) {
		try {
			if(WC_Copperx_Currencies::is_valid_currency( $currency )){
				$result = Copperx_API_Handler::get_currencyPrices($currency);

				if ( ! $result[0] ) {
					throw new Exception('Failed to fetch ' . $currency . ' prices.');
				}

				$data = reset(array_filter($result[1]['prices'], function($obj){
					if(isset($obj['price']) && ($obj['currency'] === 'usdc')){
						return true;
					}
				}));

				if($data){
					// To avoid underpaid payments in 8 decimals,
					// ceil((amount/$data['price'])*pow(10, 3)) only takes 6 values after decimal point and make other 2 values to zero.
					$usdcValue = ceil(($amount / $data['price'])*pow(10, 6)) * pow(10, 2);
					return (string)$usdcValue;
				}else{
					throw new Exception('Failed to fetch ' . $currency . ' prices.');
				}
			}else{
				throw new Exception('Copperx does not support ' . $currency . ' currency.');
			}
		} catch (Exception $error) {
		  self::log('Error fetching ' . $currency . ' prices: ' . $error->getMessage());
		  throw $error;
		}
	}

	/**
	 * Get the customer data from WooCommerce order.
  *
	 * @param array $billing
	 *
	 * @return object
	 */
	function get_customerData($billing){

		$address = array_filter([
			"line1" => $billing['address_1'],
			"line2" => $billing['address_2'],
			"city" => $billing['city'],
			"state" => $billing['state'],
			"postalCode" => $billing['postcode'],
			"country" => $billing['country'],
		]);

		$customerData = array_filter([
			'name' => (!empty($billing['first_name']) && !empty($billing['last_name'])) ? $billing['first_name'] . " " . $billing['last_name'] : (!empty($billing['first_name']) ? $billing['first_name'] : $billing['last_name']),
			'email' => $billing['email'],
			'phone' => $billing['phone'],
			'address' => !empty($address) ? (object) $address : '',
		]);

		return (object)$customerData;
	}


	/**
	 * Get the cancel url.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	public function get_cancel_url( $order ) {
		$return_url = $order->get_cancel_order_url();

		if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
			$return_url = str_replace( 'http:', 'https:', $return_url );
		}

		return apply_filters( 'woocommerce_get_cancel_url', $return_url, $order );
	}


	/**
	 * Check payment statuses on orders and update order statuses.
	 */
	public function check_orders() {
		$this->init_api();

		// Check the status of non-archived copperx orders.
		$orders = wc_get_orders( [ 'copperx_archived' => false, 'status'   => [ 'wc-pending', 'wc-blockchainpending', 'wc-incompletepayment'] ] );

		foreach ( $orders as $order ) {
			$checkout_id = $order->get_meta( '_copperx_checkout_id' );

			usleep( 300000 );  // Ensure we don't hit the rate limit.
			$result = Copperx_API_Handler::get_checkoutSessionStatus($checkout_id);

			if ( ! $result[0] ) {
				self::log( 'Failed to fetch order updates for: ' . $order->get_id() );
				continue;
			}

			$this->_update_order_status( $order, $result[1]);
			$curr_status = $order->get_status();
			self::log( 'Order id '.$order->get_id().', payment status => '.strtoupper($curr_status));

		}

	}


	/**
	 * Handle requests sent to webhook.
	 * Refer https://github.com/woocommerce/woocommerce-gateway-stripe/blob/develop/includes/class-wc-stripe-webhook-handler.php
	 */
	public function handle_webhook() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_SERVER['REQUEST_METHOD'] )
			|| ( 'POST' !== $_SERVER['REQUEST_METHOD'] )
			|| ! isset( $_GET['wc-api'] )
			|| ( 'WC_Gateway_Copperx' !== $_GET['wc-api'] )
		) {
			return;
		}
		// phpcs:enable

		$request_body = file_get_contents( 'php://input' );
		$result = $this->validate_webhook( $request_body );

		if ( $result[0] ) {

			self::log( 'Webhook received for checkoutSession ID: ' . $result[1]['checkoutSession']['id']);

			$this->_update_order_status( $result[1]['wc_order'], $result[1]['checkoutSession'] );
			$curr_status = $result[1]['wc_order']->get_status();
			self::log( 'Order id '.$result[1]['wc_order']->get_id().', payment status => '.strtoupper($curr_status));

			status_header( 200 );
			exit;
		} else {
			if($result[1] !== null && !empty($result[1])){
				self::log('Copperx Incoming webhook validation failed for Webhook ID ==> '. print_r( $result[1]['id'], true ));
				self::log('Failed webhook: Checkout Session ID ==> ' . print_r( $result[1]['data']['object']['id'], true ));
				self::log('Failed webhook: Order Metadata ==> ' . print_r( $result[1]['data']['object']['metadata'], true ));
			}

			status_header( 204 );
			exit;
		}
	}


	/**
	 * Check Copperx webhook request is valid.
	 *
	 * @param string $request_body
	 *
	 * @return array
	 *
	 * @SuppressWarnings(CyclomaticComplexity)
	 */
	public function validate_webhook( $request_body ) {
		if ( ! isset( $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ) || empty( $request_body ) ) {
			return [false, null];
		}

		$event = json_decode( $request_body, true );
		$event_data = $event['data']['object'];

		if($event['type'] === 'checkout_session.completed'
			|| $event['type'] === 'checkout_session.canceled'
			|| $event['type'] === 'checkout_session.expired'
		){

			$this->init_api();

			$result = Copperx_API_Handler::get_checkoutSession($event_data['id']);
			if ( ! $result[0] ) {
				return $result; // here $result will be [false, errorCode].
			}
			elseif(isset( $result[1]['metadata']['order_id'] ) // compare the event recieved with the fetched result.
				&& isset( $result[1]['metadata']['order_key'] )
				&& $event_data['metadata']['order_id'] === $result[1]['metadata']['order_id']
				&& $event_data['metadata']['order_key'] === $result[1]['metadata']['order_key']
				&& $event_data['status'] === $result[1]['status']
			){

				$order = wc_get_order( $result[1]['metadata']['order_id'] );

				// if order exists then compare the fetched result data with the order meta data.
				if($order
					&& $result[1]['id'] === $order->get_meta('_copperx_checkout_id')
					&& $result[1]['metadata']['order_key'] === $order->get_order_key()
					&& $order->get_meta('_copperx_archived') !== 'yes'
				){

					$data['checkoutSession'] = $result[1];
					$data['wc_order'] = $order;

					return [true, $data];
				}
			}
		}

		return [false, $event];
	}


	/**
	 * Update the status of an order.
     *
	 * @param WC_Order $order
	 * @param array    $result (It can be the response of get_checkoutSession() and get_checkoutSessionStatus().)
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function _update_order_status( $order, $result ) {
		$order_statuses = WC_Copperx_Order_Statuses::$COPPERX_ORDER_STATUSES;
		$prev_status = $order->get_status();
		$incompletePaymentChecking_count = $order->get_meta( '_copperx_incomplete_payment_checking_count', true );

		$status = $result['status'];
		$txn_hash = array_key_exists("transactionHash", $result) ? $result["transactionHash"] : null;

		if($status === "open" && !is_null($txn_hash)){
			$status = $order_statuses['BLOCKCHAIN_PENDING'];
		}
		elseif($status === "open" && 'pending' == $order->get_status()){
			$status = $order_statuses['PENDING'];
		}
		elseif($status === "expired"){
			$status = $order_statuses['EXPIRED'];
		}
		elseif($status === "complete"){
			$status = $order_statuses['COMPLETE'];
		}
		elseif($status === "incomplete"){
			if(!$incompletePaymentChecking_count){
				$incompletePaymentChecking_count = 0;
			}
			$incompletePaymentChecking_count += 1;
			$order->update_meta_data( '_copperx_incomplete_payment_checking_count', $incompletePaymentChecking_count);
			$order->save();

			$status = $order_statuses['INCOMPLETE_PAYMENT'];
		}

		if($status !== $prev_status){

			if($status === $order_statuses['EXPIRED']){
				$order->update_status( $order_statuses['EXPIRED'], __( 'Copperx payment expired.', 'copperx' ) );
			}
			elseif($status === $order_statuses['BLOCKCHAIN_PENDING']){
				$order->update_status( $order_statuses['BLOCKCHAIN_PENDING'], __( 'Copperx payment detected, but awaiting blockchain confirmation.', 'copperx' ) );
			}
			elseif($status === $order_statuses['INCOMPLETE_PAYMENT']){
				$order->update_status( $order_statuses['INCOMPLETE_PAYMENT'], __( 'Copperx payment is partially paid by customer.', 'copperx' ) );
			}
			elseif($status === $order_statuses['COMPLETE']){
				$order->update_status( $order_statuses['COMPLETE'], __( 'Copperx payment was successfully processed.', 'copperx' ) );
				$order->payment_complete();
			}

		}

		// If incompletePaymentChecking_count is greater or equal than the Bound, it will archive the order.
		if ( in_array( $status, [$order_statuses['INCOMPLETE_PAYMENT']], true ) && $incompletePaymentChecking_count>=self::$IncompletePaymentCheckingBound) {
			self::log( 'Archiving order ==> : ' . $order->get_order_number() );
			$order->update_meta_data( '_copperx_archived', 'yes' );
			$order->save();
		}

		if ( in_array( $status, [$order_statuses['COMPLETE'], $order_statuses['EXPIRED']], true )) {
			self::log( 'Archiving order ==> : ' . $order->get_order_number() );
			$order->update_meta_data( '_copperx_archived', 'yes' );
			$order->save();
		}

	}


	/**
	 * Handle a custom 'copperx_archived' query var to get orders
	 * payed through Copperx with the '_copperx_archived' meta.
     *
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Order_Query.
	 *
	 * @return array modified $query
	 */
	public function _custom_query_var( $query, $query_vars ) {
		if ( array_key_exists( 'copperx_archived', $query_vars ) ) {
			$query['meta_query'][] = [
				'key'     => '_copperx_archived',
				'compare' => $query_vars['copperx_archived'] ? 'EXISTS' : 'NOT EXISTS',
			];
			// Limit only to orders payed through Copperx.
			$query['meta_query'][] = [
				'key'     => '_copperx_checkout_id',
				'compare' => 'EXISTS',
			];
		}

		return $query;
	}
}
