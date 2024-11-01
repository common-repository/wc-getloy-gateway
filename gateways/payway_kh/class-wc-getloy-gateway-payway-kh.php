<?php

class Wc_Getloy_Gateway_Payway_Kh extends Wc_Getloy_Payment_Gateway {


	/**
	 * Identifier string for gateway (needs to be set in concrete gateway)
	 *
	 * @var string   $id  Gateway identifier
	 */
	public $id = 'payway_kh';

	/**
	 * Human-readable name of the gateway (will be shown in front-end)
	 *
	 * @var string   $name  Gateway name
	 */
	protected $name = 'PayWay';

	/**
	 * Gateway name (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_title  Gateway name
	 */
	protected $method_admin_title = 'PayWay (via GetLoy)';

	/**
	 * Gateway description (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_description  Gateway description
	 */
	protected $method_admin_description = 'Accept international credit card payments to your Cambodian bank account through GetLoy using PayWay by ABA Bank.';

	/**
	 * Default gateway name (as shown in the payment method selection during checkout)
	 *
	 * @var string   $method_checkout_title  Gateway name
	 */
	protected $method_checkout_title = 'Debit/Credit Card or ABA Pay';

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
	protected $method_checkout_logos = array();

	/**
	 * List of payment method variants supported by the gateway. Each variant will be displayed as a
	 * separate payment option on the checkout page.
	 *
	 * @var string[] $method_variants List of payment method variants
	 */
	protected $method_variants = array(
		'cards'  => array(
			'title'       => 'Debit / Credit Card',
			'name'        => 'Debit / Credit Card',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'mastercard.svg',
					'title'    => 'MasterCard',
					'style'    => 'card',
					'variants' => array(
						'dark' => 'mastercard-dark.svg',
					),
				),
				array(
					'filename' => 'visa.svg',
					'title'    => 'Visa',
					'style'    => 'card',
					'variants' => array(
						'dark' => 'visa-dark.svg',
					),
				),
				array(
					'filename' => 'unionpay.svg',
					'title'    => 'UnionPay',
					'style'    => 'card',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'payment_method' => 'cards',
			),
		),
		'abapay' => array(
			'title'       => 'ABA Pay',
			'name'        => 'ABA Pay',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'aba-pay.svg',
					'title'    => 'ABA Pay',
					'style'    => 'card',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'payment_method' => 'abapay',
			),
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
			'merchant_id'  => $this->gateway_config['merchant_id'],
			'merchant_key' => 'yes' === $this->testmode ? $this->gateway_config['test_api_key'] : $this->gateway_config['production_api_key'],
		);
	}

	/**
	 * Return associative array of additional arguments to be passed to the gateway
	 * connector's generateCreateTransactionParams method
	 *
	 * @return array Associative array of additional arguments
	 */
	protected function get_connector_create_transaction_args() {
		if ( '' !== $this->method_variant ) {
			return array(
				'payment_method' => $this->method_variants[ $this->method_variant ]['config']['payment_method'],
				// TODO: generate full return URL (with token) here
				'return_url'     => 'https://api.getloy.com/status?token=',
			);
		}
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
			'merchant_id'        => array(
				'title'       => __( 'PayWay merchant ID', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'You will receive your merchant ID by email from ABA Bank. It is the same for test and production mode.', 'wc-getloy-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'test_api_key'       => array(
				'title'       => __( 'PayWay test API key', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'You will receive this key from ABA Bank with your test account credentials.', 'wc-getloy-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'production_api_key' => array(
				'title'       => __( 'PayWay production API key', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'You will receive this key from ABA Bank after completing the tests.', 'wc-getloy-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Return form field configuration for payment method variant-specific settings
	 * shown on the gateway's settings page in WooCommerce checkout settings
	 *
	 * The fields defined in this method will be displayed once for each of the
	 * gateway's registered payment method variants.
	 *
	 * @access protected
	 * @return array Settings form field configuration
	 */
	protected function get_gateway_variant_form_field_template() {
		return array(
			'section_variant' => array(
				'title'       => 'Payment method %s',
				'type'        => 'title',
				'description' => '',
				'class'       => 'getloy-method-variant-block',
			),
			'enabled'         => array(
				'title'       => '',
				/* translators: %s payment method variant name (e.g. "AliPay") */
				'label'       => __( 'Enable payment with %s', 'wc-getloy-gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
				'desc_tip'    => false,
			),
			'title'           => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s payment method variant name (e.g. "AliPay") */
				'description' => __( 'The title of the %s payment option the user sees during checkout.', 'wc-getloy-gateway' ),
				'default'     => function ( $variant_config ) { return $variant_config['title']; },
				'desc_tip'    => false,
			),
			'description'     => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s payment method variant name (e.g. "AliPay") */
				'description' => __( 'The description of the %s payment option the user sees during checkout.', 'wc-getloy-gateway' ),
				'default'     => function ( $variant_config ) { return $variant_config['description']; },
				'desc_tip'    => false,
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
			$config_errors[] = 'PayWay merchant ID not set.';
		}

		if ( 'yes' == $this->testmode && ! $this->gateway_config['test_api_key'] ) {
			$config_errors[] = 'PayWay test API key not set.';
		}

		if ( 'yes' != $this->testmode && ! $this->gateway_config['production_api_key'] ) {
			$config_errors[] = 'PayWay production API key not set.';
		}
		return $config_errors;
	}
}
