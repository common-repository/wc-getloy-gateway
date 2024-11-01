<?php

/**
 * Definition of the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site.
 *
 * @link  https://geekho.asia
 * @since 1.0.0
 *
 * @package    Wc_Getloy_Gateway
 * @subpackage Wc_Getloy_Gateway/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wc_Getloy_Gateway
 * @subpackage Wc_Getloy_Gateway/includes
 * @author     Geekho (Cambodia) <payment@geekho.asia>
 */
class Wc_Getloy_Gateway {


	/**
	 * Single instance of the plugin class (to implement the singleton pattern).
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Wc_Getloy_Gateway $instance Single instance of the plugin class
	 */
	protected static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Wc_Getloy_Gateway_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * List of included payment gateways
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string[]    $gateways    List of payment gateway IDs.
	 */
	protected $gateways = array(
		'payway_kh' => 'PayWay',
		'pipay_kh'  => 'Pipay',
		'ipay88_kh' => 'iPay88',
	);

	/**
	 * Instances of the payment gateway classes.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Wc_Getloy_Gateway[]    $gateway_instances    The instances of the gateways.
	 */
	protected $gateway_instances;

	/**
	 * Return single instance of this class
	 *
	 * @since  1.0.0
	 * @access private
	 * @return Wc_Getloy_Gateway $instance Single instance of the plugin class
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;

	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function __construct() {

		$this->get_plugin_name();
		$this->version = '1.2.1';

		$this->load_dependencies();
		$this->set_locale();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-getloy-gateway-loader.php';

		$this->loader = new Wc_Getloy_Gateway_Loader();

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-getloy-gateway-i18n.php';

		$this->loader->add_action( 'plugins_loaded', $this, 'init_gateway' );

		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts_admin' );

		$this->loader->add_filter( 'woocommerce_payment_gateways', $this, 'add_gateway' );

		$this->loader->add_filter( 'woocommerce_available_payment_gateways', $this, 'filter_gateways' );

		$this->loader->add_filter( 'woocommerce_order_button_text', $this, 'get_wc_order_button_text' );

		$this->loader->add_action( 'woocommerce_after_checkout_form', $this, 'output_wc_checkout_script' );

		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );

		if ( is_admin() ) {
			$this->loader->add_filter( 'plugin_action_links_' . $this->plugin_name . '/wc-getloy-gateway.php', $this, 'plugin_add_action_link' );

			$this->loader->add_action( 'admin_notices', $this, 'gateway_check_setup' );
		}

	}

	/**
	 * Load the class responsible for defining all actions of the WooCommerce payment
	 * gateway backend.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function init_gateway() {

		$plugin_dir = plugin_dir_path( dirname( __FILE__ ) );

		// Load the abstract classes all gateways inherit from
		include_once $plugin_dir . 'gateways/class-wc-getloy-payment-gateway.php';
		include_once $plugin_dir . 'gateways/class-wc-getloy-payment-gateway-connector.php';

		foreach ( array_keys( $this->gateways ) as $gateway ) {
			include_once $plugin_dir . $this->get_gateway_filename( $gateway );

			$classname               = $this->get_gateway_classname( $gateway );
			$gateway_instance        = new $classname();
			$payment_method_variants = $gateway_instance->get_method_variants();

			$this->gateway_instances[ $gateway ] = new $classname();

			$gateways = array();
			if ( 0 === count( $payment_method_variants ) ) {
				$gateways[] = $gateway;
			} else {
				foreach ( $payment_method_variants as $variant ) {
					$gateway_instance_name = $gateway . '_' . $variant;
					$gateways[]            = $gateway_instance_name;

					$this->gateway_instances[ $gateway_instance_name ] = new $classname( $variant );
				}
			}

			foreach ( $gateways as $gw_name ) {
				add_action(
					'woocommerce_receipt_' . $gw_name,
					array( $this, 'gateway_receipt_page_' . $gw_name )
				);
			}

			if ( is_admin() ) {
				add_action(
					'woocommerce_update_options_payment_gateways_' . $gateway,
					array( $this, 'gateway_process_admin_options_' . $gateway )
				);
			}
		}
	}

	/**
	 * Load scripts and styles for plugin (frontend)
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function enqueue_scripts() {
		if ( is_cart() || is_checkout() ) {
			wp_enqueue_style(
				'wc-getloy-gateway-frontend-cart',
				plugins_url( '../assets/css/wc-getloy-gateway-frontend-cart.css', __FILE__ ),
				array(),
				'1.0.0'
			);
		}
	}

	/**
	 * Load scripts and styles for plugin (admin section)
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function enqueue_scripts_admin() {
		wp_enqueue_style(
			'wc-getloy-gateway-admin',
			plugins_url( '../assets/css/wc-getloy-gateway-admin.css', __FILE__ ),
			array(),
			'1.0.0'
		);
	}

	/**
	 * Method overloading to catch calls to gateway-specific methods from actions.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function __call( $method, $args ) {
		if ( preg_match(
			'/^gateway_receipt_page_(.+)$/',
			$method,
			$matches
		)
		) {
			return $this->gateway_receipt_page( $matches[1], $args[0] );
		} elseif ( preg_match(
			'/^gateway_process_admin_options_(.+)$/',
			$method,
			$matches
		)
		) {
			return $this->gateway_process_admin_options( $matches[1] );
		}
	}

	/**
	 * Generate the filename and relative path of the class definition for the specified gateway.
	 * The path is relative to the plugin's main directory.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function get_gateway_filename( $gateway_id ) {
		return 'gateways/' . $gateway_id . '/class-wc-getloy-gateway-' .
		strtolower( str_replace( '_', '-', $gateway_id ) ) . '.php';
	}

	/**
	 * Generate the class name of the gateway's main class.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function get_gateway_classname( $gateway_id ) {
		return 'Wc_Getloy_Gateway_' . ucwords( str_replace( '-', '_', $gateway_id ), '_' );
	}

	/**
	 * Generate the filename and relative path of the class definition for gateway's connector.
	 * The path is relative to the gateways main directory (./gateways/some_gateway/)
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function get_gateway_connector_filename( $gateway_id ) {
		return 'includes/class-wc-getloy-gateway-' .
		strtolower( str_replace( '_', '-', $gateway_id ) ) . '-connector.php';
	}

	/**
	 * Generate the class name of the gateway's connector class.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function get_gateway_connector_classname( $gateway_id ) {
		return $this->get_gateway_classname( $gateway_id ) . '_Connector';
	}

	/**
	 * Return the text to display on the order button of WooCommerce's checkout form
	 */
	public function get_wc_order_button_text() {
		return __( 'Confirm & Pay', 'wc-getloy-gateway' );
	}

	/**
	 * Return a JS code snippet for the checkout page to prevent the checkout page from showing the
	 * loading/blocked animation forever when navigating back to checkout from the payment page after
	 * the user cancelled the payment (after default cancel_callback was executed).
	 */
	public function output_wc_checkout_script() {
		?>
<script>
jQuery(document).ready( function($) {
	$(window).bind("pageshow", function(event) {
		if (event.originalEvent.persisted) {
			$('form[name=checkout]').removeClass('processing').unblock();
		}
	});
});
</script>
	<?php
	}

	/**
	 * Register the payment gateway with WooCommerce
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function add_gateway( $gateways ) {

		foreach ( array_keys( $this->gateways ) as $gateway ) {
			$gateways[] = $this->get_gateway_classname( $gateway );
		}

		return $gateways;
	}

	/**
	 * Process admin options for gateway
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function gateway_process_admin_options( $gateway ) {
		$this->gateway_instances[ $gateway ]->process_admin_options();

	}

	/**
	 * Receipt page hook for gateway
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function gateway_receipt_page( $gateway, $order_id ) {

		$this->gateway_instances[ $gateway ]->receipt_page( $order_id );
		add_action( 'wp_print_footer_scripts', array( $this->gateway_instances[ $gateway ], 'receipt_page_footer' ) );
	}

	/**
	 * Configuration check hook for all gateways
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function gateway_check_setup() {

		// Check PHP Version.
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			echo '<div class="notice notice-error wc-getloy-gateway"><p>' .
				esc_html(
					sprintf(
						/* translators: %1$s gateway name (e.g. "PayWay"), %2$s PHP version (e.g. "5.2") */
						__(
							'%1$s gateway error: This plugin requires PHP 5.3 and above. You are using version %2$s.',
							'wc-getloy-gateway'
						),
						$this->name(),
						phpversion()
					)
				) .
			'</p></div>';
			return false;
		}

		$has_active_gateway = false;
		foreach ( array_keys( $this->gateways ) as $gateway ) {
			$check = $this->gateway_instances[ $gateway ]->check_setup();
			if ( $check && 'yes' === $this->gateway_instances[ $gateway ]->enabled ) {
				$has_active_gateway = true;
			}
		}

		// warn about non-SSL page
		if ( $has_active_gateway
			&& ! wc_checkout_is_https()
		) {
			global $woocommerce;
			echo '<div class="notice notice-warning wc-getloy-gateway"><p>' .
			sprintf(
				/* translators: %s URL to WooCommerce settings page */
				__( // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					'You are not enforcing HTTPS for checkout. ' .
					'While the GetLoy payment gateways remains secure, users may feel insecure due to the missing confirmation in the browser address bar. ' .
					'Please <a href="%s">enforce SSL</a> and ensure your server has a valid SSL certificate!',
					'wc-getloy-gateway'
				),
				esc_url(
					version_compare( $woocommerce->version, '3.4', '<' ) ?
						admin_url( 'admin.php?page=wc-settings&tab=checkout' ) :
						admin_url( 'admin.php?page=wc-settings&tab=advanced' )
				)
			) . '</p></div>';
		}
	}

	/**
	 * Filter list of gateways available for checkout
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function filter_gateways( array $available_gateways ) {

		$remaining_gateways = $available_gateways;

		foreach ( array_keys( $this->gateways ) as $gateway ) {
			$remaining_gateways = $this->gateway_instances[ $gateway ]->filter_gateways( $remaining_gateways );
		}
		return $remaining_gateways;
	}

	/**
	 * Register API endpoint for GetLoy callback
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function register_rest_routes() {
		register_rest_route(
			'wc-getloy-gateway/v1',
			'/payments/(?P<gateway>[^/?&\s]+)/(?P<tid>[^/?&\s]+)/status',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'handle_status_update_callback' ),
			)
		);
	}

	/**
	 * Payment status update callback hook for gateway
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request The request sent to the REST endpoint
	 *
	 * @return WP_REST_Response The response
	 */
	public function handle_status_update_callback( WP_REST_Request $request ) {

		$gateway = $request->get_param( 'gateway' );

		if ( ! isset( $this->gateway_instances[ $gateway ] ) ) {
			error_log(
				sprintf(
					'(status_update_callback): Received callback for unsupported gateway %s: %s',
					$gateway,
					wp_json_encode( $request->get_params() )
				)
			);

			return array(
				'status'  => 'error',
				'message' => 'invalid gateway',
			);
		}

		return $this->gateway_instances[ $gateway ]->status_update_callback( $request );

	}

	/**
	 * Plugin action link (link to configuration page)
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function plugin_add_action_link( $links ) {

		$plugin_links = array();
		foreach ( $this->gateways as $gateway => $name ) {
			$plugin_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway ) . '">' . sprintf(
				/* translators: %s gateway name (e.g. "PayWay") */
				__( '%s settings', 'wc-getloy-gateway' ),
				$name
			) . '</a>';
		}
		return array_merge( $plugin_links, $links );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wc_Getloy_Gateway_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new Wc_Getloy_Gateway_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		if ( ! isset( $this->plugin_name ) ) {
			// extract the name of the second parent folder from the current script's path
			if ( preg_match( '/^.+[\\\\\/]([^\\\\\/]+)(?:[\\\\\/][^\\\\\/]+)[\\\\\/]?$/', dirname( __FILE__ ), $match ) ) {
				$this->plugin_name = $match[1];
			} else {
				// malformed path
				$this->plugin_name = 'wc-getloy-gateway';
			}
		}

		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Wc_Getloy_Gateway_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
