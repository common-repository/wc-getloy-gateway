<?php

abstract class Getloy_Widget extends WP_Widget {

	/**
	 * Configuration for widget options.
	 * Option configuration is done using the same format as the WooCommerce Settings API.
	 * @see https://docs.woocommerce.com/document/settings-api/
	 *
	 * @var string
	 */
	public $glw_options = array();

	/**
	 * Render the admin form for the provided instance
	 *
	 * @param array $instance The widget options
	 * @return string The HTML code for the admin form
	 */
	public function render_form( $instance ) {
		$output = '';
		foreach ( $this->glw_options as $option => $config ) {
			switch ( $config['type'] ) {
				case 'select':
					$output .= sprintf(
						'<p><label for="%1$s">%3$s</label>' .
						'<select class="widefat" id="%1$s" name="%2$s">',
						$this->get_field_id( $option ),
						$this->get_field_name( $option ),
						esc_html( $config['title'] )
					);
					foreach ( $config['options'] as $opt_key => $opt_name ) {
						$output .= sprintf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $opt_key ),
							selected( $opt_key, ! empty( $instance[ $option ] ) ? $instance[ $option ] : '', false ),
							esc_html( $opt_name )
						);
					}
					$output .= '</select></p>';
					break;

				case 'text':
					$output .= sprintf(
						'<p><label for="%1$s">%3$s</label>' .
						'<input class="widefat" id="%1$s" name="%2$s" type="text" value="%4$s" /></p>',
						$this->get_field_id( $option ),
						$this->get_field_name( $option ),
						esc_html( $config['title'] ),
						esc_attr( isset( $instance[ $option ] ) ? $instance[ $option ] : $config['default'] )
					);
					break;

				default:
					error_log(
						sprintf( '%s: unsupported form field type %s used for option %s', __CLASS__, $config['type'], $option )
					);
					break;
			}
		}
		return $output;
	}

	/**
	 * Output the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_form( $instance );
	}

	/**
	 * Process widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		foreach ( $this->glw_options as $option => $config ) {
			switch ( $config['type'] ) {
				case 'select':
					if ( isset( $new_instance[ $option ] ) && isset( $config['options'][ $new_instance[ $option ] ] ) ) {
						$instance[ $option ] = $new_instance[ $option ];
					} else {
						$instance[ $option ] = isset( $old_instance[ $option ] ) ? $old_instance[ $option ] : $config['default'];
					}
					break;

				case 'text':
					$instance[ $option ] = isset( $new_instance[ $option ] )
						? $new_instance[ $option ]
						: (
							isset( $old_instance[ $option ] )
								? $old_instance[ $option ]
								: $config['default']
						);
					break;

				default:
					error_log(
						sprintf( '%s: unsupported form field type %s used for option %s', __CLASS__, $config['type'], $option )
					);
					break;
			}
		}
		return $instance;
	}
}
