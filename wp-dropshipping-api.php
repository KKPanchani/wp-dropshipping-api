<?php
/*
Plugin Name: WP Dropshipping API
Description: Sends order details to a third-party API for dropshipping and get Shipping updated info.
Version: 1.0
Author: Krishna Panchani
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-dropshipping-api-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-dropshipping-api-order-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-dropshipping-api-api-client.php';

// Initialize the plugin
function wp_dropshipping_api_init() {
    // Initialize settings
    $settings = new WPDropshippingAPISettings();

    // Initialize order handler
    $order_handler = new WPDropshippingAPIOrderHandler();

    // Initialize API client
    $api_client = new WPDropshippingAPIClient();
}
add_action('plugins_loaded', 'wp_dropshipping_api_init');
?>