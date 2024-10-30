<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$copperx_settings = apply_filters(
	'wc_copperx_settings',
    [
        'enabled'         => [
            'type'        => 'checkbox',
            'label'       => __( 'Enable Copperx Payment', 'copperx'),
            'default'     => 'yes',
            'description' => '<span class="switch-description">' .__('When enable, Payment methods powered by Copperx will appear on checkout', 'copperx').'</span>',
            'class'       => 'switch'
        ],
        'secret_key' => [
            'title'       => '<span class="switch-title">' .__('Secret Key', 'copperx' ).'</span>',
            'label'       => __( 'Secret Key', 'copperx' ),
            'type'        => 'text',
            'default'     => '',
            'class'       => 'switch-text-field copperx-secret-key-text-field'
        ],
        'enabled_test'         => [
            'type'        => 'checkbox',
            'label'       => __( 'Test mode', 'copperx'),
            'default'     => 'no',
            'description' => '<span class="switch-description">' .__('Test transactions on Polygon Mumbai and Goreli network. Generate a test API key from the <a target="_blank" href="https://dashboard.copperx.dev/">Copperx test dashboard</a>', 'copperx').'</span>',
            'class'       => 'switch copperx-test-mode-switch'
        ],
        'test_secret_key' => [
            'title'       => '<span class="switch-title copperx-test-secret-key-label">' .__('Test Secret Key', 'copperx' ).'</span>',
            'label'       => __( 'Test Secret Key', 'copperx' ),
            'type'        => 'text',
            'default'     => '',
            'class'       => 'switch-text-field copperx-test-secret-key-text-field'
        ],
        'name'            => [
            'title'       => __( 'Name', 'copperx' ),
            'label'       => __( 'Name', 'copperx' ),
            'type'        => 'text',
            'default'     => __( 'Pay with crypto', 'copperx' ),
        ],
        'description'     => [
            'title'       => __( 'Description', 'copperx' ),
            'label'       => __( 'Description', 'copperx' ),
            'type'        => 'text',
            'default'     => __( 'Pay with Ether, USDC, USDT, SOL, MATIC and many more.', 'copperx' ),
        ],
        'nameCollection' =>  [
            'label'       => __( 'Name', 'copperx'),
            'type'        => 'checkbox',
            'default'     => 'no',
        ],
        'emailCollection' =>  [
            'label'       => __( 'Email', 'copperx'),
            'type'        => 'checkbox',
            'default'     => 'no',
        ],
        'phoneNumberCollection' =>  [
            'label'       => __( 'Phone number', 'copperx'),
            'type'        => 'checkbox',
            'default'     => 'no',
        ],
        'billingAddressCollection' =>  [
            'label'       => __( 'Billing Details', 'copperx'),
            'type'        => 'checkbox',
            'default'     => 'no',
        ],
        'shippingAddressCollection' =>  [
            'label'       => __( 'Shipping Details', 'copperx'),
            'type'        => 'checkbox',
            'default'     => 'no',
        ],
        'debug'           => [
            'type'        => 'checkbox',
            'label'       => __( 'Log error message', 'copperx' ),
            'default'     => 'no',
            'description' => __( 'When enable, Payment error log will be saved in woocommerce > Status > log', 'copperx' ),
        ],
    ]
);


return apply_filters(
	'wc_copperx_settings',
	$copperx_settings
);