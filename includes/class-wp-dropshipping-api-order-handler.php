<?php
if (!class_exists('WPDropshippingAPIOrderHandler')) {

class WPDropshippingAPIOrderHandler {

    public function __construct() {
        add_action('woocommerce_thankyou', [$this, 'send_order_to_dropshipping_api'], 10, 1);
        // add_action('woocommerce_order_status_completed', [$this, 'send_order_to_dropshipping_api'], 10, 1);
    }

    public function send_order_to_dropshipping_api($order_id) {
        if (!$order_id) {
            return;
        }
       $send_to_third_party = get_post_meta($order_id, 'send to third party', true);
       if($send_to_third_party){
        return;
       }
       
        $order = new WC_Order($order_id);

        // Loop through order items to retrieve supplier names
        $suppliers = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $supplier_name = get_post_meta($product_id, 'supplier_name', true); 
           
            if ($supplier_name) {
                if (!isset($suppliers[$supplier_name])) {
                    $suppliers[$supplier_name] = [];
                }
                $suppliers[$supplier_name][] = $item;
            }
        }

        // Process the order for each supplier
        foreach ($suppliers as $supplier_name => $items) {
            $supplier_info = $this->get_supplier_info($supplier_name);
           
            if ($supplier_info) {
                $xml = $this->generate_order_xml($order_id, $order, $items, $supplier_info['account_number'], $supplier_info['api_key']);
                error_log('No supplier info found for '.$xml ); 
                $this->third_party_integration_via_curl($xml, $supplier_info['api_url']);
                update_post_meta($order_id, 'send to third party', true);
                
            } else {
                error_log('No supplier info found for ' . $supplier_name);
            }
        }
    }

    public function get_item_supplier($order_id){
        if (!$order_id) {
            return;
        }
        
        $order = new WC_Order($order_id);

        // Loop through order items to retrieve supplier names
        $suppliers = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $supplier_name = get_post_meta($product_id, 'supplier_name', true);
           
            if ($supplier_name) {
                if (!isset($suppliers[$supplier_name])) {
                    $suppliers[$supplier_name] = [];
                }
                $suppliers[$supplier_name][] = $item;
            }
        }
        $supplier_info = [];
        // Process the order for each supplier
        foreach ($suppliers as $supplier_name => $items) {
            $supplier_info[] = $this->get_supplier_info($supplier_name);
        }

        return $supplier_info;

    }

    private function get_supplier_info($supplier_name) {
        $suppliers = get_option('wp_dropshipping_suppliers', []);
        foreach ($suppliers as $supplier) {
            if ($supplier['name'] === $supplier_name) {
                return $supplier;
            }
        }
        return false;
    }

    private function generate_order_xml($order_id, $order, $items, $account_number, $account_api) {

        $order_data = array(
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'customer_email' => $order->get_billing_email(),
            'shipping_address1' => $order->get_shipping_address_1(),
            'shipping_address2' => $order->get_shipping_address_2(),
            'shipping_city' => $order->get_shipping_city(),
            'shipping_state' => $order->get_shipping_state(),
            'shipping_zip' => $order->get_shipping_postcode(),
            'shipping_country' => $order->get_shipping_country(),
            'shipping_phone' => $order->get_shipping_phone(),
            'shipping_instructions' => $order->get_customer_note(),
            'items' => $items,
            'total_amount' => $order->get_total()
        );

       // Initialize the DOMDocument object
        $dom = new DOMDocument('1.0', 'ISO-8859-1');
        
        // Create the root element <HPEnvelope>
        $hpEnvelope = $dom->createElement('HPEnvelope');
        $dom->appendChild($hpEnvelope);

        // Add the <account> element
        $account = $dom->createElement('account', $account_number);
        $hpEnvelope->appendChild($account);

        // Add the <password> element
        $password = $dom->createElement('password', $account_api);
        $hpEnvelope->appendChild($password);

        // Create the <order> element
        $order = $dom->createElement('order');
        $hpEnvelope->appendChild($order);

        // Add child elements to <order>
        $order->appendChild($dom->createElement('reference', $order_id));
        $order->appendChild($dom->createElement('shipby', 'P002'));                                           
        $order->appendChild($dom->createElement('date', date("m/d/Y")));
        
        // Create the <items> element
        $items = $dom->createElement('items');
        $order->appendChild($items);
        foreach ($order_data['items'] as $item) {
            $product_id = $item['product_id'];
            $product = new WC_Product( $product_id ); 
            $sku = $product->get_sku();
                $item1 = $dom->createElement('item');
                $item1->appendChild($dom->createElement('sku', $sku));
                $item1->appendChild($dom->createElement('qty', $item['quantity']));
                $items->appendChild($item1);
            }
        // Add the rest of the elements
        $order->appendChild($dom->createElement('last',$order_data['first_name']));
        $order->appendChild($dom->createElement('first', $order_data['last_name']));
        $order->appendChild($dom->createElement('address1',$order_data['shipping_address1']));
        $order->appendChild($dom->createElement('address2', $order_data['shipping_address2']));
        $order->appendChild($dom->createElement('city', $order_data['shipping_city']));
        $order->appendChild($dom->createElement('state', $order_data['shipping_state']));
        $order->appendChild($dom->createElement('zip', $order_data['shipping_zip']));
        $order->appendChild($dom->createElement('country',$order_data['shipping_country']));
        $order->appendChild($dom->createElement('phone', $order_data['shipping_phone']));
        $order->appendChild($dom->createElement('emailaddress', $order_data['customer_email']));
        $order->appendChild($dom->createElement('instructions', $order_data['shipping_instructions']));
        $order->appendChild($dom->createElement('packingslip_name', 'valentines'));                                      // Change if Supplier change

        // Save the XML document as a string (or you can save it to a file)
        $xml_string = $dom->saveXML();
        
        // Output or return the XML string
        return $xml_string;
    }

    public function third_party_integration_via_curl($xml, $api_url) {

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL,$api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                curl_setopt ($ch, CURLOPT_POST, true);
                curl_setopt ($ch, CURLOPT_POSTFIELDS, "xmldata=".$xml);

                $data = curl_exec($ch);
                $info = curl_getinfo($ch);

                if ($data === false || $info['http_code'] != 200) {
                    $data = "No cURL data returned for $api_url [". $info['http_code']. "]";
                    if (curl_error($ch)) {
                        $data .= "\n". curl_error($ch);
                    }
                    echo $data;
                    exit;
                }
                header("Content-Type: text/xml; charset=utf-8");
                return $data;
    }
}
}