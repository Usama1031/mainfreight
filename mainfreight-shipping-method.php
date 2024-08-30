<?php

/*
 Plugin Name: Mainfrieght Shipping Method
 Description: Add the mainfrieght shipping method to your store. 
 Version: 1.0
 Author: Usama S. 
 Author URI: https://wpscholor.com

*/

if (!defined('WPINC')) {
    die;
}

if (!defined('MAINFREIGHT_PLUGIN_DI_URL')) {
	define('MAINFREIGHT_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
}

if( ! defined ('MAINFREIGHT_PLUGIN_DIR')) {
    define('MAINFREIGHT_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

require MAINFREIGHT_PLUGIN_DIR . 'includes/class-mainfreight-shipping.php';
require MAINFREIGHT_PLUGIN_DIR . 'functions.php';

add_action( 'woocommerce_shipping_init', 'mainfreight_shipping_method_init' );

add_filter( 'woocommerce_shipping_methods', 'add_mainfreight_shipping_method' );

add_action( 'wp_enqueue_scripts','mainfreight_enqueue_scripts');

add_filter('woocommerce_checkout_fields', 'mainfreight_add_suburb_checkout_field');

add_action('woocommerce_checkout_update_order_meta', 'mainfreight_save_suburb_order_meta');

add_filter('woocommerce_cart_shipping_packages', 'handle_shipping_address_type');

add_action('woocommerce_product_options_dimensions', 'add_custom_volume_field');

add_action('woocommerce_process_product_meta', 'save_custom_volume_field');

