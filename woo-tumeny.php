<?php
/**
 * Plugin Name: TuMeNy Mobile Payment
 * Plugin URI: https://tumenypay.com
 * Author: Nsisong E.O
 * Description: Mobile Money Payment Gateway in Zambia.
 * Version: 0.1.0
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: wc-tumeny
 *
 * @package WooCommerce\Tumeny
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'tumeny_wc_gateway_payment_init', 66 );
add_filter( 'woocommerce_payment_gateways', 'tumeny_wc_gateway_add_payment_gateway');

function tumeny_wc_gateway_payment_init() {

    if(!class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'tumeny_wc_gateway_woo_missing_notice' );
        return;
	}

    require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-gateway-tumeny.php';
    require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-gateway-tumeny-api-request.php';
    require_once plugin_dir_path( __FILE__ ) . '/includes/constants/class-wc-gateway-tumeny-payment-status.php';
}

function tumeny_wc_gateway_add_payment_gateway( $gateways ) {
    if ( 'ZK' === get_woocommerce_currency() || 'ZMW' === get_woocommerce_currency() ) {
        $gateways[] = 'Tumeny_WC_Gateway';
    }
    return $gateways;
}

/**
 * Display a notice if WooCommerce is not installed
 */
function tumeny_wc_gateway_woo_missing_notice() {
    echo '<div class="error"><p><strong>' . esc_html(__( 'Tumeny Payment requires WooCommerce to be installed and active. Please install and activate WooCommerce.', 'wc-tumeny' )). '</strong></p></div>';
}

/**
 * Registers WooCommerce Blocks integration.
 */
function tumeny_wc_gateway_block_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        require_once __DIR__ . '/includes/class-wc-gateway-tumeny-blocks-support.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            static function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new Tumeny_WC_Gateway_Blocks_Support() );
            }
        );
    }
}
add_action( 'woocommerce_blocks_loaded', 'tumeny_wc_gateway_block_support' );