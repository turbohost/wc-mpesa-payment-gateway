<?php
/*
Plugin Name: THC M-Pesa Payment Gateway
Plugin URI: http://turbohost.co.mz/wo-mpesa-payment-gateway/
Description: MPESA Payment Gateway.
Version: 1.0
Requires PHP: 7.2
WC requires at least: 4.0.0
WC tested up to: 4.0.1
Domain Path: /languages
Author: karson <karson@turbohost.co.mz>
Author URI: http://turbohost.co.mz/

    Copyright: © 2019 karson <karson@turbohost.co.mz>.
    License: GNU General Public License v2
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define('WC_MPESA_DATABASE_VERSION', 1.0);
add_action('plugins_loaded', 'wc_mpesa_init', 0);
add_action('plugins_loaded', 'wc_mpesa_update_check');
add_action('init',  'wc_mpesa_start_session', 1);
register_activation_hook(__FILE__, 'wc_mpesa_install');
function  wc_mpesa_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wc_mpesa_transactions";

    if (!get_option('wc_mpesa_version', WC_MPESA_DATABASE_VERSION)) {



        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        phone bigint(12) NOT NULL,
        reference_id varchar(10) NOT NULL,
        result_code varchar(50) NULL,
        date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status varchar(20) NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('wc_mpesa_version', WC_MPESA_DATABASE_VERSION);
    }
}

function wc_mpesa_update_check()
{
    if (WC_MPESA_DATABASE_VERSION != get_option('wc_mpesa_version')) {
        wc_mpesa_install();
    }
}

function wc_mpesa_start_session()
{
    if (!session_id()) {
        session_start();
    }
}


function wc_mpesa_init()
{
    require 'vendor/autoload.php';
    if (!class_exists('WC_Payment_Gateway')) return;
    /**
     * Localisation
     */
    load_plugin_textdomain('wc-mpesa', false, dirname(plugin_basename(__FILE__)) . '/languages');


    /**
     * Gateway class
     */
    class WC_Gateway_MPESA extends WC_Payment_Gateway
    {


        public function __construct()
        {

            $this->id                 = 'wc-mpesa';
            $this->icon               = apply_filters('wc-mpesa_icon', plugins_url('assets/img/mpesa-logo.jpeg', __FILE__));
            $this->has_fields         = false;
            $this->method_title       = __('THC M-PESA Payment Gateway', 'woocommerce');
            $this->method_description = __('Allow to pay via M-PESA', 'wc-mpesa');

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
            $this->env = $this->get_option('env');

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'payment_form_html'));
            // remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10);
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
                    'title' => __('Enable/Disable', 'wc-mpesa'),
                    'type' => 'checkbox',
                    'label' => __('Enable WooCommerce M-pesa Payment Gateway', 'wc-mpesa'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'wc-mpesa'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout', 'wc-mpesa'),
                    'default' => __('M-PESA Payment Gateway', 'wc-mpesa'),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'wc-mpesa'),
                    'type' => 'textarea',
                    'default' => __('Pay via mpesa', 'wc-mpesa')
                ),
                'api_key' => array(
                    'title' => __('API Key', 'wc-mpesa'),
                    'type' => 'text',
                    'default' => __('', 'wc-mpesa')
                ),
                'public_key' => array(
                    'title' => __('Public Key', 'wc-mpesa'),
                    'type' => 'textarea',
                    'default' => __('', 'wc-mpesa')
                ),
                'service_provider' => array(
                    'title' => __('Service Provider Code', 'wc-mpesa'),
                    'type' => 'text',
                    'default' => __('', 'wc-mpesa')
                ),
                'test' => array(
                    'title' => __('Test Mode', 'wc-mpesa'),
                    'type' => 'checkbox',
                    'default' => __('no', 'wc-mpesa'),
                    'label' => __('Enable Test Environment', 'wc-mpesa'),
                ),
            );
        }





        public function payment_fields()
        {
            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ($this->env) {
                    $this->description .= '<br/> TEST MODE ENABLED.';
                    $this->description  = trim($this->description);
                }
                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';


            // // I recommend to use inique IDs, because other gateways could already use 
            echo '<div class="form-row form-row-wide"><label>Mpesa Number <span class="required">*</span></label>
                <input name="mpesa_number" type="tel" autocomplete="off">
                </div>';

            echo '<div class="clear"></div></fieldset>';
        }


        public function validate_fields()
        {
            //validate currency
            if ('MZN' != get_woocommerce_currency()) {
                wc_add_notice(__('Currency not supported!', 'wc-mpesa'), 'error');
                return false;
            }
            //validate  phone
            $mpesa_number = filter_input(INPUT_POST, 'mpesa_number', FILTER_VALIDATE_INT);

            if (!isset($mpesa_number)) {
                wc_add_notice(__('Phone number is required!', 'wc-mpesa'), 'error');
                return false;
            }
            if (!$mpesa_number || strlen($mpesa_number) != 9 || !preg_match('/^84[0-9]{7}$/', $mpesa_number)) {
                wc_add_notice(__('Phone number is incorrect!', 'wc-mpesa'), 'error');
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
            wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', [], false, true);
            wp_enqueue_script('axios', 'https://unpkg.com/axios/dist/axios.min.js', array('vue'), false, true);
            wp_enqueue_script('payment', plugin_dir_url(__FILE__) . '/assets/js/payment.js', array('vue', 'axios'), false, true);
            wp_localize_script('payment', 'payment_text', [
                'intro'  => [
                    'title' => __('Payment Information', 'wc-mpesa'),
                    'description'  => __('<ul><li>Check your details before pressing the button below.</li><li>Your phone number MUST be registered with MPesa (and Active) for this to work.</li><li>You will receive a pop-up on the phone requesting payment confirmation.</li><li>Enter your service PIN (MPesa) to continue.</li><li>You will receive a confirmation message shortly thereafter</li></ul>', 'wc-mpesa'),
                ],
                'requested' => [
                    'title' => __('Payment request sent!', 'wc-mpesa'),
                    'description' => __('Check your phone and enter your PIN code to confirm payment ...', 'wc-mpesa')
                ],
                'received' => [
                    'title' => __('Payment received!', 'wc-mpesa'),
                    'description' => __('Your payment has been received and your order will be processed soon.', 'wc-mpesa')
                ],
                'timeout' => [
                    'title' => __('Payment timeout exceeded!', 'wc-mpesa'),
                    'description' => __('Use your browser\'s back button and try again.', 'wc-mpesa')
                ],
                'failed' => [
                    'title' => __('Payment failed!', 'wc-mpesa'),
                    'description' => __('Use your browser\'s back button and try again.', 'wc-mpesa')
                ]
            ]);
            wp_enqueue_style('style', plugin_dir_url(__FILE__) . '/assets/css/style.css', false, '1.1', 'all');
        }

        function payment_form_html($order_id)
        {
            $order = new WC_Order($order_id);
            //get transaction using reference_id
            $return_url = $this->get_return_url($order);
            require plugin_dir_path(__FILE__) . '/templates/payment.php';
            // modify post object here
        }






        /**
         * Process the order payment status
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {

            $order = new WC_Order($order_id);
            $phone = '258' . filter_input(INPUT_POST, 'mpesa_number', FILTER_SANITIZE_NUMBER_INT);

            $checkout_url = $order->get_checkout_payment_url(true);

            //Save the Mpesa number to process 
            $_SESSION['phone'] = $phone;
            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $checkout_url
            );
        }




        function process_action()
        {
            global $wpdb;
            // start_session();

            
            $mpesa = new \Karson\MpesaPhpSdk\Mpesa();
            $mpesa->setApiKey($this->api_key);
            $mpesa->setPublicKey($this->public_key);
            if (!$this->test) {
                $mpesa->setEnv('live');
            }


            $order = new WC_Order(filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT));
            $order_id = $order->get_id();
            if ($order_id) {
                $amount = $order->get_total();
                $reference_id = $this->generate_reference_id($order_id);
                $phone = $_SESSION['phone'];
                $result = $mpesa->c2b($order_id, $phone, $amount, $reference_id, $this->service_provider);
                $response['raw'] =  $result->response;

                if ($result->response->output_ResponseCode == 'INS-0') {
                    $response['status'] = 'success';
                    // Mark as paid 
                    $order->payment_complete();
                    // Reduce stock levels
                    $order->reduce_order_stock();

                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note('Your order is paid! Thank you!', true);


                    // Remove cart
                    WC()->cart->empty_cart();
                } else {
                    // Mark as Failed
                    $response['status'] = 'failed';
                    $order->update_status('failed', __('Payment failed', 'wc-mpesa'));
                }

                $wpdb->insert("{$wpdb->prefix}wc_mpesa_transactions", [
                    'phone' => $phone,
                    'order_id' => $order->get_id(),
                    'reference_id' => $reference_id,
                    'status' => $response['status'],
                    'result_code' => $result->response->output_ResponseCode ?? null,
                ]);
            }
            wp_send_json($response);
        }


        function generate_reference_id($order_id)
        {
            //generate uniq reference_id
            return substr($order_id . bin2hex(random_bytes(5)), 10);
        }

        function wc_minimum_order_amount()
        {
            // Set this variable to specify a minimum order value
            $minimum = 10;

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
