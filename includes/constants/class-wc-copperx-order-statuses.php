<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Copperx_Order_Statuses
 *
 * Contains a list of Payment statuses.
 *
 * @SuppressWarnings(LongVariable)
 */
class WC_Copperx_Order_Statuses {

    public static $COPPERX_ORDER_STATUSES = [
        'PENDING' => "pending",
        'BLOCKCHAIN_PENDING' => "blockchainpending",
        'INCOMPLETE_PAYMENT' => "incompletepayment",
        'EXPIRED' => "cancelled",
        'COMPLETE' => "processing"
    ];

}
