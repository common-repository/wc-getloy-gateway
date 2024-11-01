<?php

/**
 * Defines the connector for the Getloy API
 *
 * @link  https://geekho.asia
 * @since 1.0.0
 *
 * @package    Wc_Getloy_Gateway
 * @subpackage Wc_Getloy_Gateway/gateway/includes
 */

/**
 * The Getloy API connector class.
 *
 * @since      1.0.0
 * @package    Wc_Getloy
 * @subpackage Wc_Getloy_Gateway/gateway/includes
 * @author     Jan Hagelauer, Geekho (Cambodia)
 */
class Wc_Getloy_Gateway_Payway_Kh_Connector extends Wc_Getloy_Payment_Gateway_Connector {

	/**
	 * Gateway-specific validation of the parameters for a new transaction.
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 * @param string $callback_url  URL to call upon successful completion of the transaction
	 * @param array  $order         Associative array with order details.
	 * @param array  $args          Associative array with additional, gateway-specific transaction details (optional).
	 *
	 * @return string[] Returns list of errors, or empty array if no errors were found
	 */
	protected function validateCreateTransactionParamsGateway( $transaction_id, $callback_url, array $order, array $args = array() ) {
		$errors = array();
		if ( ! $this->validateTransactionId( $transaction_id ) ) {
			$errors[] = sprintf(
				/* translators: %s transaction id (e.g. "WC-1234") */
				__(
					'Malformed transaction ID %s.',
					'wc-getloy-gateway'
				),
				$transaction_id
			);
		}
		return $errors;
	}

	/**
	 * Validate transaction ID (has to be shorter than 20 characters)
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 *
	 * @return bool True if the value is valid.
	 */
	protected function validateTransactionId( $transaction_id ) {
		return ( strlen( $transaction_id ) < 20 );
	}

	/**
	 * Transform an array of order items to a base64 encoded string.
	 *
	 * @param array $order_items Array of order items.
	 *
	 * @return array Order items as base64 encoded string
	 */
	protected function order_items_to_base64_string( array $order_items ) {
		$item_array = array();

		foreach ( $order_items as $order_item ) {
			$item_array[] = array(
				'name'     => $order_item['description'],
				'quantity' => $order_item['quantity'],
				'price'    => $order_item['unit_price'],
			);
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( mb_convert_encoding( wp_json_encode( $item_array ), 'UTF-8' ) );
	}

	/**
	 * Generate gateway-specific parameters to include in the create transaction request for GetLoy.
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 * @param string $callback_url  URL to call upon successful completion of the transaction
	 * @param array  $order         Associative array with order details.
	 * @param array  $payee         Associative array with payee details.
	 * @param array  $args          Associative array with additional, gateway-specific transaction details (optional).
	 *
	 * @return array List of gateway-specific parameters to be passed to GetLoy
	 */

	protected function getCreateTransactionGatewayParams( $transaction_id, $callback_url, array $order, array $payee, array $args = array() ) {
		$status_hash    = $this->hash(
			( $this->paywayV2_mode ? $args['req_time'] : null ) .
			$this->gateway_args['merchant_id'] .
			$transaction_id,
			$this->gateway_args['merchant_key']
		);
		$getloyCallback = base64_encode( 'https://api.getloy.com/transactions/status?token=' . $status_hash );
		$init_hash      = $this->hash(
			( $this->paywayV2_mode ? $args['req_time'] : null ) .
			( $this->gateway_args['merchant_id'] ) .
			$transaction_id .
			$order['amount_total'] .
			$this->order_items_to_base64_string( $order['order_items'] ) .
			( $this->paywayV2_mode ? $order['billing_details']['first_name'] : null ) .
			( $this->paywayV2_mode ? $order['billing_details']['last_name'] : null ) .
			( $this->paywayV2_mode ? $order['billing_details']['email'] : null ) .
			( $this->paywayV2_mode ? $args['payment_method'] : null ) .
			( $this->paywayV2_mode ? $getloyCallback : null ),
			$this->gateway_args['merchant_key']
		);

		return array(
			'merchant_id'    => $this->paywayV2_mode ? $this->gateway_args['merchant_id'] : null,
			'init_token'     => $init_hash,
			'status_token'   => $status_hash,
			'payment_method' => $args['payment_method'],
			'getloyCallback' => $getloyCallback,
		);
	}
}
