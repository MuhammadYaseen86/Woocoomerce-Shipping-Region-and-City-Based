<?php
/** 
* Plugin Name: Woocommerce Province and City Based Shipping
* Plugin URI: https://doozielabs.com 
* Description: Custom Province and City Based Shipping Shipping Method for WooCommerce 
* Version: 1.0.0 
* Author: Doozie Labs 
* Author URI: https://doozielabs.com
* License: GPL-3.0+ 
* License URI: http://www.gnu.org/licenses/gpl-3.0.html 
* Domain Path: /lang 
* Text Domain: woocommerce-province-and-city-based-shipping 
*/

// Enqueue TutsPlus Shipping CSS file
function woocommerce_province_and_city_based_shipping_enqueue_styles() {
    // Get the plugin directory URL
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue select.min.css from the assets folder
    wp_enqueue_style('woocommerce-province-and-city-based-shipping-select-css', $plugin_url . '/assets/css/select2.min.css', array(), '1.0.0', 'all');
    wp_enqueue_script('woocommerce-province-and-city-based-shipping-select-js', $plugin_url . '/assets/js/select2.min.js', array(), '1.0.0', 'all');

    wp_enqueue_script('custom-shipping',  $plugin_url . '/assets/js/custom-shipping.js', array('jquery'), '1.0', true);
    wp_localize_script( 'custom-shipping', 'ajax_url', admin_url( 'admin-ajax.php' ) );
}
add_action('wp_enqueue_scripts', 'woocommerce_province_and_city_based_shipping_enqueue_styles');

if (!defined('WPINC')) {
    die;
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function woocommerce_province_and_city_based_shipping_method() {
        if (!class_exists('WoocommerceProvinceAndCityBased_Shipping_Method')) {
            class WoocommerceProvinceAndCityBased_Shipping_Method extends WC_Shipping_Method {

                public function __construct() {
                    $this->id                 = 'woocommerceprovinceandcitybasedshipping';
                    $this->method_title       = __('Woocommerce Province and City Based Shipping', 'woocommerceprovinceandcitybasedshipping');
                    $this->method_description = __('This plugin is used on Woocommerce Shipping based on Province and City', 'woocommerceprovinceandcitybasedshipping');
                    $this->availability       = 'including';
                    $this->countries          = array('PK'); // Limit the shipping method to Pakistan
                    $this->init();
                    $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                    $this->title   = isset($this->settings['title']) ? $this->settings['title'] : __('Shipping', 'woocommerceprovinceandcitybasedshipping');
                }

                function init() {
                    $this->init_form_fields();
                    $this->init_settings();
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                function init_form_fields() {
                    $this->form_fields = array(
                        'enabled' => array(
                            'title'       => __('Enable', 'woocommerceprovinceandcitybasedshipping'),
                            'type'        => 'checkbox',
                            'description' => __('Enable this shipping.', 'woocommerceprovinceandcitybasedshipping'),
                            'default'     => 'yes',
                        ),
                        'title'   => array(
                            'title'       => __('Title', 'woocommerceprovinceandcitybasedshipping'),
                            'type'        => 'text',
                            'description' => __('Title to be displayed on the site', 'woocommerceprovinceandcitybasedshipping'),
                            'default'     => __('Shipping', 'woocommerceprovinceandcitybasedshipping'),
                        ),
                    );
                }

                public function calculate_shipping($package = array()) {
                    //print_r($package);
                    $country = $package['destination']['country'];
                    $state   = $package['destination']['state'];
                    $city    = $package['destination']['city'];
                    $cost    = 0;
    
    
                    // Check if the destination country is Pakistan and handle shipping costs based on state
                    if ($country === 'PK') {
                        if ($state === 'SD' && $city === "Karachi") {
                            $cost = 200; // states Sindh and city Karachi
                        }else if ($state === 'SD') {
                             $cost = 300; // Sindh
                        } elseif ($state === 'PB' || $state === 'KP') {
                             $cost = 350; // Other states in Pakistan
                        } else {
                            $cost = 400; // Other states in Pakistan
                        }
                    }

                    $rate = array(
                        'id'    => $this->id,
                        'label' => $this->title,
                        'cost'  => $cost,
                    );

                    $this->add_rate($rate);
                }
            }
        }
    }

    add_action('woocommerce_shipping_init', 'woocommerce_province_and_city_based_shipping_method');

    function add_woocommerce_province_and_city_based_shipping_method($methods) {
        $methods[] = 'WoocommerceProvinceAndCityBased_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_woocommerce_province_and_city_based_shipping_method');
    
    function get_updated_shipping_cost() {
        // Get the selected state and country from the AJAX request
        $selected_state = $_POST['selected_state'];
        $selected_city = $_POST['selected_city'];
        $country = $_POST['country'];
    
        // Calculate the updated shipping cost based on $selected_state and $country
        $updated_shipping_cost = calculate_shipping_cost($selected_state, $selected_city, $country);
    
        // Send the updated shipping cost back as a JSON response
        echo json_encode(array('updated_shipping_cost' => $updated_shipping_cost));
        wp_die();
    }
    add_action('wp_ajax_get_updated_shipping_cost', 'get_updated_shipping_cost');
    add_action('wp_ajax_nopriv_get_updated_shipping_cost', 'get_updated_shipping_cost');
    
    function calculate_shipping_cost($selected_state, $selected_city, $country) {
        // Define shipping costs based on states and countries
        $shipping_costs = array(
            'SD' => 300,
            'PB' => 350,
            'KP' => 350,
        );
        $selected_city = 'Karachi';
    
        // Check if the selected state exists in the shipping costs array
        // if (array_key_exists($selected_state, $shipping_costs)) {
        //     // Return the shipping cost for the selected state
        //     return $shipping_costs[$selected_state];
        // } elseif ($country == 'PK') {
        //     // If the state is not found, set a default shipping cost for Pakistan (PK)
        //     return 299; // Set your default shipping cost here
        // } else {
        //     // If the country is not Pakistan, set a default shipping cost for other countries
        //     return 299; // Set your default international shipping cost here
        // }
        
        if (array_key_exists($selected_state, $shipping_costs)) {
            // Return the shipping cost for the selected state
            return $shipping_costs[$selected_state];
        }else if ($selected_state === 'SD' && $selected_city ) {
            return 200; // Set the shipping cost to 99 for Karachi
        }else {
            return 400;
        }
    }
}



