<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

final class Tumeny_WC_Gateway_Blocks_Support extends AbstractPaymentMethodType {


	protected $name = 'tumeny';
    private $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_tumeny_settings', array() );
        $this->gateway = new Tumeny_WC_Gateway();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {

        wp_register_script(
            'wc-tumeny-blocks',
            plugin_dir_url(__FILE__) . '../assets/block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n'
            ],
            null,
            true
        );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-tumeny-blocks', 'wc-tumeny', null);
		}

		return array( 'wc-tumeny-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {

		return array(
			'title'             => $this->gateway->title,
			'description'       => $this->gateway->description,
			'logo_urls'         => array( $this->gateway->get_logo_url() ),
		);
	}
}
