<?php
/*
Plugin Name: Mobile M-Pesa Payment Gateway
Plugin URI: https://wordpress.org/plugins/wc-m-pesa-payment-gateway/
Description: Receive payments directly to your store through the Vodacom Mozambique M-Pesa.
Version: 1.2.0
WC requires at least: 4.0.0
WC tested up to: 4.1.1
Author: karson <karson@turbohost.co.mz>
Author URI: http://karsonadam.com

    Copyright: © 2019 karson <karson@turbohost.co.mz>.
    License: GNU General Public License v2
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

$wc_mpesa_db_version = 1.1;
add_action('plugins_loaded', 'wc_mpesa_init', 0);
add_action('plugins_loaded', 'wc_mpesa_update_check');
register_activation_hook(__FILE__, 'wc_mpesa_install');
function  wc_mpesa_install()
{
    global $wc_mpesa_db_version;
    global $wpdb;
    $table_name = $wpdb->prefix . "wc_mpesa_transactions";

    if (!get_option('wc_mpesa_version', $wc_mpesa_db_version)) {



        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        phone bigint(12) NOT NULL,
        reference_id varchar(20) NOT NULL,
        result_code varchar(20) NULL,
        date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status varchar(20) NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('wc_mpesa_version', $wc_mpesa_db_version);
    }
}

function wc_mpesa_update_check()
{
    global $wc_mpesa_db_version;
    if ($wc_mpesa_db_version != get_option('wc_mpesa_version')) {
        wc_mpesa_install();
    }
}




function wc_mpesa_init()
{
    require 'vendor/autoload.php';
    if (!class_exists('WC_Payment_Gateway')) return;
    /**
     * Localisation
     */
    load_plugin_textdomain('wc-mpesa-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages');


    /**
     * Gateway class
     */
    class WC_Gateway_MPESA extends WC_Payment_Gateway
    {


        public function __construct()
        {

            $this->id                 = 'wc-mpesa-payment-gateway';
            $this->icon               = apply_filters('wc-mpesa_icon', plugins_url('assets/img/m-pesa-logo.png', __FILE__));
            $this->has_fields         = false;
            $this->method_title       = __('Mobile M-PESA Payment Gateway', 'wc-mpesa-payment-gateway');
            $this->method_description = __('Allow to pay via M-PESA', 'wc-mpesa-payment-gateway');

            // Load the settings.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->title         = $this->get_option('title');
            $this->description   = $this->get_option('description');
            $this->api_key = $this->get_option('api_key');
            $this->public_key = $this->get_option('public_key');
            $this->service_provider = $this->get_option('service_provider');
            $this->test = $this->get_option('test');

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'payment_form_html'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('woocommerce_api_process_action', array($this, 'process_action'));

            /**
             * Set a minimum order amount for checkout
             */
            add_action('woocommerce_checkout_process', 'wc_minimum_order_amount');
            add_action('woocommerce_before_cart', 'wc_minimum_order_amount');
        }





        /**
         * Create form fields for the payment gateway
         *
         * @return void
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-mpesa-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Mobile M-Pesa Payment Gateway', 'wc-mpesa-payment-gateway'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'wc-mpesa-payment-gateway'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout', 'wc-mpesa-payment-gateway'),
                    'default' => __('Mobile M-Pesa Payment Gateway', 'wc-mpesa-payment-gateway'),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'wc-mpesa-payment-gateway'),
                    'type' => 'textarea',
                    'default' => __('Pay via mpesa', 'wc-mpesa-payment-gateway')
                ),
                'api_key' => array(
                    'title' => __('API Key', 'wc-mpesa-payment-gateway'),
                    'type' => 'text',
                    'default' => __('', 'wc-mpesa-payment-gateway')
                ),
                'public_key' => array(
                    'title' => __('Public Key', 'wc-mpesa-payment-gateway'),
                    'type' => 'textarea',
                    'default' => __('', 'wc-mpesa-payment-gateway')
                ),
                'service_provider' => array(
                    'title' => __('Service Provider Code', 'wc-mpesa-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Use 171717 for testing', 'wc-mpesa-payment-gateway'),
                    'default' => 171717
                ),
                'test' => array(
                    'title' => __('Test Mode', 'wc-mpesa-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Test Environment', 'wc-mpesa-payment-gateway'),
                    'default' => 'yes',
                ),

            );
        }





        public function payment_fields()
        {
            session_start();
            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ('yes' == $this->test) {
                    $this->description .= __('<br/> TEST MODE ENABLED.', 'wc-mpesa-payment-gateway');
                    $this->description  = trim($this->description);
                }
                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            $phone = $_SESSION['wc_mpesa_phone'] ?? '';

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';


            //Use unique IDs, because other gateways could already use 
            echo '<div class="form-row form-row-wide"><label>' . esc_html__('Mpesa number', 'wc-mpesa-payment-gateway') . '<span class="required">*</span></label>
                <input name="mpesa_number" type="tel" value="' . esc_attr($phone) . '" placeholder="' . esc_attr__('ex: 84 123 4567', 'wc-mpesa-payment-gateway') . '">
                </div>';

            echo '<div class="clear"></div></fieldset>';
        }


        public function validate_fields()
        {
            //validate currency
            if ('MZN' != get_woocommerce_currency()) {
                wc_add_notice(__('Currency not supported!', 'wc-mpesa-payment-gateway'), 'error');
                return false;
            }
            //validate  phone
            $mpesa_number = filter_input(INPUT_POST, 'mpesa_number', FILTER_VALIDATE_INT);

            if (!isset($mpesa_number)) {
                wc_add_notice(__('Phone number is required!', 'wc-mpesa-payment-gateway'), 'error');
                return false;
            }
            if (!$mpesa_number || strlen($mpesa_number) != 9 || !preg_match('/^84[0-9]{7}$/', $mpesa_number)) {
                wc_add_notice(__('Phone number is incorrect!', 'wc-mpesa-payment-gateway'), 'error');
                return false;
            }
            return true;
        }



        function payment_scripts()
        {
            if (!is_checkout_pay_page()) {
                return;
            }
            if ('no' == $this->enabled) {
                return;
            }
            // Load only on specified pages
            /**
             * Add styles and scripts
             */
            if (WP_DEBUG) {
                wp_enqueue_script('vue', plugin_dir_url(__FILE__) . '/assets/js/vue.js', [], false, true);
            } else {
                wp_enqueue_script('vue', plugin_dir_url(__FILE__) . '/assets/js/vue.min.js', [], false, true);
            }

            wp_enqueue_script('axios', plugin_dir_url(__FILE__) . '/assets/js/axios.min.js', array('vue'), false, true);
            wp_enqueue_script('payment', plugin_dir_url(__FILE__) . '/assets/js/payment.js', array('vue', 'axios'), false, true);

            wp_localize_script('payment', 'payment_text', [
                'status' => [
                    'intro'  => [
                        'title' => __('Payment Information', 'wc-mpesa-payment-gateway'),
                        'description'  => __('<ul><li>Check your details before pressing the button below.</li><li>Your phone number MUST be registered with MPesa (and Active) for this to work.</li><li>You will receive a pop-up on the phone requesting payment confirmation.</li><li>Enter your service PIN (MPesa) to continue.</li><li>You will receive a confirmation message shortly thereafter</li></ul>', 'wc-mpesa-payment-gateway'),
                    ],
                    'requested' => [
                        'title' => __('Payment request sent!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Check your mobile phone and enter your PIN code to confirm payment ...', 'wc-mpesa-payment-gateway')
                    ],
                    'received' => [
                        'title' => __('Payment received!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Your payment has been received and your order will be processed soon.', 'wc-mpesa-payment-gateway')
                    ],
                    'timeout' => [
                        'title' => __('Payment timeout exceeded!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Use your browser\'s back button and try again.', 'wc-mpesa-payment-gateway')
                    ],
                    'failed' => [
                        'title' => __('Payment failed!', 'wc-mpesa-payment-gateway'),
                        'description' => __('Use your browser\'s back button and try again.', 'wc-mpesa-payment-gateway')
                    ],
                ],
            ]);
            wp_enqueue_style('style', plugin_dir_url(__FILE__) . '/assets/css/style.css', false, false, 'all');
        }

        function payment_form_html($order_id)
        {
            // modify post object here
            $order = new WC_Order($order_id);
            $return_url = $this->get_return_url($order);
            require plugin_dir_path(__FILE__) . '/templates/payment.php';
        }






        /**
         * Process the order payment status
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            session_start();
            $order = new WC_Order($order_id);
            $phone = filter_input(INPUT_POST, 'mpesa_number', FILTER_SANITIZE_NUMBER_INT);
            //save phone to use on new transactions
            $_SESSION['wc_mpesa_phone'] = $phone;

            $checkout_url = $order->get_checkout_payment_url(true);

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $checkout_url
            );
        }




        function process_action()
        {
            session_start();
            global $wpdb;


            $mpesa = new \Karson\MpesaPhpSdk\Mpesa();
            $mpesa->setApiKey($this->api_key);
            $mpesa->setPublicKey($this->public_key);
            if ('yes' != $this->test) {
                $mpesa->setEnv('live');
            }

            //Update code to use wp_send_json status instead custom status to reduce redundancy
            $order = new WC_Order(filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT));
            $order_id = $order->get_id();
            if ($order_id) {
                $amount = $order->get_total();
                $reference_id = $this->generate_reference_id($order_id);
                $phone = "258{$_SESSION['wc_mpesa_phone']}";
                $response = [];
                try {
                    $result = $mpesa->c2b($order_id, $phone, $amount, $reference_id, $this->service_provider);
                } catch (\Exception $e) {
                    $response['status'] = 'failed';
                    $response['raw'] =  $e->getMessage();
                    return wp_send_json_error($response);
                }
                if ('yes' == $this->test) {
                    $response['raw'] =  $result->response;
                }
                if ($result->response->output_ResponseCode == 'INS-0') {
                    // Mark as paid 
                    $order->payment_complete();
                    // Reduce stock levels
                    $order->reduce_order_stock();

                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note('Your order is paid! Thank you!', true);
                    // Remove cart
                    WC()->cart->empty_cart();
                    $response['status'] = 'success';
                } else {
                    // Mark as Failed
                    $response['status'] = 'failed';
                    switch ($result->response->output_ResponseCode) {
                            //show detailed error message
                        case 'INS-13':
                            $error_message  = __('Invalid Shortcode Used!', 'wc-mpesa-payment-gateway');
                            break;
                        case 'INS-16':
                            $error_message  = __('Unable to handle the request due to a temporary overloading!', 'wc-mpesa-payment-gateway');
                            break;
                        case 'INS-996':
                            $error_message  = __('Customer Account Status Not Active!', 'wc-mpesa-payment-gateway');
                            break;
                        case 'INS-2001':
                            $error_message  = __('Initiator authentication error!', 'wc-mpesa-payment-gateway');
                            break;
                        case 'INS-2006':
                            $error_message  = __('Insufficient balance!', 'wc-mpesa-payment-gateway');
                            break;
                        default:
                            break;
                    }
                    //Detect API key error
                    if ($result->response->output_error) {
                        if (strpos($result->response->output_error, 'not authorized')) {
                            $error_message = __('API or Public key is not authorized!', 'wc-mpesa-payment-gateway');
                        } else if ($result->response->output_error = 'Bad API Key') {
                            $error_message = __('Bad API Key!', 'wc-mpesa-payment-gateway');
                        }
                    }
                    $response['error_message'] = $error_message;
                    $order->update_status('failed', __('Payment failed', 'wc-mpesa-payment-gateway'));
                }

                try {
                    $wpdb->insert("{$wpdb->prefix}wc_mpesa_transactions", [
                        'phone' => $phone,
                        'order_id' => $order->get_id(),
                        'reference_id' => $reference_id,
                        'status' => $response['status'],
                        'result_code' => $response['code'] ?? null,
                    ]);
                } catch (\Throwable $th) {
                    $response['raw'] = $th->getMessage();
                }
            }
            wp_send_json($response);
        }


        function generate_reference_id($order_id)
        {
            //generate uniq reference_id
            $random_number = bin2hex(random_bytes(5));
            return substr($order_id . bin2hex(random_bytes(5)), 0, 10);
        }

        function wc_minimum_order_amount()
        {
            // Set this variable to specify a minimum order value
            $minimum = 1;

            if (WC()->cart->total < $minimum) {

                if (is_cart()) {

                    wc_print_notice(
                        sprintf(
                            'Your current order total is %s — you must have an order with a minimum of %s to place your order ',
                            wc_price(WC()->cart->total),
                            wc_price($minimum)
                        ),
                        'error'
                    );
                } else {

                    wc_add_notice(
                        sprintf(
                            'Your current order total is %s — you must have an order with a minimum of %s to place your order',
                            wc_price(WC()->cart->total),
                            wc_price($minimum)
                        ),
                        'error'
                    );
                }
            }
        }
    } //END  WC_Gateway_MPESA


    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_gateway_mpesa_gateway($methods)
    {
        $methods[] = 'WC_Gateway_MPESA';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_mpesa_gateway');
}
