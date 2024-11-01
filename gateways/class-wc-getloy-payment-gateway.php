<?php

/**
 * Abstract gateway class
 *
 * Defines interfaces and contains common logic shared by all gateways.
 *
 * @link  https://geekho.asia
 * @since 1.0.0
 *
 * @package    Wc_Getloy_Gateway
 * @subpackage Wc_Getloy_Gateway/gateways
 */

abstract class Wc_Getloy_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Instance of GetLoy connector
	 *
	 * @var Wc_Getloy_Payment_Gateway_Connector   $getloy_connector  Instance of GetLoy connector
	 */
	protected $getloy_connector;

	/**
	 * Plugin ID used for generating the option key in the options table
	 *
	 * @var string   $plugin_id  Plugin ID
	 */
	public $plugin_id = 'getloy_gateway_';

	/**
	 * Human-readable name of the gateway (will be shown in front-end)
	 *
	 * @var string   $name  Gateway name
	 */
	protected $name;

	/**
	 * Identifier for the payment method variant
	 *
	 * @var string   $method_variant  Gateway name
	 */
	protected $method_variant;

	/**
	 * Gateway name (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_title  Gateway name
	 */
	protected $method_admin_title;

	/**
	 * Gateway description (as shown on the tab in the WooCommerce checkout settings)
	 *
	 * @var string   $method_admin_description  Gateway description
	 */
	protected $method_admin_description;

	/**
	 * Default gateway name (as shown in the payment method selection during checkout)
	 *
	 * @var string   $method_checkout_title  Gateway name
	 */
	protected $method_checkout_title;

	/**
	 * Default gateway description (as shown in the payment method selection during checkout)
	 *
	 * @var string   $method_checkout_description  Gateway description
	 */
	protected $method_checkout_description;

	/**
	 * List of logos to display next to the payment method during checkout
	 *
	 * @var array[]   $method_checkout_logos  List of logos
	 */
	protected $method_checkout_logos;

	/**
	 * List of payment method variants supported by the gateway. Each variant will be displayed as a
	 * separate payment option on the checkout page.
	 *
	 * @var string[] $method_variants List of payment method variants
	 */
	protected $method_variants = array();

	/**
	 * Constructor for the gateway.
	 *
	 * @var string $method_variant Identifier for the payment method variant to use (default is
	 *                             default variant)
	 *
	 * @return void
	 */
	public function __construct( $method_variant = '' ) {
		$plugin                  = Wc_Getloy_Gateway::instance();
		$this->has_fields        = false;
		$this->plugin_identifier = sprintf( '%s v%s', $plugin->get_plugin_name(), $plugin->get_version() );
		$this->method_variant    = $method_variant;
		$this->id_parent         = $this->id;
		if ( '' !== $method_variant ) {
			$this->id .= '_' . $method_variant;
		}

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->method_title       = __( $this->method_admin_title, 'wc-getloy-gateway' );
		$this->method_description = __( $this->method_admin_description, 'wc-getloy-gateway' );

		$this->enabled               = $this->get_enabled_option();
		$this->title                 = $this->get_variant_option( 'title' );
		$this->description           = $this->get_variant_option( 'description' );
		$this->testmode              = $this->get_option( 'testmode' );
		$this->paywayV2_mode         = $this->get_option( 'paywayV2_mode' );
		$this->getloy_token          = $this->get_option( 'getloy_token' );
		$this->transaction_id_prefix = $this->get_option( 'transaction_id_prefix' );
		$this->transaction_id_suffix = $this->get_option( 'transaction_id_suffix' );
		$this->gateway_config        = $this->get_gateway_config();
		$this->gateway_classname     = $plugin->get_gateway_classname( $this->id_parent );
		$this->connector_classname   = $plugin->get_gateway_connector_classname( $this->id_parent );
		$this->connector_filename    = $plugin->get_gateway_connector_filename( $this->id_parent );

		$this->config_error = ! $this->check_setup( false );
		if ( ! $this->config_error ) {
			if ( ! class_exists( $this->connector_classname ) ) {
				include plugin_dir_path( dirname( __FILE__ ) ) . 'gateways/' . $this->id_parent . '/' . $this->connector_filename;
			}

			$connector_args = $this->get_connector_constructor_args();

			$connector_classname    = $this->connector_classname;
			$this->getloy_connector = new $connector_classname(
				$this->getloy_token,
				$this->plugin_identifier,
				'yes' == $this->testmode,
				'yes' == $this->paywayV2_mode,
				$connector_args
			);
		}
	}

	/**
	 * Return the name of the option in the WP DB.
	 * Overrides method from WC_Settings_API to  use option keys of main gateway for variants.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . $this->id_parent . '_settings';
	}

	/**
	 * Get option value for method variant.
	 *
	 * Gets an option, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key Option key.
	 * @param  mixed  $empty_value Value when empty.
	 * @return string The value specified for the option or a default value for the option.
	 */
	protected function get_variant_option( $key, $empty_value = null ) {
		if ( '' === $this->method_variant ) {
			return $this->get_option( $key, $empty_value );
		}

		return $this->get_option(
			$this->method_variant . '_' . $key,
			! is_null( $empty_value )
				? $empty_value
				: (
					array_key_exists( $key, $this->method_variants[ $this->method_variant ] )
					? $this->method_variants[ $this->method_variant ][ $key ]
					: null
				)
		);
	}

	/**
	 * Get the value of the 'enabled' option for the gateway or method variant.
	 * A variant will always be marked as disabled if the parent gateway is disabled.
	 *
	 * @return string Option value
	 */
	protected function get_enabled_option() {
		$parent_enabled = $this->get_option( 'enabled' );
		if ( '' === $this->method_variant || 'no' === $parent_enabled ) {
			return $parent_enabled;
		}

		return $this->get_variant_option( 'enabled' );
	}

	/**
	 * Get the name of the gateway and variant (if any)
	 *
	 * @return string The gateway name
	 */
	protected function get_name() {
		if ( '' === $this->method_variant ) {
			return $this->name;
		}
		return sprintf(
			'%s (%s)',
			$this->name,
			$this->method_variants[ $this->method_variant ]['name']
		);
	}
	/**
	 * Generate an associative array with gateway-specific configuration values
	 *
	 * @return array Associative array with gateway-specific configuration values
	 */
	protected function get_gateway_config() {
		$config_keys = array_keys( $this->filter_form_fields( $this->get_gateway_form_fields() ) );
		$config      = array();
		foreach ( $config_keys as $config_key ) {
			$config[ $config_key ] = $this->get_option( $config_key );
		}

		return $config;
	}

	/**
	 * Return associative array of additional arguments to be passed to the gateway
	 * connector constructor
	 *
	 * @return array Associative array of additional arguments
	 */
	abstract protected function get_connector_constructor_args();

	/**
	 * Get filtered gateway icon HTML code
	 *
	 * @return string Filtered HTML code for the icons
	 */
	public function get_icon() {
		return apply_filters( 'woocommerce_gateway_icon', $this->get_icon_code(), $this->id );
	}

	/**
	 * Get <img> tag for gateway icon
	 *
	 * @since  1.2.0
	 * @param array $logo configuration
	 * @param string $logo_variant Logo variant to show (optional, uses configured variant by default)
	 * @return string HTML code for <img> tag
	 */
	protected function get_icon_img( $logo, $logo_variant = '' ) {
		$logo_variant = $logo_variant ?: $this->get_active_logo_variant();

		$filename = $logo['filename'];
		if (
			'default' !== $logo_variant
			&& array_key_exists( 'variants', $logo )
			&& array_key_exists( $logo_variant, $logo['variants'] )
		) {
			$filename = $logo['variants'][ $logo_variant ];
		}

		return sprintf(
			'<img src="%1$s" class="%3$s" alt="%2$s logo" title="%2$s" />',
			WC_HTTPS::force_https_url( plugins_url( '../assets/images/' . $filename, __FILE__ ) ),
			$logo['title'],
			$logo['style'] ? 'card-logo-' . $logo['style'] : ''
		);
	}

	/**
	 * Get gateway icon HTML code
	 *
	 * @param string $logo_variant Logo variant to show (optional, uses configured variant by default)
	 * @return string HTML code for the icons
	 */
	protected function get_icon_code( $logo_variant = '' ) {
		$logos = $this->get_logo_config( $this->method_variant );
		if ( 0 === count( $logos ) ) {
			return '';
		}
		$images = implode(
			'',
			array_map(
				function ( $logo ) use ( $logo_variant ) {
					return $this->get_icon_img( $logo, $logo_variant );
				},
				$logos
			)
		);

		return '<span class="card-logos">' . $images . '</span>';
	}

	/**
	 * Return the logo configuration for the gateway or method variant.
	 *
	 * @param string $variant Name of the method variant to get the logos for (set to null for main method)
	 * @return object The logo configuration
	 */
	protected function get_logo_config( $variant = '' ) {
		if ( '' === $variant ) {
			return $this->method_checkout_logos;
		} else {
			return $this->method_variants[ $variant ]['logos'];
		}
	}

	/**
	 * Return the configured logo variant for the gateway.
	 * @return string Logo variant name
	 */
	protected function get_active_logo_variant() {
		return $this->get_option( 'logo_variant' ) ?: 'default';
	}

	/**
	 * Return gateway settings form field configuration for general settings
	 *
	 * @return array    Form field configuration
	 */
	protected function get_form_fields_general() {
		return array_merge(
			array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable', 'wc-getloy-gateway' ),
					'label'       => sprintf(
						/* translators: %s gateway name (e.g. "PayWay") */
						__( 'Enable %s', 'wc-getloy-gateway' ),
						$this->method_admin_title
					),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
			),
			$this->method_variants && count( $this->method_variants ) > 0
				? array()
				: array(
					'title'       => array(
						'title'       => __( 'Title', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
						'default'     => $this->method_checkout_title,
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
						'default'     => $this->method_checkout_description,
						'desc_tip'    => true,
					),
				),
			array(
				'testmode'      => array(
					'title'       => __( 'Test mode', 'wc-getloy-gateway' ),
					'label'       => __( 'Enable Test Mode', 'wc-getloy-gateway' ),
					'type'        => 'checkbox',
					'description' => __( 'Place the payment gateway in test mode (no actual payments will be made) <strong>Note: PayWay removed support for PayWay 1.x in test mode</strong>.', 'wc-getloy-gateway' ),
					'default'     => 'yes',
					'desc_tip'    => false,
				),
				'paywayV2_mode' => array(
					'title'       => __( 'Use PayWay API V2.x', 'wc-getloy-gateway' ),
					'label'       => __( 'Enable PayWay API V2.x', 'wc-getloy-gateway' ),
					'type'        => 'checkbox',
					'description' => __( 'Switch to PayWay API version 2, called "re-integration" by ABA team.', 'wc-getloy-gateway' ),
					'default'     => 'no',
					'desc_tip'    => false,
				),
				'getloy_token'  => array(
					'title'       => __( 'GetLoy merchant token', 'wc-getloy-gateway' ),
					'type'        => 'text',
					'description' => __( 'You will receive this token from GetLoy after setting up your account.', 'wc-getloy-gateway' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'logo_variant'  => array(
					'title'       => __( 'Background color for payment method logos on checkout page', 'wc-getloy-gateway' ),
					'type'        => 'select',
					'options'     => array(
						'default' => __( 'Non-white background', 'wc-getloy-gateway' ),
						'dark'    => __( 'White background', 'wc-getloy-gateway' ),
					),
					'description' => __( 'This option changes the style of the payment method logos on the checkout page.', 'wc-getloy-gateway' ),
					'default'     => 'default',
					'desc_tip'    => false,
				),
			)
		);
	}

	/**
	 * Return gateway settings form field configuration for transaction ID settings
	 *
	 * @return array    Form field configuration
	 */
	protected function get_form_fields_transaction_id() {
		return array(
			'section_transaction_id' => array(
				'title'       => __( 'Transaction ID settings', 'woocommerce' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s gateway name (e.g. "PayWay") */
					__(
						'The transaction ID identifies the payment in %s. It is a combination of an optional prefix, the WooCommerce order ID and an optional prefix (example: prefix-123-suffix).',
						'wc-getloy-gateway'
					),
					$this->name
				),
			),
			'transaction_id_prefix'  => array(
				'title'       => __( 'Transaction ID prefix', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'This text will be prepended to the WooCommerce order number to create the transaction ID.', 'wc-getloy-gateway' ),
				'default'     => 'WC-',
			),
			'transaction_id_suffix'  => array(
				'title'       => __( 'Transaction ID suffix', 'wc-getloy-gateway' ),
				'type'        => 'text',
				'description' => __( 'This text will be appended to the WooCommerce order number to create the transaction ID.', 'wc-getloy-gateway' ),
				'default'     => '',
			),
		);
	}


	/**
	 * Initialize Gateway Settings Form Fields
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$form_fields = array_merge(
			$this->get_form_fields_general(),
			$this->filter_form_fields( $this->get_gateway_form_fields() ),
			$this->get_gateway_variant_form_fields(),
			$this->get_form_fields_transaction_id()
		);

		$this->form_fields = apply_filters( 'wc_' . $this->id_parent . '_form_fields', $form_fields );

	}

	/**
	 * Return filtered form field configuration for gateway's settings page in
	 * WooCommerce (duplicates of non-gateway specific settings are removed)
	 *
	 * @access protected
	 * @param  array $form_fields Settings form field configuration
	 * @return array Filtered settings form field configuration
	 */
	protected function filter_form_fields( $form_fields ) {
		$reserved_keys = array_merge(
			array_keys( $this->get_form_fields_general() ),
			array_keys( $this->get_form_fields_transaction_id() )
		);

		return array_filter(
			$form_fields,
			function ( $key ) use ( $reserved_keys ) {
				return ! in_array( $key, $reserved_keys );
			},
			ARRAY_FILTER_USE_KEY
		);
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
	abstract protected function get_gateway_form_fields();

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
		return array();
	}

	protected function get_gateway_variant_form_fields() {
		$form_field_template = $this->get_gateway_variant_form_field_template();

		if ( 0 === count( $form_field_template ) ) return array();

		$form_fields = array();
		foreach ( $this->method_variants as $variant => $variant_def ) {
			foreach ( $form_field_template as $field_name => $field_config ) {
				foreach ( array( 'title', 'label', 'description' ) as $field ) {
					if ( array_key_exists( $field, $field_config ) && false !== strpos( $field_config[ $field ], '%s' ) ) {
						$field_config[ $field ] = sprintf( $field_config[ $field ], $variant_def['name'] );
					}
				}
				if ( array_key_exists( 'default', $field_config ) && is_callable( $field_config['default'] ) ) {
					$field_config['default'] = $field_config['default']( $variant_def );
				}
				$form_fields[ $variant . '_' . $field_name ] = $field_config;
			}
		}
		return $form_fields;
	}

	/**
	 * Show a notice if errors were detected in the gateway configuration
	 *
	 * @access protected
	 * @return bool True if there was a config error
	 */
	protected function show_config_error_notice() {
		if ( ! $this->config_error ) {
			return false;
		}

		$this->add_notice(
			sprintf(
				/* translators: %s gateway name (e.g. "PayWay") */
				__(
					'The %s payment gateway is not configured properly. Please ask the site administrator to complete the configuration.',
					'wc-getloy-gateway'
				),
				$this->name
			),
			'error'
		);
		return true;
	}

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

		if ( $this->show_config_error_notice() ) {
			return false;
		}

		$order_id = absint( $order_id );
		$order    = wc_get_order( $order_id );
		if ( ! $order || $order->get_id() !== $order_id ) {
			$this->add_notice(
				__(
					'Sorry, this order is invalid and cannot be paid for.',
					'woocommerce'
				),
				'error'
			);
			return;
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);

	}

	/**
	 * Display the PayWay payment form on the payment page
	 *
	 * @access public
	 * @param  int $order_id
	 * @return array
	 */
	public function receipt_page( $order_id ) {

		if ( $this->show_config_error_notice() ) {
			return;
		}

		$order_id = absint( $order_id );
		$order    = wc_get_order( $order_id );
		if ( ! $order || $order->get_id() !== $order_id ) {
			$this->add_notice(
				__(
					'This order is invalid and cannot be paid for.',
					'woocommerce'
				),
				'error'
			);
			return;
		}
		$this->order = $order;

		$payment_status = get_post_meta( $order_id, '_wc_getloy_gateway_payment_status', true );
		$payment_method = get_post_meta( $order_id, '_wc_getloy_gateway_payment_method', true );

		if ( $payment_status && 'ongoing' != $payment_status ) {
			$this->add_notice(
				__(
					'Payment not possible - the payment may have timed out or failed. Please place a new order instead.',
					'wc-getloy-gateway'
				),
				'error'
			);
			error_log(
				sprintf(
					'%s (receipt_page): Payment not processed because of payment status "%s" (order status "%s").',
					$this->id_parent,
					$payment_status,
					$order->get_status()
				)
			);
			return;
		}

		$transaction_id = $this->generateTransactionId( $order->get_order_number() );
		update_post_meta( $order_id, '_wc_getloy_gateway_transaction_id', $transaction_id );
		update_post_meta( $order_id, '_wc_getloy_gateway_payment_status', 'ongoing' );
		update_post_meta( $order_id, '_wc_getloy_gateway_payment_method', $this->id_parent );

		$order->add_order_note(
			sprintf(
				/* translators: %1$s payment method name (e.g. "PayWay"), %2$s transaction id (e.g. "WC-1234") */
				__( 'Customer started payment via %1$s (transaction ID: %2$s)', 'wc-getloy-gateway' ),
				$this->get_name(),
				$transaction_id
			)
		);

		// todo: add call to abstract method here to allow setting gateway-specific order properties
		echo '<script type="text/javascript">jQuery(document).ready( function($) { $(\'body\').prepend(\'<div class="getloy"></div>\');});</script>';

	}

	/**
	 * Add JS code to start GetLoy
	 *
	 * @access public
	 * @param  int $order_id
	 * @return array
	 */
	public function receipt_page_footer() {

		if ( $this->config_error ) {
			return;
		}

		$order = $this->order;
		if ( ! $order ) {
			return;
		}

		$transaction_id = get_post_meta( $order->get_id(), '_wc_getloy_gateway_transaction_id', true );
		$callback_url   = get_rest_url(
			null,
			sprintf(
				'/wc-getloy-gateway/v1/payments/%s/%s/status',
				$this->id_parent,
				$transaction_id
			)
		);
		$order_details  = $this->get_order_details( $this->order );

		$connector_args = array_merge(
			array(
				'gateway_id'         => $this->id_parent,
				'gateway_variant_id' => $this->method_variant,
			),
			$this->get_connector_create_transaction_args()
		);

		$connector_transaction_errors = $this->getloy_connector->validateCreateTransactionParams(
			$transaction_id,
			$callback_url,
			$order_details,
			$connector_args
		);

		if ( $connector_transaction_errors ) {
			$errorlist_html = '<ul><li>' . implode( '</li><li>', $connector_transaction_errors ) . '</li></ul>';

			$this->add_notice(
				__(
					'Invalid payment request:',
					'wc-getloy-gateway'
				) . $errorlist_html,
				'error'
			);
			$errorlist_text = '  ' . implode( PHP_EOL . '  ', $connector_transaction_errors );
			error_log(
				sprintf(
					'%s (receipt_page): Payment parameters did not pass validation.' . PHP_EOL .
					'Order detais (json): %s' . PHP_EOL .
					'Connector arguments (json): %s' . PHP_EOL .
					'Validation error(s):' . PHP_EOL .
					'%s',
					$this->id,
					wp_json_encode( $order_details ),
					wp_json_encode( $connector_args ),
					$errorlist_text
				)
			);
			return;
		}

		$getloy_params = $this->getloy_connector->generateCreateTransactionParams(
			$transaction_id,
			$callback_url,
			$order_details,
			$connector_args
		);

		?>
<script>
!function(g,e,t,l,o,y){g.GetLoyPayments=t;g[t]||(g[t]=function(){
(g[t].q=g[t].q||[]).push(arguments)});g[t].l=+new Date;o=e.createElement(l);
y=e.getElementsByTagName(l)[0];o.src='https://some.getloy.com/getloy.js';
y.parentNode.insertBefore(o,y)}(window,document,'gl','script');
gl('payload', <?php echo wp_json_encode( $getloy_params ); ?>);
gl('success_callback', function(){window.location='<?php echo esc_url( $order->get_checkout_order_received_url() ); ?>';});
gl('cancel_callback', function(){window.top.location='<?php echo esc_url( wc_get_checkout_url() ); ?>';});
</script>
<?php
	}

	/**
	 * Return associative array of additional arguments to be passed to the gateway
	 * connector's generateCreateTransactionParams method
	 *
	 * @return array Associative array of additional arguments
	 */
	abstract protected function get_connector_create_transaction_args();

	/**
	 * Handle a callback from GetLoy
	 *
	 * @access public
	 * @return void
	 */
	public function status_update_callback( WP_REST_Request $request ) {
		if ( ! $this->config_error ) {

			if ( ! $request ) {
				error_log(
					sprintf(
						'%s (status_update_callback): No request passed to handler',
						$this->id
					)
				);

				return array(
					'status'  => 'error',
					'message' => 'no payload',
				);
			}

			$transaction_id = $request->get_param( 'tid' );
			$status         = $request->get_param( 'status' );
			$amount_paid    = floatval( $request->get_param( 'amount_paid' ) );
			$currency       = $request->get_param( 'currency' );
			$auth_hash      = $request->get_param( 'auth_hash_ext' );

			if ( ! $this->getloy_connector->validate_callback_hash( $transaction_id, $status, $amount_paid, $currency, $auth_hash ) ) {
				error_log(
					sprintf(
						'%s (status_update_callback): Received invalid callback: %s',
						$this->id,
						wp_json_encode( $request->get_params() )
					)
				);

				return array(
					'status'  => 'error',
					'message' => 'invalid request',
				);
			}

			$order_id       = $this->lookupOrderId( $transaction_id );
			$order          = wc_get_order( $order_id );
			$payment_status = get_post_meta( $order_id, '_wc_getloy_gateway_payment_status', true );
			$payment_method = get_post_meta( $order_id, '_wc_getloy_gateway_payment_method', true );

			if ( abs( $amount_paid - floatval( $order->get_total() ) ) >= 0.01 ) {
				error_log(
					sprintf(
						'%s (status_update_callback): Cannot process callback for order %d: Order total amount is "%.2f", but callback amount is "%.2f".',
						$this->id,
						$order_id,
						$order->get_total(),
						$amount_paid
					)
				);

				return array(
					'status'  => 'error',
					'message' => 'payment amount mismatch',
				);
			}

			if ( $currency !== $order->get_currency() ) {
				error_log(
					sprintf(
						'%s (status_update_callback): Cannot process callback for order %d: Order currency is "%s", but callback currency is "%s".',
						$this->id,
						$order_id,
						$order->get_currency(),
						$currency
					)
				);

				return array(
					'status'  => 'error',
					'message' => 'payment currency mismatch',
				);
			}

			if ( $payment_method !== $this->id_parent ) {
				error_log(
					sprintf(
						'%s (status_update_callback): Cannot process callback for order %d: Order payment method is "%s", but callback payment method is "%s".',
						$this->id,
						$order_id,
						$payment_method,
						$this->id
					)
				);

				return array(
					'status'  => 'error',
					'message' => 'payment method mismatch',
				);
			}

			if ( 'ongoing' !== $payment_status
				|| ( 'successful' !== $status && 'timed_out' !== $status )
			) {
				error_log(
					sprintf(
						'%s (status_update_callback): Cannot process callback for order %d: Status is "%s", but payment status is "%s".',
						$this->id,
						$order_id,
						$status,
						$payment_status
					)
				);

				return array(
					'status'  => 'error',
					'message' => 'invalid transaction status',
				);
			}

			if ( 'successful' === $status ) {

				update_post_meta( $order_id, '_wc_getloy_gateway_payment_status', 'complete' );
				$order->payment_complete();
				$order->add_order_note(
					sprintf(
						/* translators: %1$s currency code, %2$f order amount, %3$s payment method name (e.g. "PayWay") */
						__( 'Payment of %1$s %2$.2f received via %3$s', 'wc-getloy-gateway' ),
						$currency,
						$amount_paid,
						$this->get_name()
					)
				);

			} elseif ( 'timed_out' === $status ) {

				update_post_meta( $order_id, '_wc_getloy_gateway_payment_status', 'timeout' );
				$order->update_status( 'failed', __( 'Payment timed out', 'wc-getloy-gateway' ) );

			}

			return array(
				'status'  => 'success',
				'message' => 'transaction updated',
			);
		}
	}

	/**
	 * Look up the order ID for the order with the provided transaction ID.
	 *
	 * @access public
	 * @param  string $transaction_id The GetLoy transaction ID.
	 * @return int The WooCommerce order ID
	 */
	protected function lookupOrderId( $transaction_id ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_getloy_gateway_transaction_id' and meta_value = %s",
				$transaction_id
			)
		);

		return $order_id;
	}

	/**
	 * Generate transaction ID to be sent to GetLoy
	 *
	 * @access public
	 * @param  int $order_id The WooCommerce order ID.
	 * @return string The GetLoy transaction ID
	 */
	protected function generateTransactionId( $order_id ) {

		$transaction_id = ( $this->transaction_id_prefix ?: '' )
			. $order_id
			. ( $this->transaction_id_suffix ?: '' );

		return $transaction_id;

	}

	/**
	 * Generate list of order items
	 *
	 * @access public
	 * @return array    Associative array with order details
	 */
	public function get_order_items( WC_Order $order ) {

		$wc_items = $order->get_items();
		$gl_items = array();

		// TODO: get additional details for WooCommerce Bookings items
		foreach ( $wc_items as $wc_item ) {
			$price_total    = round( ( $wc_item['line_subtotal'] + $wc_item['line_subtotal_tax'] ), 2 );
			$price_per_unit = round( $price_total / $wc_item['qty'], 2 );

			$gl_items[] = array(
				'description' => $wc_item['name'],
				'quantity'    => (int) $wc_item['quantity'],
				'total_price' => $price_total,
				'unit_price'  => $price_per_unit,
			);
		}

		return $gl_items;
	}


	/**
	 * Collect information about the order
	 *
	 * @access public
	 * @return array    Associative array with order details
	 */
	public function get_order_details( WC_Order $order ) {

		$location = null;
		if ( class_exists( 'WC_Geolocation' ) ) {
			$location = WC_Geolocation::geolocate_ip( $order->get_customer_ip_address() );
		}

		$order_details = array(
			'amount_total'     => $order->get_total(),
			'currency'         => $order->get_currency(),
			'order_timestamp'  => $order->get_date_created()->date( DateTime::ATOM ),
			'order_ip'         => $order->get_customer_ip_address(),
			'order_country'    => $location ? $location['country'] : '',
			'billing_details'  => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'company'    => $order->get_billing_company(),
				'address'    => $order->get_billing_address_1() . ( $order->get_billing_address_2() ? "\n" . $order->get_billing_address_2() : '' ),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			),
			'shipping_details' => array(
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'company'    => $order->get_shipping_company(),
				'address'    => $order->get_shipping_address_1() . ( $order->get_shipping_address_2() ? "\n" . $order->get_shipping_address_2() : '' ),
				'city'       => $order->get_shipping_city(),
				'state'      => $order->get_shipping_state(),
				'postcode'   => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			),
			'order_items'      => $this->get_order_items( $order ),
		);

		return $order_details;
	}

	/**
	 * Check if the environment is suitable for the payment method and that it is configured correctly.
	 * Optionally display warnings for any issues found.
	 *
	 * @access public
	 * @param  bool $output_error Output HTML code for displaying error messages in the WordPress admin interface? (default false)
	 * @return bool True if the setup is valid
	 */
	public function check_setup( $output_error = true ) {
		if ( 'no' === $this->enabled ) {
			return true;
		}

		$gateway_config_link = $this->get_gateway_config_link();
		$config_errors       = array();

		if ( ! $this->getloy_token ) {
			$config_errors[] = sprintf(
				/* translators: %s "click here to sign up" link */
				__(
					'GetLoy token is missing. If you did not create a GetLoy account yet, please %s.',
					'wc-getloy-gateway'
				),
				sprintf(
					'<a href="https://getloy.com/signup" target="_blank">%s</a>',
					__( 'click here to sign up', 'wc-getloy-gateway' )
				)
			);
		}

		// Check gateway configuration.
		$config_errors = array_merge( $config_errors, $this->check_config() );

		if ( count( $config_errors ) ) {
			if ( $output_error ) {
				echo '<div class="notice notice-error wc-getloy-gateway"><p><strong>' .
					sprintf(
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
						/* translators: %1$s gateway name (e.g. "PayWay"), %2$s "click here to fix" link */
						__(
							'%1$s gateway configuration error(s) (%2$s):',
							'wc-getloy-gateway'
						),
						esc_html( $this->name ),
						sprintf(
							'<a href="%s">%s</a>',
							esc_url( $gateway_config_link ),
							esc_html( __( 'click here to fix', 'wc-getloy-gateway' ) )
						)
					) . '</strong></p><ul class="getloy-error-list">';
				foreach ( $config_errors as $config_error ) {
					printf( '<li>%s</li>', $config_error );
				}
				echo '</ul></div>';
			}
			return false;
		}

		// warn about test payments
		if ( 'yes' === $this->testmode ) {
			if ( $output_error ) {
				echo '<div class="notice notice-info wc-getloy-gateway"><p>' .
					sprintf(
						/* translators: %s gateway name (e.g. "PayWay") */
						__(
							'%s payment gateway is in test mode, no real payments are processed!',
							'wc-getloy-gateway'
						),
						esc_html( $this->name )
					) . '</p></div>';
			}
		}

		return true;
	}

	/**
	 * Check if the gateway configuration is complete and correct.
	 *
	 * @access public
	 * @return array List of error messages for all issues identified during the check.
	 */
	abstract public function check_config();

	/**
	 * Filter list of gateways available for checkout - hide current gateway if
	 * the configuration is incomplete, and show a warning
	 *
	 * @since  1.0.0
	 * @param  array $available_gateways List of gateways to show as options during checkout
	 * @return array                    List of gateways
	 * @access public
	 */
	public function filter_gateways( array $available_gateways ) {

		if ( ! isset( $available_gateways[ $this->id ] ) ) {
			return $available_gateways;
		}
		if ( $this->check_setup( false ) ) {
			if ( 0 === count( $this->method_variants ) ) {
				return $available_gateways;
			}

			$gateway_variants = array();
			$gateway_class    = get_class( $this );

			foreach ( array_keys( $this->method_variants ) as $variant_name ) {
				$variant_gateway = new $gateway_class( $variant_name );

				if ( 'yes' === $variant_gateway->enabled ) {
					$gateway_variants[ $variant_gateway->id ] = $variant_gateway;
				}
			}
			unset( $available_gateways[ $this->id ] );
			return array_merge( $available_gateways, $gateway_variants );
		}

		unset( $available_gateways[ $this->id ] );
		// Todo: fix the following block so it is no longer breaking the ajax call /?wc-ajax=update_order_review ( is_ajax() doesn't work)
		// if ( is_cart() || is_checkout() ) {
		// $this->add_notice( sprintf( __(
		// 'The %s payment gateway is not configured properly. Please ask the site administrator to complete the configuration.',
		// 'wc-getloy-gateway'
		// ),
		// $this->name
		// ),
		// 'error'
		// );
		// }
		return $available_gateways;

	}

	/**
	 * Add a notice to be displayed to the user. Skip duplicate notices.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string $message Notice message
	 * @param  string $type    Notice type
	 * @return void
	 */
	protected function add_notice( $message, $type ) {

		foreach ( wc_get_notices( $type ) as $notice ) {
			if ( $notice === $message ) {
				return;
			}
		}
		wc_add_notice( $message, $type );

	}

	/**
	 * Get a fully qualified URL of the gateway configuration tab on the WooCommerce checkout settings page.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string  Gateway settings link
	 */
	public function get_gateway_config_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id_parent );
	}

	/**
	 * Get a list of payment method variants supported by the gateway.
	 *
	 * @since  1.0.7
	 * @access public
	 * @return string[]  Gateway method variant names
	 */
	public function get_method_variants() {
		return array_keys( $this->method_variants );
	}

	/**
	 * Get a list of activated payment method variants for the gateway.
	 *
	 * @since  1.2.0
	 * @access public
	 * @return string[]  Gateway method variant names
	 */
	public function get_active_method_variants() {
		return array_filter(
			array_keys( $this->method_variants ),
			function ( $variant ) {
				return 'yes' === $this->get_option(
					sprintf( '%s_enabled', $variant )
				);
			}
		);
	}

	/**
	 * Get a list of <img> tags for payment option logos of the active payment options.
	 *
	 * @since  1.2.0
	 * @access public
	 * @param string $logo_variant Logo variant to show (optional, uses configured variant by default)
	 * @return string[]  List of payment option logo <img> tags
	 */
	public function get_active_payment_option_logo_imgs( $logo_variant = '' ) {
		$active_variants = $this->get_method_variants();
		if ( 0 === count( $this->get_method_variants() ) ) {
			$logos = $this->get_logo_config();
		} else {
			$active_variants = $this->get_active_method_variants();
			$logos           = array();
			foreach ( $active_variants as $variant ) {
				$logos = array_merge(
					$logos,
					$this->get_logo_config( $variant )
				);
			}
		}
		return array_map(
			function ( $logo ) use ( $logo_variant ) {
				return $this->get_icon_img( $logo, $logo_variant );
			},
			$logos
		);
	}
}
