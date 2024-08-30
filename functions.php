<?php

// function to add the mainfreight to shipping methods

function add_mainfreight_shipping_method( $methods ) {
    $methods['mainfreight_shipping'] = 'WC_Mainfreight_Shipping_Method';
    return $methods;
}

// function to load the scripts

function mainfreight_enqueue_scripts(){
    if (is_checkout()) {
        wp_enqueue_script('mainfreight-recalculate-shipping', MAINFREIGHT_PLUGIN_DIR_URL . 'scripts/script.js', array('jquery'));

    }
}

// add suburb field to checkout

function mainfreight_add_suburb_checkout_field($fields) {
    $fields['billing']['billing_suburb'] = array(
        'type'        => 'text',
        'label'       => __('Suburb'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 65,
    );

    $fields['shipping']['shipping_suburb'] = array(
        'type'        => 'text',
        'label'       => __('Suburb'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 65,
    );

    return $fields;
}

// update the order meta data

function mainfreight_save_suburb_order_meta($order_id) {
    if (!empty($_POST['billing_suburb'])) {
        update_post_meta($order_id, 'billing_suburb', sanitize_text_field($_POST['billing_suburb']));
    }
    if (!empty($_POST['shipping_suburb'])) {
        update_post_meta($order_id, 'shipping_suburb', sanitize_text_field($_POST['shipping_suburb']));
    }
}

// modify the woocommerce shipping packages

function handle_shipping_address_type($packages) {
    // Parse the post data from the AJAX request
    if (isset($_POST['post_data'])) {
        parse_str(sanitize_text_field($_POST['post_data']), $post_data);
    } else {
        $post_data = array();
    }

    // Loop through each package and modify the destination
    foreach ($packages as $i => $package) {
        $packages[$i]['destination']['billing_suburb'] = !empty($post_data["billing_suburb"]) ? sanitize_text_field($post_data["billing_suburb"]) : '';
        $packages[$i]['destination']['shipping_suburb'] = !empty($post_data["shipping_suburb"]) ? sanitize_text_field($post_data["shipping_suburb"]) : '';
    }

    // Return the modified packages
    return $packages;
}

// add volume field to product data section

function add_custom_volume_field() {
    woocommerce_wp_text_input(array(
        'id' => '_volume',
        'label' => __('Volume (m³)', 'woocommerce'),
        'description' => __('Enter the volume of the product in cubic meters.', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => array(
            'step' => '0.0001',
            'min' => '0'
        ),
    ));
}

// save the product volume field

function save_custom_volume_field($post_id) {
    $volume = isset($_POST['_volume']) ? sanitize_text_field($_POST['_volume']) : '';
    update_post_meta($post_id, '_volume', $volume);
}
