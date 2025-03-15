<?php
if (!class_exists('WPDropshippingAPIClient')) {

    class WPDropshippingAPIClient {

        // public function send_order($order_data, $api_key, $api_url) {
        //     $response = wp_remote_post($api_url, [
        //         'method'    => 'POST',
        //         'body'      => json_encode($order_data),
        //         'headers'   => [
        //             'Content-Type' => 'application/json',
        //             'Authorization' => 'Bearer ' . $api_key,
        //         ],
        //     ]);

        //     return $response;
        // }

        public function check_order_status($order_reference_number, $api_key, $api_url, $account_number) {
            $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
            <HPEnvelope>
                <account>'.$account_number.'</account>
                <password>'.$api_key.'</password>
                <orderstatus>'.$order_reference_number.'</orderstatus>
            </HPEnvelope>';
        
         $curl_call = new WPDropshippingAPIOrderHandler();
         
         $response = $curl_call->third_party_integration_via_curl($xml, $api_url);
         return $response;
        }
    }
}