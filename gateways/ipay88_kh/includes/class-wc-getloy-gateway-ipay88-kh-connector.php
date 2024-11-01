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
class Wc_Getloy_Gateway_Ipay88_Kh_Connector extends Wc_Getloy_Payment_Gateway_Connector {


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
	 * Validate transaction ID (has to be shorter than 30 characters)
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 *
	 * @return bool True if the value is valid.
	 */
	protected function validateTransactionId( $transaction_id ) {
		return ( strlen( $transaction_id ) < 30 );
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
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$signature = base64_encode(
			sha1(
				$this->gateway_args['merchant_key'] .
				$this->gateway_args['merchant_code'] .
				$transaction_id .
				sprintf( '%03d', $order['amount_total'] * 100 ) .
				$order['currency'],
				true
			)
		);

		return array(
			'signature'         => $signature,
			'payment_method_id' => $args['payment_method_id'],
			'merchant_code'     => $this->gateway_args['merchant_code'],
		);
	}
}
