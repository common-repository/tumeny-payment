<?php

class Tumeny_WC_Gateway_Api_Request {

    private $base_url;
    private $api_key;
    private $api_secret;

    /**
     * @param $base_url
     * @param $api_key
     * @param $api_secret
     */
    public function __construct($base_url, $api_key, $api_secret)
    {
        $this->base_url = $base_url;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }


    public function tumeny_wc_gateway_get_token() {
        $response = wp_remote_post($this->base_url.'/api/token', array(
            'method'      => 'POST',
            'sslverify' => FALSE,
            'headers' => array(
                'apiKey' => $this->api_key,
                'apiSecret' => $this->api_secret,
                'content-type' => 'application/json'
            ),
        ));

        $body = json_decode(wp_remote_retrieve_body( $response ), true);
        return $body['token'];
    }

    public function tumeny_wc_gateway_create_payment($order, $callback_url) {
        $response = wp_remote_post($this->base_url.'/api/v1/plugin/payment', array(
            'method'      => 'POST',
            'sslverify' => FALSE,
            'headers' => array(
                'Authorization' => 'Bearer '.$this->tumeny_wc_gateway_get_token(),
                'content-type' => 'application/json'
            ),
            'body' => json_encode(array(
                'description' => 'WooTumeny Payment',
                'customerFirstName' => $order->get_billing_first_name(),
                'customerLastName' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phoneNumber' => $order->get_billing_phone(),
                'amount' => $order->get_total(),
                'thirdPartyReferenceNumber' => $order->get_id(),
                'callbackUrl' => $callback_url,
            )),
        ));

        $body = json_decode(wp_remote_retrieve_body( $response ), true);
        return $body['payment']['id'];
    }

    public function tumeny_wc_gateway_get_payment_status($payment_id) {
        $response = wp_remote_get($this->base_url.'/api/v1/payment/'.$payment_id, array(
            'method'      => 'GET',
            'sslverify' => FALSE,
            'headers' => array(
                'Authorization' => 'Bearer '.$this->tumeny_wc_gateway_get_token(),
                'content-type' => 'application/json'
            ),
        ));

        $body = json_decode(wp_remote_retrieve_body( $response ), true);
        return $body['payment']['status'];
    }

}