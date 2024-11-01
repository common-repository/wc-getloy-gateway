<?php

class Wc_Getloy_Gateway_Ipay88_Kh extends Wc_Getloy_Payment_Gateway {


	/**
	 * Identifier string for gateway (needs to be set in concrete gateway)
	 *
	 * @var string   $id  Gateway identifier
	 */
	public $id = 'ipay88_kh';

	/**
	 * Human-readable name of the gateway (will be shown in front-end)
	 *
	 * @var string   $name  Gateway name
	 */
	protected $name = 'iPay 88';

	/**
	 * Gateway name (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_title  Gateway name
	 */
	protected $method_admin_title = 'iPay88 (via GetLoy)';

	/**
	 * Gateway description (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_description  Gateway description
	 */
	protected $method_admin_description = 'Accept payments by credit card, digital wallet or bank account through GetLoy using iPay88.';

	/**
	 * Default gateway name (as shown in the payment method selection during checkout)
	 *
	 * @var string   $method_checkout_title  Gateway name
	 */
	protected $method_checkout_title = 'iPay88';

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
		'cc'         => array(
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
					'filename' => 'jcb.svg',
					'title'    => 'JCB',
					'style'    => 'card',
				),
				array(
					'filename' => 'diners-club.svg',
					'title'    => 'Diners Club',
					'style'    => 'card',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 1,
			),
		),
		'upay'       => array(
			'title'       => 'UnionPay',
			'name'        => 'UnionPay',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'unionpay.svg',
					'title'    => 'UnionPay',
					'style'    => 'card',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 15,
			),
		),
		'pipay'      => array(
			'title'       => 'Pi Pay',
			'name'        => 'Pi Pay',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'pipay.svg',
					'title'    => 'Pi Pay',
					'style'    => 'square',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 11,
			),
		),
		'wing'       => array(
			'title'       => 'Wing',
			'name'        => 'Wing',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'wing.svg',
					'title'    => 'Wing',
					'style'    => 'square',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 123,
			),
		),
		'metfone'    => array(
			'title'       => 'Metfone eMoney',
			'name'        => 'Metfone eMoney',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'metfone.svg',
					'title'    => 'Metfone eMoney',
					'style'    => 'square',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 9,
			),
		),
		'alipaybc'   => array(
			'title'       => 'Alipay (barcode)',
			'name'        => 'Alipay (barcode)',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'alipay.svg',
					'title'    => 'Alipay',
					'style'    => 'square',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 234,
			),
		),
		'alipayqr'   => array(
			'title'       => 'Alipay (QR code)',
			'name'        => 'Alipay (QR code)',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'alipay.svg',
					'title'    => 'Alipay',
					'style'    => 'square',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 233,
			),
		),
		'acledaxpay' => array(
			'title'       => 'Acleda XPAY',
			'name'        => 'Acleda Bank Account',
			'description' => '',
			'logos'       => array(
				array(
					'filename' => 'acleda.svg',
					'title'    => 'Acleda XPAY',
					'style'    => 'square',
				),
			),
			'currencies'  => array( 'USD' ),
			'config'      => array(
				'method_id' => 3,
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
			'merchant_code' => 'yes' === $this->testmode
				? $this->gateway_config['test_merchant_code']
				: $this->gateway_config['production_merchant_code'],
			'merchant_key'  => 'yes' === $this->testmode
				? $this->gateway_config['test_merchant_key']
				: $this->gateway_config['production_merchant_key'],
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
			return array( 'payment_method_id' => $this->method_variants[ $this->method_variant ]['config']['method_id'] );
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
			'section_test_details'       => array(
				'title'       => __( 'iPay88 demo merchant credentials', 'wc-getloy-gateway' ),
				'type'        => 'title',
				'description' => __(
					'You will receive the credentials from iPay88 with your demo account.',
					'wc-getloy-gateway'
				),
			),
			'test_merchant_code'         => array(
				'title'       => __( 'iPay88 demo merchant code', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => '',
				'default'     => '',
				'desc_tip'    => false,
			),
			'test_merchant_key'          => array(
				'title'       => __( 'iPay88 demo merchant key', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => '',
				'default'     => '',
				'desc_tip'    => false,
			),
			'section_production_details' => array(
				'title'       => __( 'iPay88 production merchant credentials', 'wc-getloy-gateway' ),
				'type'        => 'title',
				'description' => __(
					'You will receive the credentials from iPay88 after completing the tests with the demo merchant.',
					'wc-getloy-gateway'
				),
			),
			'production_merchant_code'   => array(
				'title'       => __( 'iPay88 production merchant code', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => '',
				'default'     => '',
				'desc_tip'    => false,
			),
			'production_merchant_key'    => array(
				'title'       => __( 'iPay88 production merchant key', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => '',
				'default'     => '',
				'desc_tip'    => false,
			),
			'section_ipay88kh_variants'  => array(
				'title'       => __( 'iPay88 payment method selection', 'wc-getloy-gateway' ),
				'type'        => 'title',
				'description' => __(
					'Select all payment methods you want to allow in your shop.',
					'wc-getloy-gateway'
				),
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
				'title'       => '%s',
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

		if ( 'yes' == $this->testmode && ! $this->gateway_config['test_merchant_code'] ) {
			$config_errors[] = 'iPay88 demo merchant code not set.';
		}

		if ( 'yes' != $this->testmode && ! $this->gateway_config['production_merchant_code'] ) {
			$config_errors[] = 'iPay88 production API code not set.';
		}

		if ( 'yes' == $this->testmode && ! $this->gateway_config['test_merchant_key'] ) {
			$config_errors[] = 'iPay88 demo merchant key not set.';
		}

		if ( 'yes' != $this->testmode && ! $this->gateway_config['production_merchant_key'] ) {
			$config_errors[] = 'iPay88 production API key not set.';
		}
		return $config_errors;
	}

}
