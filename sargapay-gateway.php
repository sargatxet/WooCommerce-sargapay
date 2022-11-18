<?php

/*
    SargaPay. Cardano gateway plug-in for Woocommerce. 
    Copyright (C) 2021  Sargatxet Pools

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Sargapay_WC_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {

        $this->id = 'sargapay';
        $this->icon = plugin_dir_url(__FILE__) . '/assets/img/ada_logo.png';
        $this->has_fields = true;
        $this->method_title = 'Sargapay Gateway';
        $this->method_description = __('Allow customers to pay using Cardano ADA', 'sargapay'); //traducir
        $this->supports = array(
            'products'
        );
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->mpk = $this->get_option('mpk');
        $this->currency = $this->get_option('currency');
        $this->blockfrost_key = $this->get_option('blockfrost_key');
        $this->blockfrost_test_key = $this->get_option('blockfrost_test_key');
        $this->confirmations = $this->get_option('confirmations');
        $this->markup = $this->get_option('markup');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
    }
    public function generate_screen_button_html($key, $value)
    {
?>
        <tr valign="top">
            <td colspan="2" class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                <div class="ad-container">
                    <div class="banner-container">
                        <span class="header-subtitle">Delega en el pool de Cardano Sargatxet</span>
                        <div class="banner">
                            <a class="banner-link" href="https://cardano.sargatxet.cloud/" target="_blank"><span class="dashicons dashicons-admin-site-alt3 icono-link"></span> Website</a>
                            <a class="banner-link" href="https://discord.gg/X6Ruku9q42" target="_blank"><img class="discord-logo" src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/discord.png';  ?>" alt="Discord Logo" /></a>
                        </div>
                    </div>
                    <div class="img-container">
                        <img class="banner-logo" src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/banner.png';  ?>" alt="Sargatxet Logo" />
                    </div>
                </div>
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2" class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=sargapay&screen=orders'); ?>" class="button"><?php _e('Orders Paid with this plugin', 'sargapay'); ?></a>
            </td>
        </tr>
        <?php
    }

    /**
     * Plugin options
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'screen_button' => array(
                'id'    => 'screen_button',
                'type'  => 'screen_button',
                'title' => 'Other Settings',
            ),
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
                'default'     => __('Pay using Cardano ADA via our super-cool payment gateway. You have 24 hrs to pay or the order will be cancelled.', 'sargapay'),
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'sargapay'),
                'label'       => __('Enable Test Mode', 'sargapay'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode to use TESTNET.', 'sargapay'),
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
                'description' => __('Confirmations needed to accept a trasaction as valid.', 'sargapay'),
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
            'markup' => array(
                'title'       => __('Markup/Markdown %', 'sargapay'),
                'type'        => 'float',
                'description' => __('This only increases the crypto amount owed, the original fiat value will still be displayed to the customer. 3.8 = 3.8% markup, -10.0 = 10.0% markdown', 'sargapay'),
                'desc_tip'    => false,
            ),
            'blockfrost_key' => array(
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Mainnet', 'sargapay') . '</a>',
                'type'        => 'password',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Mainnet.', 'sargapay'),
                'desc_tip'    => true,
            ),
            'blockfrost_test_key' => array(
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Testnet.', 'sargapay') . '</a>',
                'type'        => 'password',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Testnet.', 'sargapay'),
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
        );
    }

    public function admin_options()
    {
        #sanitize 
        $get_key =  isset($_GET['screen']) ? sanitize_text_field($_GET['screen']) : false;
        if (!$get_key || '' === $get_key) {
            parent::admin_options();
        } else {
            if ('orders' === $get_key) {
                global $hide_save_button;
                $hide_save_button    = true;
        ?>
                <h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=sargapay')); ?>"><?php echo esc_html($this->method_title); ?>
                    </a>
                    <?php echo __('Orders done with Sargapay Gateway', 'sargapay'); ?>
                </h2>
                <?php
                $orders = wc_get_orders(array(
                    'limit' => '-1',
                    'payment_method' => $this->id,
                ));
                if ($orders) { ?> <table class="form-table">
                        <thead>
                            <tr>
                                <?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) :
                                    if ('order-actions' === $column_id) {
                                        continue;
                                    }
                                ?>
                                    <td><strong><?php echo esc_html($column_name); ?></strong></td>
                                <?php endforeach; ?>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($orders as $customer_order) :
                                $order      = wc_get_order($customer_order);
                                $item_count = $order->get_item_count();
                            ?>
                                <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr($order->get_status()); ?> order">
                                    <?php foreach (wc_get_account_orders_columns() as $column_id => $column_name) : ?>
                                        <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr($column_id); ?>" data-title="<?php echo esc_attr($column_name); ?>">
                                            <?php if ('order-number' === $column_id) : ?>
                                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_order_number() . '&action=edit')); ?>">
                                                    <?php echo _x('#', 'hash before order number', 'woocommerce') . $order->get_order_number(); ?>
                                                </a>

                                            <?php elseif ('order-date' === $column_id) : ?>
                                                <time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></time>

                                            <?php elseif ('order-status' === $column_id) : ?>
                                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>

                                            <?php elseif ('order-total' === $column_id) : ?>
                                                <?php
                                                global $wpdb;
                                                $order_id = $order->get_id();
                                                $query_result = $wpdb->get_results(
                                                    $wpdb->prepare(
                                                        "SELECT order_amount FROM {$wpdb->prefix}wc_sargapay_address WHERE order_id=%d",
                                                        $order_id
                                                    )
                                                );
                                                if ($wpdb->last_error === "" && count($query_result) === 0) {
                                                    $ada_amount = "Error";
                                                } else {
                                                    $ada_amount = $query_result[0]->order_amount;
                                                }
                                                /* translators: 1: formatted order total 2: total order items */
                                                printf(_n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce'), $ada_amount, $item_count);
                                                ?>

                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php
                } else {
                ?>
                    <p> <?php echo __('No orders done yet with this gateway', 'sargapay'); ?> </p>
                <?php
                }
            }
        }
    }

    function process_admin_options()
    {

        #sanitize 
        $get_key =  isset($_GET['screen']) ? sanitize_text_field($_GET['screen']) : false;
        if ($get_key || '' !== $get_key) {
        } else {
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
                    $errors = 1;
                }
                if (empty($_POST['woocommerce_sargapay_blockfrost_key'])) {
                    WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for MAINNET to validate transactions.', 'sargapay'));
                    $errors = 1;
                }
                $errors = $this->check_API_KEY(0, $_POST['woocommerce_sargapay_blockfrost_key']);
            } else if ($_POST['woocommerce_sargapay_testmode'] == 1) {
                if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_sargapay_blockfrost_test_key'])) {
                    WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for TESTNET.', 'sargapay'));
                    $errors = 1;
                }
                if (empty($_POST['woocommerce_sargapay_blockfrost_test_key'])) {
                    WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for TESTNET to validate transactions.', 'sargapay'));
                    $errors = 1;
                }
                $errors = $this->check_API_KEY($_POST['woocommerce_sargapay_testmode'], $_POST['woocommerce_sargapay_blockfrost_test_key']);
            }
            return $errors === 0;
        }
        return false;
    }

    // API KEY CHECK
    function check_API_KEY($testmode, $apikey)
    {
        if ($testmode == 1) {
            $url = "https://cardano-testnet.blockfrost.io/api/v0/";
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

    public function payment_fields()
    {
        // Get supported currencies
        $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/supported_vs_currencies'));
        $supported_currencies = json_decode($request, true);
        // check if the wc currency is supported if is not we remplace it with the plugin options currency            
        if (in_array(strtolower(get_woocommerce_currency()), $supported_currencies)) {
            $currency = get_woocommerce_currency();
            $symbol = get_woocommerce_currency_symbol();
        } else {
            $currency = $this->currency;
            $currency === "USD" ? $symbol = "$" : $symbol = "€";
        }
        $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=' . $currency));
        $data = json_decode($request, true);
        if (count($data) == 1) {
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
            $fiat = $data['cardano'][array_key_first($data['cardano'])];
            $fiat_total_order = WC()->cart->get_totals()["total"];
            $cryptoTotalPreMarkup = round($fiat_total_order / $fiat, 6, PHP_ROUND_HALF_UP);
            $total_ada = number_format((float)($cryptoTotalPreMarkup * $cryptoPriceRatio), 6, '.', '');
            ?>
            <p><?php echo esc_html($instrucciones); ?></p>
            <div style='text-align:center;'>
                <p><?php echo __("Currency", 'sargapay') . " = " . esc_html($currency); ?></p>
                <p><?php echo __("ADA Price", 'sargapay') . " = " . esc_html($symbol) . " " . esc_html($fiat); ?></p>
                <p><?php echo __("ADA Total", 'sargapay') . " = " . esc_html($total_ada); ?>*</p>
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
        // Get supported currencies 
        $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/supported_vs_currencies'));
        $supported_currencies = json_decode($request, true);
        // check if the wc currency is supported if is not we remplace it with the plugin options currency            
        if (in_array(strtolower(get_woocommerce_currency()), $supported_currencies)) {
            $currency = get_woocommerce_currency();
        } else {
            $currency = $this->currency;
        }
        $request = wp_remote_retrieve_body(wp_remote_get('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=' . $currency));
        $data = json_decode($request, true);
        if (count($data) == 1) {

            $cryptoMarkupPercent = $this->markup;
            if (!is_numeric($cryptoMarkupPercent)) {
                $cryptoMarkupPercent = 0.0;
            }
            $cryptoMarkup = $cryptoMarkupPercent / 100.0;
            $cryptoPriceRatio = 1.0 + $cryptoMarkup;
            $fiat = $data['cardano'][array_key_first($data['cardano'])];
            $fiat_total_order = WC()->cart->get_totals()["total"];
            $cryptoTotalPreMarkup = round($fiat_total_order / $fiat, 6, PHP_ROUND_HALF_UP);
            $total_ada = number_format((float)($cryptoTotalPreMarkup * $cryptoPriceRatio), 6, '.', '');

            // Get xpub from settings                
            $mpk = $this->mpk;
            // 0=TESTNET 1=MAINNET
            $this->testmode == 1 ? $network = 1 : $network = 0;
            // GET IT AND UPDATE IT
            global $wpdb;
            $table = $wpdb->prefix . 'wc_sargapay_address';
            $get_key =  $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, pay_address FROM {$wpdb->prefix}wc_sargapay_address WHERE testnet=%d AND status_order =%s ORDER BY id ASC LIMIT 1",
                    $network,
                    'unused'
                )
            );
            //LOG ERROR DB
            if ($wpdb->last_error === "" && isset($get_key[0]->pay_address)) {
                $pay_address = $get_key[0]->pay_address;
                $id = $get_key[0]->id;
                // Update data                 
                $dataDB =
                    array(
                        'mpk' => $mpk,
                        'pay_address' => $pay_address,
                        'status_order' => 'on-hold',
                        'last_checked' => 0,
                        'assigned_at' => $order->get_date_created()->getTimestamp(),
                        'order_id' => $order_id,
                        'order_amount' => $total_ada,
                        'ada_price' => floatval($fiat),
                        'currency' => $currency
                    );
                //CHECK THIS ONE         
                $format = array('%s',  '%s', '%s', '%d', '%d', '%d', '%f', '%f', '%s');
                $wpdb->update($table, $dataDB, array('id' => $id), $format);
                //LOG ERROR UPDATE
                if ($wpdb->last_error === "") {
                    // Remove cart
                    $woocommerce->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)

                    );
                }
            }

            $order->update_status('failed', __('Payment error:', 'woocommerce') . $this->get_option('error_message'));
            wc_add_notice($payment_status['message'], 'error');
            // Remove cart
            WC()->cart->empty_cart();
            return array(
                'result'   => 'failure',
                'redirect' => WC()->cart->get_checkout_url()
            );
        } else {
            $order->update_status('failed', __('Payment error:', 'woocommerce') . $this->get_option('error_message'));
            wc_add_notice($payment_status['message'], 'error');
            // Remove cart
            WC()->cart->empty_cart();
            return array(
                'result'   => 'failure',
                'redirect' => WC()->cart->get_checkout_url()
            );
        }
    }

    public function webhook()
    {
    }
}

?>