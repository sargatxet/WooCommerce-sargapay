<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    sargapay
 * @subpackage sargapay/paymentGateway
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    sargapay
 * @subpackage sargapay/paymentGateway
 * @author     trakadev <trakadev@protonmail.com>
 */

if (!defined('WPINC')) {
    die;
}

class Sargapay_Cardano_Gateway extends WC_Payment_Gateway
{
    /**
     * Params for the payment request.
     *
     * @since    2.2.0
     */
    public $id;
    public $icon;
    public $has_fields;
    public $method_title;
    public $method_description;
    public $supports;
    public $blockfrost_key;
    public $blockfrost_test_key;
    public $mpk;
    public $currency;
    public $confirmations;
    public $markup;
    public $time_wait;
    public $testmode;
    public $lightWallets;


    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->id = "sargapay_cardano";
        $this->icon = plugin_dir_url(__FILE__) . '/assets/img/ada_logo.png';
        $this->has_fields = true;
        $this->method_title = 'Sargapay Cardano Gateway';
        $this->method_description = __('Allow customers to pay using Cardano ADA', 'sargapay');
        $this->supports = array('products');
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->lightWallets = 'yes' === $this->get_option('lightWallets');
        $this->mpk = $this->get_option('mpk');
        $this->currency = $this->get_option('currency');
        $this->blockfrost_key = $this->get_option('blockfrost_key');
        $this->blockfrost_test_key = $this->get_option('blockfrost_test_key');
        $this->confirmations = $this->get_option('confirmations');
        $this->markup = $this->get_option('markup');
        $this->time_wait = $this->get_option('time_wait');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
    }

    /**
     * Plugin options
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'sargapay'),
                'label'       => __('Enable Sargapay Gateway', 'sargapay'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => __('no', 'sargapay'),
            ),
            'title' => array(
                'title'       => __('Title', 'sargapay'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'sargapay'),
                'default'     => __('Pay with Cardano ADA', 'sargapay'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'sargapay'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'sargapay'),
                'default'     => __('Pay using Cardano ADA via our super-cool payment gateway.', 'sargapay'),
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'sargapay'),
                'label'       => __('Enable Test Mode', 'sargapay'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode to use TESTNET.', 'sargapay'),
                'default'     => __('yes', 'sargapay'),
                'desc_tip'    => true,
            ),
            'lightWallets' => array(
                'title'       => __('Light Wallets', 'sargapay'),
                'label'       => __('Enable Light Wallets Buttons', 'sargapay'),
                'type'        => 'checkbox',
                'description' => __('Enable light wallets buttons to pay', 'sargapay'),
                'default'     => __('yes', 'sargapay'),
                'desc_tip'    => true,
            ),
            'mpk' => array(
                'title'       => __('Public Address Key for Cardano', 'sargapay'),
                'type'        => 'password',
                'description' => __('Place the Public Address Key to generate Payment Addresses.', 'sargapay'),
            ),
            'confirmations' => array(
                'title'       => __('Confirmations', 'sargapay'),
                'type'        => 'select',
                'default' => '10',
                'description' => __('Confirmations needed to accept a transaction as valid.', 'sargapay'),
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '20' => '20',
                    '30' => '30',
                    '40' => '40',
                    '50' => '50',
                ),
                'desc_tip'    => true,
            ),
            'blockfrost_key' => array(
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Mainnet', 'sargapay') . '</a>',
                'type'        => 'password',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Mainnet.', 'sargapay'),
                'desc_tip'    => true,
            ),
            'blockfrost_test_key' => array(
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Preview.', 'sargapay') . '</a>',
                'type'        => 'password',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Preview.', 'sargapay'),
                'desc_tip'    => true,
            ),
            'currency' => array(
                'title'       => __('Currency', 'sargapay'),
                'type'        => 'select',
                'description' => __('Currency used in case default currency is not supported.', 'sargapay'),
                'default'     => 'USD',
                'options' => array(
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                ),
                'desc_tip'    => true,
            ),
            'markup' => array(
                'title'       => __('Markup', 'sargapay'),
                'type'        => 'text',
                'description' => __('Add a premium to price calculation', 'sargapay'),
                'default'     => '5',
                'desc_tip'    => true,
            ),
            'time_wait' => array(
                'title'       => __('# of hours of waiting for payment', 'sargapay'),
                'type'        => 'text',
                'description' => __('Select the max number of hours to wait for payments', 'sargapay'),
                'default'     => '24',
                'desc_tip'    => true,
            ),
        );
    }

    public function admin_options()
    {
        global $hide_save_button;
        $hide_save_button = true;
?>
        <div id='sargapay'></div>
        <?php

    }


    function process_admin_options()
    {

        parent::process_admin_options();
        $errors = 0;
        if (empty($_POST['woocommerce_sargapay_mpk'])) {
            WC_Admin_Settings::add_error(__('Error: You require a Master Public Key to generate  payment addresses.', 'sargapay'));
            $errors = 1;
        }
        if (!preg_match("/^[A-Za-z0-9_]+$/", $_POST['woocommerce_sargapay_mpk'])) {
            WC_Admin_Settings::add_error(__('Error: Invalid Character in Public Key.', 'sargapay'));
            $errors = 1;
        }
        if (empty($_POST['woocommerce_sargapay_testmode'])) {
            if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_sargapay_blockfrost_key'])) {
                WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for MAINNET.', 'sargapay'));
                $errors = 2;
            }
            if (empty($_POST['woocommerce_sargapay_blockfrost_key'])) {
                WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for MAINNET to validate transactions.', 'sargapay'));
                $errors = 1;
            }
            $errors = $this->check_API_KEY(0, $_POST['woocommerce_sargapay_blockfrost_key']);
        } else if ($_POST['woocommerce_sargapay_testmode'] == 1) {
            if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_sargapay_blockfrost_test_key'])) {
                WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for Preview.', 'sargapay'));
                $errors = 1;
            }
            if (empty($_POST['woocommerce_sargapay_blockfrost_test_key'])) {
                WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for Preview to validate transactions.', 'sargapay'));
                $errors = 1;
            }
            $errors = $this->check_API_KEY($_POST['woocommerce_sargapay_testmode'], $_POST['woocommerce_sargapay_blockfrost_test_key']);
        }
        return $errors === 0;
    }

    public function check_API_KEY($testmode, $apikey)
    {
        if ($testmode == 1) {
            $url = "https://cardano-preview.blockfrost.io/api/v0/";
            $network = "TESTNET";
        } else {
            $url = "https://cardano-mainnet.blockfrost.io/api/v0/";
            $network = "MAINNET";
        }

        $headers = array('project_id' => $apikey, 'Content-Type' => 'application/json',);

        $args = array(
            'body'        => array(),
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => $headers,
            'cookies'     => array(),
        );

        $response  = wp_remote_get($url, $args);
        $body      = wp_remote_retrieve_body($response);
        $body_json = json_decode($body);

        if (!isset($body_json->version)) {
            // API CALL CONECTION FAILED
            WC_Admin_Settings::add_error(__('Error: Connection Failed', 'sargapay'));
            return 1;
        }
        if (isset($body_json->status_code)) {
            // ERROR API CALL
            WC_Admin_Settings::add_error($network . " API KEY Code: " . $body_json->status_code . " Error: " . $body_json->error . " Message: " . $body_json->message);
            return 1;
        }
        return 0;
    }

    private function getCurrency()
    {
        $result = new stdClass();

        if (strtolower(get_woocommerce_currency()) === "sargacardano") {
            $result->currency = "ADA";
            $result->symbol = get_woocommerce_currency_symbol();
            return $result;
        }

        // Get supported currencies coingeeko
        $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/supported_vs_currencies'));
        $supported_currencies = json_decode($request, true);

        // check if the wc currency is supported if is not we remplace it with the plugin options currency
        if (in_array(strtolower(get_woocommerce_currency()), $supported_currencies)) {
            $result->currency = get_woocommerce_currency();
            $result->symbol = get_woocommerce_currency_symbol();
        } else {
            $result->currency = $this->currency;
            switch ($result->currency) {
                case "ADA":
                    $result->symbol = "₳";
                    break;
                case "EUR":
                    $result->symbol = "€";
                    break;
                default:
                    $result->symbol = "$";
                    break;
            }
        }

        return $result;
    }

    public function payment_fields()
    {
        $currencyObj = $this->getCurrency();
        $currency = $currencyObj->currency;
        $symbol = $currencyObj->symbol;
        if ($currency !== "ADA") {
            $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=' . $currency));
            $data = json_decode($request, true);
        }
        if ($currency === "ADA" || count($data['cardano']) == 1) {
            if ($this->testmode) {
        ?>
                <h3 style='text-align:center; background:red; color:white; font-weight:bold;'>
                    <?php echo __("TEST MODE", 'sargapay'); ?>
                </h3>
            <?php
            }
            $instrucciones = $this->description;
            $cryptoMarkupPercent = $this->markup;
            if (!is_numeric($cryptoMarkupPercent)) {
                $cryptoMarkupPercent = 0.0;
            }
            $cryptoMarkup = $cryptoMarkupPercent / 100.0;
            $cryptoPriceRatio = 1.0 + $cryptoMarkup;

            $fiat = $currency === "ADA" ? 1 : $data['cardano'][array_key_first($data['cardano'])];
            global $wp;
            if (isset($wp->query_vars['order-pay'])) {
                $order_id = $wp->query_vars['order-pay'];
                $order = new WC_Order($order_id);
                $fiat_total_order = $order->get_total();
            } else {
                $fiat_total_order = WC()->cart->get_totals()["total"];
            }
            $cryptoTotalPreMarkup = round($fiat_total_order / $fiat, 6, PHP_ROUND_HALF_UP);
            $total_ada = number_format((float)($cryptoTotalPreMarkup * $cryptoPriceRatio), 6, '.', '');
            ?>
            <p><?php echo esc_html($instrucciones); ?></p>
            <div style='text-align:center;'>
                <?php if ($currency !== "ADA") { ?>
                    <p><?php echo __("Currency", 'sargapay') . " = " . esc_html($currency); ?></p>
                    <p><?php echo __("ADA Price", 'sargapay') . " = " . esc_html($symbol) . " " . esc_html($fiat); ?></p>
                <?php } ?>
                <p><?php echo __("ADA Total", 'sargapay') . " = " . esc_html($total_ada) . "*"; ?></p>
            </div>
            <p style='text-align: center; font-size:1rem'>
                <?php echo "* " . __("ADA exchange rate is calculated at the time the order is made", 'sargapay') ?>
            </p>
        <?php
        } else {
        ?>

            <br><?php echo __("Contact the admin to provide you with a payment address.", 'sargapay'); ?>
<?php
        }
    }

    public function payment_scripts()
    {
    }

    public function validate_fields()
    {
        return true;
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);
        // Mark as on-hold (we're awaiting the confirmations)
        $order->update_status('on-hold', __('Awaiting valid payment', 'woocommerce'));

        // GENERATE PAYMENT ADDRESS
        $total_ada = 0;
        $currencyObj = $this->getCurrency();
        $currency = $currencyObj->currency;
        if ($currency !== "ADA") {
            $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=' . $currency));
            $data = json_decode($request, true);
        }
        if ($currency === "ADA" || count($data) == 1) {

            $cryptoMarkupPercent = $this->markup;

            if (!is_numeric($cryptoMarkupPercent)) {
                $cryptoMarkupPercent = 0.0;
            }

            $cryptoMarkup = $cryptoMarkupPercent / 100.0;
            $cryptoPriceRatio = 1.0 + $cryptoMarkup;
            $fiat = $currency === "ADA" ? 1 : $data['cardano'][array_key_first($data['cardano'])];
            $fiat_total_order = WC()->cart->get_totals()["total"];
            $cryptoTotalPreMarkup = round($fiat_total_order / $fiat, 6, PHP_ROUND_HALF_UP);
            $total_ada = number_format((float)($cryptoTotalPreMarkup * $cryptoPriceRatio), 6, '.', '');

            // Get xpub from settings                
            $mpk = $this->mpk;
            // 0=TESTNET 1=MAINNET
            $network = $this->testmode == 1 ? 1 : 0;
            // GET IT AND UPDATE IT
            global $wpdb;
            $table = $wpdb->prefix . 'wc_sargapay_address';

            $get_key =  $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, pay_address FROM {$wpdb->prefix}wc_sargapay_address WHERE testnet=%d AND status_order =%s AND mpk=%s ORDER BY id ASC LIMIT 1",
                    $network,
                    'unused',
                    $mpk
                )
            );

            if ($wpdb->last_error === "" && isset($get_key[0]->pay_address)) {
                $id = $get_key[0]->id;
                // Update data                 
                $dataDB =
                    array(
                        'status_order' => 'on-hold',
                        'last_checked' => 0,
                        'assigned_at' => $order->get_date_created()->getTimestamp(),
                        'order_id' => $order_id,
                        'order_amount' => $total_ada,
                        'ada_price' => floatval($fiat),
                        'currency' => $currency
                    );
                //CHECK THIS ONE         
                $format = array('%s', '%d', '%d', '%d', '%f', '%f', '%s');
                $wpdb->update($table, $dataDB, array('id' => $id), $format);
                //LOG ERROR UPDATE
                if ($wpdb->last_error === "") {
                    // Remove cart
                    $woocommerce->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                } else {
                    sargapay_plugin_log("ERROR UPDATE ADDRESS STATUS::" . $wpdb->last_error);
                }
            }
        }
        $order->update_status('failed', __('Payment error:', 'woocommerce') . $this->get_option('error_message'));
        wc_add_notice($payment_status['message'], 'error');
        // Remove cart
        WC()->cart->empty_cart();
        return array(
            'result'   => 'failure',
            'redirect' => wc_get_checkout_url()
        );
    }

    public function webhook()
    {
    }
}
