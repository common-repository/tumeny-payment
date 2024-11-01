<?php

class Tumeny_WC_Gateway extends WC_Payment_Gateway {

    private $apiRequest;
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->base_url = $this->get_option( 'base_url' );
		$this->api_key = $this->get_option( 'api_key' );
		$this->api_secret = $this->get_option( 'api_secret' );

        $this->apiRequest = new Tumeny_WC_Gateway_Api_Request($this->base_url, $this->api_key, $this->api_secret);

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_'. $this->id, array( $this, 'tumeny_wc_gateway_payment_callback') );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'tumeny';
		$this->icon               = apply_filters( 'woocommerce_gateway_icon', plugins_url('../assets/images/moc-zambia.png', __FILE__ ) );
		$this->method_title       = __( 'Mobile Money Payment by TuMeNy', 'wc-tumeny' );
		$this->method_description = __( 'Accepting Mobile Payment in Zambia.', 'wc-tumeny' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'wc-tumeny' ),
				'label'       => __( 'Enable Tumeny Mobile Money Payment', 'wc-tumeny' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'wc-tumeny' ),
				'type'        => 'text',
				'description' => __( 'Tumeny Mobile Money Payment method description that the customer will see on your checkout.', 'wc-tumeny' ),
				'default'     => __( 'Mobile Money Payment', 'wc-tumeny' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Description', 'wc-tumeny' ),
				'type'        => 'textarea',
				'description' => __( 'Tumeny Mobile Money Payment method description that the customer will see on your website.', 'wc-tumeny' ),
				'default'     => __( 'Mobile Money Payment in Zambia - supports MTN, Airtel and Zamtel.', 'wc-tumeny' ),
				'desc_tip'    => true,
			),
            'base_url'         => array(
                'title'       => __( 'Base Url', 'wc-tumeny' ),
                'type'        => 'text',
                'description' => __( 'Tumeny Base Url', 'wc-tumeny' ),
                'desc_tip'    => true,
            ),
            'api_key'         => array(
                'title'       => __( 'API Key', 'wc-tumeny' ),
                'type'        => 'text',
                'description' => __( 'Tumeny API Key', 'wc-tumeny' ),
                'desc_tip'    => true,
            ),
            'api_secret'         => array(
                'title'       => __( 'API Secret', 'wc-tumeny' ),
                'type'        => 'text',
                'description' => __( 'Tumeny API Secret', 'wc-tumeny' ),
                'desc_tip'    => true,
            ),
		);
	}

    public function get_icon() {
        $icon_url  = plugin_dir_url( __FILE__ ) . '../assets/images/moc-zambia.png';
        $icon_html = sprintf('<img src="%s" alt="%s" />', $icon_url, $this->method_title );

        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }

    public function get_logo_url() {
        $url  = plugins_url( '../assets/images/moc-zambia.png', __FILE__ );
        return apply_filters( 'wc_tumeny_gateway_icon_url', $url, $this->id );
    }

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
            $callback_url = get_home_url().'/wc-api/tumeny?order_id='.$order_id;
            $paymentId = $this->apiRequest->tumeny_wc_gateway_create_payment($order, $callback_url);
            $url = $this->base_url.'/pay/'.$paymentId.'/new/payment';
		} else {
			$order->payment_complete();
            WC()->cart->empty_cart();
            $url = $this->get_return_url( $order );
		}

		return array(
			'result'   => 'success',
			'redirect' => $url,
		);
	}

    public function tumeny_wc_gateway_payment_callback() {

        if (!isset($_GET['order_id'], $_GET['paymentId'])) {
            wc_add_notice( 'Oops! Something went wrong, please try again ', 'error' );
            wp_redirect( WC()->cart->get_checkout_url() );
            exit();
        }
        $order_id = sanitize_text_field($_GET['order_id']);
        $payment_id = sanitize_text_field($_GET['paymentId']);

        $status = $this->apiRequest->tumeny_wc_gateway_get_payment_status($payment_id);

        if (Tumeny_WC_Gateway_Payment_Status::SUCCESS === $status) {
            $order = wc_get_order( $order_id );
            $order->payment_complete();
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();

            wc_add_notice( 'Payment Successfully Completed' , 'success' );

            wp_redirect( $this->get_return_url( $order ) );
            exit();
        }
        else {
            wc_add_notice( 'Oops! Your Payment Did not complete successfully - Please try again' , 'error' );
            wp_redirect( WC()->cart->get_checkout_url() );
            exit();
        }
    }
}