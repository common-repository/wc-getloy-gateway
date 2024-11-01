<?php

class Wc_Getloy_Gateway_Pipay_Kh extends Wc_Getloy_Payment_Gateway {


	/**
	 * Identifier string for gateway (needs to be set in concrete gateway)
	 *
	 * @var string   $id  Gateway identifier
	 */
	public $id = 'pipay_kh';

	/**
	 * Human-readable name of the gateway (will be shown in front-end)
	 *
	 * @var string   $name  Gateway name
	 */
	protected $name = 'Pi Pay';

	/**
	 * Gateway name (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_title  Gateway name
	 */
	protected $method_admin_title = 'Pi Pay (via GetLoy)';

	/**
	 * Gateway description (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_description  Gateway description
	 */
	protected $method_admin_description = 'Accept payments to your Pi Pay account through GetLoy.';

	/**
	 * Default gateway name (as shown in the payment method selection during checkout)
	 *
	 * @var string   $method_checkout_title  Gateway name
	 */
	protected $method_checkout_title = 'Pi Pay';

	/**
	 * Default gateway description (as shown in the payment method selection during checkout)
	 *
	 * @var string   $method_checkout_description  Gateway description
	 */
	protected $method_checkout_description = '';

	/**
	 * List of logos to display next to the payment method during checkout
	 *
	 * @var array[]   $method_checkout_logos  List of logos
	 */
	protected $method_checkout_logos = array(
		array(
			'filename' => 'pipay.svg',
			'title'    => 'Pi Pay',
			'style'    => 'square',
		),
	);

	/**
	 * Return associative array of additional arguments to be passed to the gateway
	 * connector constructor
	 *
	 * @return array Associative array of additional arguments
	 */
	protected function get_connector_constructor_args() {
		return array(
			'merchant_id' => $this->gateway_config['merchant_id'],
			'store_id'    => $this->gateway_config['store_id'],
			'device_id'   => $this->gateway_config['device_id'],
		);
	}

	/**
	 * Return associative array of additional arguments to be passed to the gateway
	 * connector's generateCreateTransactionParams method
	 *
	 * @return array Associative array of additional arguments
	 */
	protected function get_connector_create_transaction_args() {
		return array();
	}

	/**
	 * Return form field configuration for gateway-specific settings shown on the
	 * gateway's settings page in WooCommerce checkout settings
	 *
	 * See WooCommerce Settings API documentation for details:
	 *  https://docs.woocommerce.com/document/settings-api/
	 *
	 * @access protected
	 * @return array Settings form field configuration
	 */
	protected function get_gateway_form_fields() {
		return array(
			'merchant_id' => array(
				'title'       => __( 'Pi Pay merchant ID', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'You will receive your merchant ID by email from Pi Pay. It is the same for test and production mode.', 'wc-getloy-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'store_id'    => array(
				'title'       => __( 'Pi Pay store ID', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'You will receive your store ID by email from Pi Pay. It is the same for test and production mode.', 'wc-getloy-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'device_id'   => array(
				'title'       => __( 'Pi Pay device ID', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'You will receive your device ID by email from Pi Pay. It is the same for test and production mode.', 'wc-getloy-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Check if the gateway configuration is complete and correct.
	 *
	 * @access public
	 * @return array List of error messages for all issues identified during the check.
	 */
	public function check_config() {

		$config_errors = array();

		if ( ! $this->gateway_config['merchant_id'] ) {
			$config_errors[] = 'Pi Pay merchant ID not set.';
		} elseif ( 1 !== preg_match( '/^\d{1,19}$/', $this->gateway_config['merchant_id'] ) ) {
			$config_errors[] = 'Invalid Pi Pay merchant ID.';
		}

		if ( ! $this->gateway_config['store_id'] ) {
			$config_errors[] = 'Pi Pay store ID not set.';
		} elseif ( 1 !== preg_match( '/^\d{1,19}$/', $this->gateway_config['store_id'] ) ) {
			$config_errors[] = 'Invalid Pi Pay store ID.';
		}

		if ( ! $this->gateway_config['device_id'] ) {
			$config_errors[] = 'Pi Pay device ID not set.';
		} elseif ( strlen( $this->gateway_config['device_id'] ) > 255 ) {
			$config_errors[] = 'Invalid Pi Pay device ID.';
		}

		return $config_errors;
	}

}
