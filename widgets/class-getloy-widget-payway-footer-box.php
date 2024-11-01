<?php

class Getloy_Widget_Payway_Footer_Box extends Getloy_Widget {

	/**
	 * Root ID for all widgets of this type.
	 *
	 * @var mixed|string
	 */
	public $id_base = 'getloy_payway_footer_box';

	/**
	 * PayWay payment methods (set in WooCommerce payment gateway settings)
	 * @var string
	 */
	private $payment_methods = null;

	public function __construct( $id_base = null, $name = null, $widget_options = array(), $control_options = array() ) {
		$this->name        = $name ?: __( 'PayWay accepted payment methods', 'wc-getloy-gateway' );
		$this->glw_options = array(
			'title'        => array(
				'title'   => __( 'Footer box title', 'wc-getloy-gateway' ),
				'type'    => 'text',
				'default' => 'We accept:',
			),
			'logo_variant' => array(
				'title'   => __( 'Footer background color', 'wc-getloy-gateway' ),
				'type'    => 'select',
				'default' => 'default',
				'options' => array(
					'default' => __( 'White background', 'wc-getloy-gateway' ),
					'dark'    => __( 'Non-white background', 'wc-getloy-gateway' ),
				),
			),
		);

		parent::__construct( $id_base, $this->name, $widget_options, $control_options );

		if ( ! is_admin() && is_active_widget( false, false, $this->id_base ) ) {
			add_action(
				'wp_enqueue_scripts',
				function() {
					wp_enqueue_style(
						'wc-getloy-gateway-frontend-widgets',
						plugins_url( '../assets/css/wc-getloy-gateway-frontend-widgets.css', __FILE__ ),
						array(),
						'1.0.0'
					);
				}
			);
		}
	}

	/**
	 * Output the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$payway_gateway = new Wc_Getloy_Gateway_Payway_Kh();
		$logo_variant   = array_key_exists( 'logo_variant', $instance ) && $instance['logo_variant']
			? $instance['logo_variant']
			: 'default';

		$active_payment_option_imgs = $payway_gateway->get_active_payment_option_logo_imgs(
			$logo_variant
		);

		if ( 0 === count( $active_payment_option_imgs ) ) return;

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'];

		echo $args['before_title']
			. $instance['title']
			. $args['after_title'];

		echo '<div class="getloy-widget-payment-options">'
			. implode( '', $active_payment_option_imgs )
			. '</div>';

		echo $args['after_widget'];
	}
}
