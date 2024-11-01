<?php

/**
 * Abstract gateway connector class
 *
 * Defines interfaces and contains common logic shared by all gateway connectors.
 *
 * @link  https://geekho.asia
 * @since 1.0.0
 *
 * @package    Wc_Getloy_Gateway
 * @subpackage Wc_Getloy_Gateway/gateways
 */

abstract class Wc_Getloy_Payment_Gateway_Connector {


	/**
	 * List of allowed transaction status values (in GetLoy).
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array     $transactionStatusList    List of transaction status values.
	 */
	const TRANSACTION_STATUS_LIST = array(
		'successful',
		'timed_out',
		'failed',
	);

	/**
	 * GetLoy account token.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string     $getloy_token    Getloy account token.
	 */
	protected $getloy_token;

	/**
	 * URL to call upon successful completion of the transaction.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $merchantKey    The URL to call upon successful completion of the transaction.
	 */
	protected $callback_url;

	/**
	 * Activate test mode.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    bool    $test_mode    Activate test mode.
	 */
	protected $test_mode;

		/**
         * Activate PayWay V2.
         *
         * @since  1.3.0
         * @access protected
         * @var    bool    $paywayV2_mode    Activate PayWay API V2 mode.
         */
	protected $paywayV2_mode;

	/**
	 * String identifying the type and version of the requestor (e.g. "wc-getloy-gateway v1.0.0").
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    bool    $request_origin    Identifier for the type and version of the requestor.
	 */
	protected $request_origin;

	/**
	 * Additional gateway-specific arguments (associative array)
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $gateway_args    Additional connector arguments
	 */
	protected $gateway_args;

	/**
	 * Initiate the connector
	 *
	 * @param string $getloy_token   GetLoy account token
	 * @param string $request_origin String identifying the type and version of the requestor
	 * @param bool   $test_mode      Activate test mode (optional, default false)
	 * @param bool   $paywayV2_mode  Activate PayWay API V2 mode (optional, default false)
	 * @param array  $args           List of parameters to be passed to PayWay to initialize the new transaction
	 *
	 * @return array List of parameters to be passed to PayWay to initialize the new transaction
	 */
	public function __construct( $getloy_token, $request_origin, $test_mode, $paywayV2_mode, $args = array() ) {

		$this->getloy_token   = $getloy_token;
		$this->request_origin = $request_origin;
		$this->test_mode      = $test_mode;
		$this->paywayV2_mode  = $paywayV2_mode;
		$this->gateway_args   = $args;

	}

	/**
	 * Validate the parameters for a new transaction.
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 * @param string $callback_url  URL to call upon successful completion of the transaction
	 * @param array  $order         Associative array with order details.
	 * @param array  $args          Associative array with additional, gateway-specific transaction details (optional).
	 *
	 * @return string[] Returns list of errors, or empty array if no errors were found
	 */
	public function validateCreateTransactionParams( $transaction_id, $callback_url, array $order, array $args = array() ) {

		$errors                = array();
		$order['amount_total'] = is_scalar( $order['amount_total'] ) ? floatval( $order['amount_total'] ) : -1;
		if ( 0 >= $order['amount_total'] ) {
			$errors[] = sprintf(
				/* translators: %f total amount (e.g. "9.99") */
				__(
					'Transaction amount must be greater than 0, but %f given.',
					'wc-getloy-gateway'
				),
				$order['amount_total']
			);
		}

		$order['currency'] = is_scalar( $order['currency'] ) ? strtoupper( $order['currency'] ) : '';
		if ( ! isset( get_woocommerce_currencies()[ $order['currency'] ] ) ) {
			$errors[] = sprintf(
				/* translators: %s currency code (e.g. "EUR") */
				__(
					'Unknown transaction currency %s.',
					'wc-getloy-gateway'
				),
				$order['currency']
			);
		}

		return array_merge( $errors, $this->validateCreateTransactionParamsGateway( $transaction_id, $callback_url, $order, $args ) );
	}

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
	abstract protected function validateCreateTransactionParamsGateway( $transaction_id, $callback_url, array $order, array $args = array() );

	/**
	 * Validate the parameters for checking the status of a gateway transaction.
	 *
	 * @param string     $transaction_id     Unique identifier string for the transaction.
	 * @param array        $args             Associative array with additional, gateway-specific transaction details (optional).
	 *
	 * @return string[] Returns list of errors, or empty array if no errors were found
	 */
	// protected function validateCheckTransactionParams( $transaction_id, array $args = [] ) {
	// return $this->validateCheckTransactionParamsGateway( $transaction_id, $args );
	// }
	/**
	 * Gateway-specific validation of the parameters for checking the status of a transaction.
	 *
	 * @param string     $transaction_id     Unique identifier string for the transaction.
	 * @param array        $args             Associative array with additional, gateway-specific transaction details (optional).
	 *
	 * @return string[] Returns list of errors, or empty array if no errors were found
	 */
	// abstract protected function validateCheckTransactionParamsGateway( $transaction_id );
	/**
	 * Create new GetLoy transaction.
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 * @param string $callback_url  URL to call upon successful completion of the transaction
	 * @param array  $order         Associative array with order details.
	 * @param array  $args          Associative array with additional, gateway-specific transaction details (optional).
	 *
	 * @return array List of parameters to be passed to GetLoy to initialize the new transaction
	 */
	public function generateCreateTransactionParams( $transaction_id, $callback_url, array $order, array $args = array() ) {

		$getloy_merchant_hash = $this->hash( $this->getloy_token, $this->getloy_token );
		$req_time             = gmdate( 'YmdHis' ); // Format from ABA docs ... YYYYmmddHis

		$args['req_time'] = $req_time;

		$payee = array(
			'first_name'    => $order['billing_details']['first_name'],
			'last_name'     => $order['billing_details']['last_name'],
			'company'       => $order['billing_details']['company'],
			'address'       => $order['billing_details']['address'],
			'city'          => $order['billing_details']['city'],
			'state'         => $order['billing_details']['state'],
			'postcode'      => $order['billing_details']['postcode'],
			'country'       => $order['billing_details']['country'],
			'email_address' => $order['billing_details']['email'],
			'mobile_number' => $order['billing_details']['phone'],
		);

		$transactionGwParams = $this->getCreateTransactionGatewayParams(
			$transaction_id,
			$callback_url, // the merchant callback page (woocommerce)
			$order,
			$payee,
			$args
		);

		$getloy_auth_hash = $this->hash( $this->getloy_token . $transaction_id . $order['amount_total'], $this->getloy_token );


		$params = array(
			'v2'               => $this->paywayV2_mode,
			'req_time'         => $this->paywayV2_mode ? $req_time : null,
			'gct'              => $this->paywayV2_mode ? $transactionGwParams['status_token'] : null,
			'tid'              => $transaction_id,
			'provider'         => $args['gateway_id'],
			'provider_variant' => $args['gateway_variant_id'] ?: '',
			'merchant_hash'    => $getloy_merchant_hash,
			'auth_hash'        => $getloy_auth_hash,
			'callback'         => $callback_url,
			'test_mode'        => $this->test_mode,
			'request_origin'   => $this->request_origin,
			'request_ip'       => $order['order_ip'],
			'request_country'  => $order['order_country'],
			'payee'            => $payee,
			'order'            => array(
				'total_amount'    => $order ['amount_total'],
				'currency'        => $order ['currency'],
				'order_items'     => $order ['order_items'],
				'order_timestamp' => $order ['order_timestamp'],
			),
			'payment_provider' => $transactionGwParams,
		);

		// remove keys with empty values from $params
		$params = array_filter(
			$params,
			function ( $v ) {
				return isset( $v ) && '' !== $v;
			}
		);

		return $params;
	}

	/**
	 * Generate HMAC SHA512 hash value of the provided string.
	 *
	 * @param string $string String value to generate the hash for.
	 *
	 * @return string Hash value
	 */
	protected function hash( $data, $key ) {
		$hash = hash_hmac( 'sha512', $data, $key );
		return $hash;
	}

	/**
	 * Validate authentication hash from GetLoy.
	 *
	 * @param string $transaction_id Unique identifier string for the transaction.
	 * @param string $status         Transaction status.
	 * @param float  $amount_paid    Paid amount
	 * @param string $currency       Amount currency.
	 * @param string $auth_hash      Authentication hash for the transaction
	 *
	 * @return bool Validation result
	 */
	public function validate_callback_hash( $transaction_id, $status, $amount_paid, $currency, $auth_hash ) {
		if ( ! in_array( $status, self::TRANSACTION_STATUS_LIST ) ) {
			return false;
		}

		$hash = $this->hash( $this->getloy_token . '|' . $transaction_id . '|' . $amount_paid . '|' . $currency . '|' . $status, $this->getloy_token );
		return $hash === $auth_hash;
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
	abstract protected function getCreateTransactionGatewayParams( $transaction_id, $callback_url, array $order, array $payee, array $args = array() );
}
