<?php 

function mainfreight_shipping_method_init() {
    if ( ! class_exists( 'WC_Mainfreight_Shipping_Method' ) ) {
        class WC_Mainfreight_Shipping_Method extends WC_Shipping_Method {
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct( $instance_id = 0 ) {
                $this->id                 = 'mainfreight_shipping'; // ID for your shipping method. Should be unique.
                $this->instance_id        = absint( $instance_id );
                $this->method_title       = 'Mainfreight Shipping Method';  // Title shown in admin
                $this->method_description = 'Allows you to add the Mainfreight shipping method to your store and get the pricing from their API based on the product volume and weight.'; // Description shown in admin

                $this->supports           = array(
                    'shipping-zones',
                    'instance-settings',
                );

                // Load the form fields.
                $this->init_form_fields();
                // Load the settings.
                $this->init_settings();

                $this->enabled = $this->get_option('enabled');
                $this->title = $this->get_option('title');
                $this->api_key = $this->get_option('api_key');
                $this->account_code = $this->get_option('account_code');
                $this->origin_suburb = $this->get_option('origin_suburb');
                $this->origin_city = $this->get_option('origin_city');
                $this->origin_postcode = $this->get_option('origin_postcode');
                $this->default_weight = $this->get_option('default_weight');
                $this->default_volume = $this->get_option('default_volume');
                $this->debug_mode = $this->get_option('debug_mode');

                // Save settings in admin if you have any defined.
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            /**
             * Define settings field for this shipping method
             *
             * @return void
             */
            public function init_form_fields() {

                $weightUnit = strtolower(get_option('woocommerce_weight_unit'));
                $this->instance_form_fields = array(
                    'title' => array(
                        'title' => 'Mainfreight',
                        'type' => 'text',
                        'description' => 'Title to be displayed during checkout',
                        'default' => 'Mainfreight',
                        'desc_tip' => true
                    ),
                    'api_key' => array(
                        'title' => 'API Key',
                        'type' => 'text',
                        'description' => 'Enter your API Key',
                        'default' => '',
                        'desc_tip' => true
                    ),
                    'account_code' => array(
                        'title' => 'Account Code',
                        'type' => 'text',
                        'description' => 'Enter your Mainfreight account code',
                        'default' => '',
                        'desc_tip' => true
                    ),
                    'origin_suburb' => array(
                        'title' => 'Origin Suburb',
                        'type' => 'text',
                        'description' => 'Enter the suburb from where the package will be picked by Mainfreight',
                        'default' => '',
                        'desc_tip' => true
                    ),
                    'origin_city' => array(
                        'title' => 'Origin City',
                        'type' => 'text',
                        'description' => 'Enter the city from where the package will be picked by Mainfreight',
                        'default' => '',
                        'desc_tip' => true
                    ),
                    'origin_postcode' => array(
                        'title' => 'Origin PostCode',
                        'type' => 'text',
                        'description' => 'Enter the postcode from where the package will be picked by Mainfreight',
                        'default' => '',
                        'desc_tip' => true
                    ),
                    'default_weight' => array(
                        'title' => 'Default Weight of the Package',
                        'type' => 'text',
                        'label' => 'Please only add the positive integers',
                        'description' => $weightUnit,
                        'default' => '1',
                        'css' => 'width: 70px;'
                    ),
                    'default_volume' => array(
                        'title' => 'Default Volume of the Package',
                        'type' => 'text',
                        'default' => '5',
                        'description' => 'Volume (m³)',
                        'css' => 'width: 70px;'
                    ),
                    'debug_mode' => array(
                        'title' => 'Enable Debug Mode',
                        'type' => 'checkbox',
                        'label' => 'Enable',
                        'description' => 'If debug mode is enabled, the shipping method will be activated just for the administrator.',
                        'default' => 'no',
                    ),
                );
            }

            

            /**
             * calculate_shipping function.
             *
             * @access public
             * @param array $package
             * @return void
             */
            public function calculate_shipping( $package = array() ) {
            
                if(!$this->is_plugin_configured()) {
                    wc_add_notice('Mainfreight Shipping plugin is not configured properly. Please contact the store administrator.', 'error');
                    return;
                }
            
                $freightDetails = $this->get_freight_details($package['contents']);
                
            
                if (empty($freightDetails)) {
                    return;
                }
            
                $request_body = $this->build_api_request_body($package['destination'], $freightDetails);
                
            
                $shipping_cost = $this->get_shipping_cost_from_api($request_body);
            
                if ($shipping_cost === 0) {
                    wc_add_notice('Failed to retrieve shipping cost from the shipping service. Please try again later.', 'error');
                    return;
                }

                $rate = array(
                    // 'id'       => $this.id . $this->instance_id,
                    'label'    => $this->title,
                    'cost'     => $shipping_cost,
                );

                // Register the rate.
                $this->add_rate( $rate );
            }

            // GET THE RATES FROM THE API

            public function get_shipping_cost_from_api($request_body) {
                $response = wp_remote_post("https://api.mainfreight.com/transport/1.0/Customer/Rate?region=NZ", array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Secret ' . $this->api_key,
                    ),
                    'body' => json_encode($request_body),
                    'method' => 'POST'
                ));
                // var_dump($response);
                // die();
                if (is_wp_error($response)) {
                    return array('error' => $response->get_error_message());
                }
        
                $body = wp_remote_retrieve_body($response);
                
                $data = json_decode($body, true);
               
        
                if (isset($data['charges'])) {
                    foreach ($data['charges'] as $charge) {
                        if ($charge['name'] === 'TotalExcludingGSTAmount') {
                            return $charge['value'];
                        }
                    }
                }
        
                return 0;
            }
            
            // BUILD THE REQUEST BODY TO SEND API REQUEST

            public function build_api_request_body($deliveryAddress, $freightDetails) {

                $shipping_suburb = ( 
                                    isset($deliveryAddress['shipping_suburb']) && $deliveryAddress['shipping_suburb'] != '') 
                                    ? $deliveryAddress['shipping_suburb'] 
                                    : ((isset($deliveryAddress['billing_suburb']) && $deliveryAddress['billing_suburb'] != '') 
                                    ? $deliveryAddress['billing_suburb'] : ''
                                    );

                
                return array(
                    "account" => array(
                        "code" => 'GESTURES14'
                    ),
                    "serviceLevel" => array(
                        "code" => "M2H"
                    ),
                    "origin" => array(
                        "freightRequiredDateTime" => date('c'),
                        "freightRequiredDateTimeZone" => "New Zealand Standard Time", // Corrected timezone
                        "address" => array(
                            "suburb" => $this->origin_suburb,
                            "postcode" => $this->origin_postcode,
                            "city" => $this->origin_city,
                            "countryCode" => "NZ"
                        )
                    ),
                    "destination" => array(
                        "address" => array(
                            "suburb" => $shipping_suburb,
                            "postcode" => $deliveryAddress['postcode'],
                            "city" => $deliveryAddress['city'],
                            "countryCode" => 'NZ'
                        )
                    ),
                    "freightDetails" => $freightDetails,
                );
            }

            // get the weight, volume and quantities of each item in the cart
        
            public function get_freight_details($cart_items) {
                $freightDetails = array();
                // $cart_items = WC()->cart->get_cart();


                foreach($cart_items as $cart_item_key => $cart_item) {
                    $product = $cart_item['data'];
                    $quantity = $cart_item['quantity'];

                    $weight = ceil($product->get_weight());

                    $volume =  get_post_meta($product->get_id(), '_volume', true);
                    $volume = !empty($volume) ? $volume : $this->default_volume;

                    $total_weight = $weight * $quantity;
                    $total_volume = $volume * $quantity;

                    $freightDetails[] = [
                        "units"  => (string) $quantity,
                        "volume" => (string) $total_volume,
                        "weight" => (string) $total_weight
                    ];
                }

                return $freightDetails;

            }

            // if the default required options are missing

            public function is_plugin_configured() {
                return $this->api_key && $this->account_code && $this->origin_suburb && $this->origin_city && $this->origin_postcode;
            }


        
        }

        // end of the class
    }

    // end of the if condition that checks if the class name already exsts

}
// end of the function in which the class is added

?>