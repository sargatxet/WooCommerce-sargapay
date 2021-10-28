<?php

class SARGAPAY_WC_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {

        $this->id = 'sargapay-plugin';
        $this->icon = plugin_dir_url(__FILE__) . '/assets/img/ada_logo.png';
        $this->has_fields = true;
        $this->method_title = 'Sargapay Gateway';
        $this->method_description = __('Allow customers to pay using Cardano ADA', 'sargapay-plugin'); //traducir
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

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
    }
    public function generate_screen_button_html($key, $value)
    {
        if (true) {
?>
            <!-- Banner Config Panel TODO: text and check for license -->
            <style>
                .ad-container {
                    display: flex;
                    justify-content: space-between;
                    padding: 25px 0;
                }

                .banner-container {
                    display: flex;
                    flex-direction: column;
                }

                .banner {
                    background-image: linear-gradient(135deg, #3C8CE7 10%, #00EAFF 100%);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: space-around;
                    width: 50vw;
                    height: 10vh;
                    padding: 10px;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
                }

                .img-container {
                    width: 40%;
                    display: flex;
                    justify-content: center;
                }

                .header-subtitle {
                    text-align: left;
                    font-weight: bold;
                }

                .banner-logo {
                    height: 20vh;
                    width: 30vh;
                    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
                }

                .banner-link {
                    text-decoration: none;
                    color: #fff;
                    font-size: 2vw;
                    font-weight: bold;
                }

                .banner-link:after,
                .banner-link:hover,
                .banner-link:active {
                    color: #fff;
                }

                .discord-logo {
                    height: 40px;
                }

                .icono-link {
                    font-size: 2vw;
                    padding-right: 5px;
                }
            </style>
            <tr valign="top">
                <td colspan="2" class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                    <div class="ad-container">
                        <div class="banner-container">
                            <span class="header-subtitle">Delega en el pool de Cardano Sargatxet</span>
                            <div class="banner">
                                <a class="banner-link" href="https://cardano.sargatxet.cloud/" target="_blank"><span class="dashicons dashicons-admin-site-alt3 icono-link"></span> Website</a>
                                <a class="banner-link" href="https://discord.gg/X6Ruku9q42" target="_blank"><img class="discord-logo" src="<?php echo plugin_dir_url(__FILE__) . '/assets/img/discord.png';  ?>" alt="Discord Logo" /></a>
                            </div>
                        </div>
                        <div class="img-container">
                            <img class="banner-logo" src="<?php echo plugin_dir_url(__FILE__) . '/assets/img/banner.png';  ?>" alt="Sargatxet Logo" />
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
        <tr valign="top">
            <td colspan="2" class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=sargapay-plugin&screen=orders'); ?>" class="button"><?php _e('Orders Paid with this plugin', 'sargapay-plugin'); ?></a>
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
                'title'       => __('Enable/Disable', 'sargapay-plugin'),
                'label'       => __('Enable Sargapay Gateway', 'sargapay-plugin'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => __('no', 'sargapay-plugin'),
            ),
            'title' => array(
                'title'       => __('Title', 'sargapay-plugin'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'sargapay-plugin'),
                'default'     => __('Pay with Cardano ADA', 'sargapay-plugin'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'sargapay-plugin'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'sargapay-plugin'),
                'default'     => __('Pay using Cardano ADA via our super-cool payment gateway. You have 24 hrs to pay or the order will be cancelled.', 'sargapay-plugin'),
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'sargapay-plugin'),
                'label'       => __('Enable Test Mode', 'sargapay-plugin'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode to use TESTNET.', 'sargapay-plugin'),
                'default'     => __('yes', 'sargapay-plugin'),
                'desc_tip'    => true,
            ),
            'mpk' => array(
                'title'       => __('Public Adress Key for Cardano', 'sargapay-plugin'),
                'type'        => 'text',
                'description' => __('Place the Public Adress Key to generate Payment Addresses.', 'sargapay-plugin'),
            ),
            'confirmations' => array(
                'title'       => __('Confirmations', 'sargapay-plugin'),
                'type'        => 'select',
                'description' => __('Confirmations needed to accept a trasaction as valid.', 'sargapay-plugin'),
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
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Mainnet', 'sargapay-plugin') . '</a>',
                'type'        => 'text',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Mainnet.', 'sargapay-plugin'),
                'desc_tip'    => true,
            ),
            'blockfrost_test_key' => array(
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Testnet.', 'sargapay-plugin') . '</a>',
                'type'        => 'text',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Testnet.', 'sargapay-plugin'),
                'desc_tip'    => true,
            ),
            'currency' => array(
                'title'       => __('Currency', 'sargapay-plugin'),
                'type'        => 'select',
                'description' => __('Currency used in case default currency is not supported.', 'sargapay-plugin'),
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
        if (!isset($_GET['screen']) || '' === $_GET['screen']) {
            parent::admin_options();
        } else {
            if ('orders' === $_GET['screen']) {
                echo '<h2><a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=sargapay-plugin') . '">' . $this->method_title . '</a> > ' . __('Orders done with Sargapay Gateway', 'sargapay-plugin') . '</h2>';
                $hide_save_button = true; // Remove the submit button need to be fixed.
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
                                                $table = $wpdb->prefix . 'wc_sarga_address';
                                                $order_id = $order->get_id();
                                                $query_result = $wpdb->get_results("SELECT order_amount FROM $table WHERE order_id=$order_id");
                                                if ($wpdb->last_error) {
                                                    //LOG Error
                                                    write_log($wpdb->last_error);
                                                } else if (count($query_result) === 0) {
                                                    $ada_amount = "Error";
                                                    write_log("Error ADA AMOUNT is empty for order #$order_id");
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
<?php               } else {
                    echo '<p>' . __('No orders done yet with this gateway', 'sargapay-plugin') . '</p>';
                }
            }
        }
    }

    function process_admin_options()
    {
        if (isset($_GET['screen']) && '' !== $_GET['screen']) {
        } else {
            parent::process_admin_options();
            $errors = 0;
            if (empty($_POST['woocommerce_sargapay-plugin_mpk'])) {
                WC_Admin_Settings::add_error(__('Error: You require a Master Public Key to generate  payment addresses.', 'sargapay-plugin'));
                $errors = 1;
            }
            if (!preg_match("/^[A-Za-z0-9_]+$/", $_POST['woocommerce_sargapay-plugin_mpk'])) {
                WC_Admin_Settings::add_error(__('Error: Invalid Character in Publick Key.', 'sargapay-plugin'));
                $errors = 1;
            }
            if (empty($_POST['woocommerce_sargapay-plugin_testmode'])) {
                if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_sargapay-plugin_blockfrost_key'])) {
                    WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for MAINNET.', 'sargapay-plugin'));
                    $errors = 1;
                }
                if (empty($_POST['woocommerce_sargapay-plugin_blockfrost_key'])) {
                    WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for MAINNET to validate transactions.', 'sargapay-plugin'));
                    $errors = 1;
                }
                $errors = $this->check_API_KEY(0, $_POST['woocommerce_sargapay-plugin_blockfrost_key']);
            } else if ($_POST['woocommerce_sargapay-plugin_testmode'] == 1) {
                if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_sargapay-plugin_blockfrost_test_key'])) {
                    WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for TESTNET.', 'sargapay-plugin'));
                    $errors = 1;
                }
                if (empty($_POST['woocommerce_sargapay-plugin_blockfrost_test_key'])) {
                    WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for TESTNET to validate transactions.', 'sargapay-plugin'));
                    $errors = 1;
                }
                $errors = $this->check_API_KEY($_POST['woocommerce_sargapay-plugin_testmode'], $_POST['woocommerce_sargapay-plugin_blockfrost_test_key']);
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
        // API KEY TEST
        $apicall = curl_init();
        curl_setopt($apicall, CURLOPT_URL, $url);
        curl_setopt($apicall, CURLOPT_HTTPHEADER, array(
            'project_id: ' . $apikey,
        ));
        curl_setopt($apicall, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($apicall, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $result = json_decode(curl_exec($apicall), true);
        if (!$result) {
            // API CALL CONECTION FAILED
            WC_Admin_Settings::add_error(__('Error: Connection Failed', 'sargapay-plugin'));
            return 1;
        }
        curl_close($apicall);
        if (isset($result["status_code"])) {
            // ERROR API CALL
            WC_Admin_Settings::add_error($network . " API KEY Code: " . $result["status_code"] . " Error: " . $result["error"] . " Message: " . $result["message"]);
            return 1;
        }
        return 0;
    }

    public function payment_fields()
    {
        // Get supported currencies 
        $supported_currencies = json_decode(file_get_contents('https://api.coingecko.com/api/v3/simple/supported_vs_currencies'), true);
        // check if the wc currency is supported if is not we remplace it with the plugin options currency            
        if (in_array(strtolower(get_woocommerce_currency()), $supported_currencies)) {
            $currency = get_woocommerce_currency();
            $symbol = get_woocommerce_currency_symbol();
        } else {
            $currency = $this->currency;
            $currency === "USD" ? $symbol = "$" : $symbol = "€";
        }
        $data = json_decode(file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=' . $currency), true);
        if (count($data) == 1) {
            if ($this->testmode) {
                echo "<h3 style='text-align:center; background:red; color:white; font-weight:bold;'>" . __("TEST MODE", 'sargapay-plugin') . "</h3>";
            }
            $instrucciones = $this->description;
            $fiat = $data['cardano'][array_key_first($data['cardano'])];
            $total_ada = round(WC()->cart->get_cart_contents_total() / $fiat, 6);
            echo "<p>$instrucciones</p>";
            echo "<div style='text-align:center;'>";
            echo "<p>" . __("Currency", 'sargapay-plugin') . " = " . $currency . "</p>";
            echo "<p>" . __("ADA Price", 'sargapay-plugin') . " = $symbol $fiat</p>";
            echo "<p>" . __("ADA Total", 'sargapay-plugin') . " = " . $total_ada . "*</p>";
            echo "</div>";
            echo "<p style='text-align: center; font-size:1rem'>* " . __("ADA exchange rate is calculated at the time the order is made", 'sargapay-plugin') . "</p>";
        } else {
            echo "<br>" . __("Contact the admin to provide you with a payment address.", 'sargapay-plugin');
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

        // GENERATE PAYMENT ADRESS
        $total_ada = 0;
        // Get supported currencies 
        $supported_currencies = json_decode(file_get_contents('https://api.coingecko.com/api/v3/simple/supported_vs_currencies'), true);
        // check if the wc currency is supported if is not we remplace it with the plugin options currency            
        if (in_array(strtolower(get_woocommerce_currency()), $supported_currencies)) {
            $currency = get_woocommerce_currency();
        } else {
            $currency = $this->currency;
        }
        $data = json_decode(file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=' . $currency), true);
        if (count($data) == 1) {
            $fiat = $data['cardano'][array_key_first($data['cardano'])];
            $total_ada = round(WC()->cart->get_cart_contents_total() / $fiat, 6);
            // Get xpub from settings                
            $mpk = $this->mpk;
            // 0=TESTNET 1=MAINNET
            $this->testmode == 1 ? $network = 1 : $network = 0;
            // GET IT AND UPDATE IT
            global $wpdb;
            $table = $wpdb->prefix . 'wc_sarga_address';
            $get_key = $wpdb->get_results("SELECT id, pay_address FROM $table WHERE testnet=$network AND status = 'unused' ORDER BY id ASC LIMIT 1");
            //LOG ERROR DB
            if ($wpdb->last_error) {
                write_log($wpdb->last_error);
            } else {
                $pay_address = $get_key[0]->pay_address;
                $id = $get_key[0]->id;
                // Update data                 
                $dataDB =
                    array(
                        'mpk' => $mpk,
                        'pay_address' => $pay_address,
                        'status' => 'on-hold',
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
                if ($wpdb->last_error) {
                    //LOG Error             
                    write_log($wpdb->last_error);
                } else {
                    // Remove cart
                    $woocommerce->cart->empty_cart();

                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)

                    );
                }
            }
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