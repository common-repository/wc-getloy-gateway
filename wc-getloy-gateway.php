<?php

/**
 * GetLoy payment gateway for WooCommerce (supports iPay88, PayWay and Pi Pay)
 *
 * @link    https://geekho.asia/
 * @since   1.0.0
 * @package Wc_Getloy_Gateway
 *
 * @wordpress-plugin
 * Description: Payment gateway for WooCommerce to accept online payments in Cambodia. Supports iPay88, PayWay by ABA Bank and Pi Pay.
 * Plugin Name:          GetLoy payment gateway for WooCommerce (supports iPay88, PayWay and Pi Pay)
 * Plugin URI:           https://getloy.com/wc-getloy/
 * Description:          Payment gateway for WooCommerce to accept online payments in Cambodia. Supports iPay88, PayWay by ABA Bank and Pi Pay.
 * Version:              1.3.0
 * Author:               Geekho (Cambodia)
 * Author URI:           https://geekho.asia/
 * License:              GPL-2.0+
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:          wc-getloy-gateway
 * Domain Path:          /languages
 * WC requires at least: 3.2.0
 * WC tested up to:      6.2.0-rc.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


require plugin_dir_path( __FILE__ ) . 'widgets/class-getloy-widget.php';
require plugin_dir_path( __FILE__ ) . 'widgets/class-getloy-widget-payway-footer-box.php';

add_action(
	'widgets_init',
	function() {
		register_widget( 'Getloy_Widget_Payway_Footer_Box' );
	}
);

/**
 * The core plugin class that is used to define internationalization,
 * and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-getloy-gateway.php';

/**
 * Begin execution of the plugin.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'run_wc_getloy_gateway' ) ) {
	function run_wc_getloy_gateway() {

		$plugin = Wc_Getloy_Gateway::instance();
		$plugin->run();

	}
}
run_wc_getloy_gateway();
