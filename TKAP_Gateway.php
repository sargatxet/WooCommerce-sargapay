<?php

class TKAP_WC_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {

        $this->id = 'tk-ada-pay-plugin';
        $this->icon = plugin_dir_url(__FILE__) . '/assets/img/ada_logo.png';
        $this->has_fields = true;
        $this->method_title = 'Sargatxet ADA Gateway';
        $this->method_description = __('Allow customers to pay using Cardano ADA', 'tk-ada-pay-plugin'); //traducir
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
                    align-items: center;
                    height: 10vh;
                    background: #ffd;
                    justify-content: space-around;
                    padding: 10px;
                    margin-bottom: 25px;
                    background-image: linear-gradient(135deg, #3C8CE7 10%, #00EAFF 100%);
                    color: white;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
                }

                .img-contianer {
                    width: 30%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: flex-end;
                }

                .img-subtitle {
                    text-align: center;
                    font-weight: bold;
                }

                .banner-logo {
                    height: auto;
                    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
                }
            </style>
            <tr valign="top">
                <td colspan="2" class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                    <div class="ad-container">
                        <div>Version Pro Sin anuncios</div>
                        <div>PRECIO Y LINK</div>
                        <div class="img-contianer"> <img class="banner-logo" src="<?php echo plugin_dir_url(__FILE__) . '/assets/img/banner.jpg';  ?>" alt="Sargatxet Logo" /> <span class="img-subtitle">Delega en el pool de Cardano Sargatxet</span></div>
                    </div>
                </td>
            </tr>
        <?php } ?>
        <tr valign="top">
            <td colspan="2" class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=tk-ada-pay-plugin&screen=orders'); ?>" class="button"><?php _e('Orders Paid with this plugin', 'tk-ada-pay-plugin'); ?></a>
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
                'title'       => __('Enable/Disable', 'tk-ada-pay-plugin'),
                'label'       => __('Enable TrakaDev ADA Gateway', 'tk-ada-pay-plugin'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => __('no', 'tk-ada-pay-plugin'),
            ),
            'title' => array(
                'title'       => __('Title', 'tk-ada-pay-plugin'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'tk-ada-pay-plugin'),
                'default'     => __('Pay with Cardano ADA', 'tk-ada-pay-plugin'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'tk-ada-pay-plugin'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'tk-ada-pay-plugin'),
                'default'     => __('Pay using Cardano ADA via our super-cool payment gateway. You have 24 hrs to pay or the order will be cancelled.', 'tk-ada-pay-plugin'),
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'tk-ada-pay-plugin'),
                'label'       => __('Enable Test Mode', 'tk-ada-pay-plugin'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode to use TESTNET.', 'tk-ada-pay-plugin'),
                'default'     => __('yes', 'tk-ada-pay-plugin'),
                'desc_tip'    => true,
            ),
            'mpk' => array(
                'title'       => __('Public Adress Key for Cardano', 'tk-ada-pay-plugin'),
                'type'        => 'text',
                'description' => __('Place the Public Adress Key to generate Payment Addresses.', 'tk-ada-pay-plugin'),
            ),
            'confirmations' => array(
                'title'       => __('Confirmations', 'tk-ada-pay-plugin'),
                'type'        => 'select',
                'description' => __('Confirmations needed to accept a trasaction as valid.', 'tk-ada-pay-plugin'),
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
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Mainnet', 'tk-ada-pay-plugin') . '</a>',
                'type'        => 'text',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Mainnet.', 'tk-ada-pay-plugin'),
                'desc_tip'    => true,
            ),
            'blockfrost_test_key' => array(
                'title'       => '<a href="https://blockfrost.io/">' . __('Blockfrost API Key for Testnet.', 'tk-ada-pay-plugin') . '</a>',
                'type'        => 'text',
                'description' => __('Place the API KEY to use BlockFrost to verify transactions on Testnet.', 'tk-ada-pay-plugin'),
                'desc_tip'    => true,
            ),
            'currency' => array(
                'title'       => __('Currency', 'tk-ada-pay-plugin'),
                'type'        => 'select',
                'description' => __('Currency used in case default currency is not supported.', 'tk-ada-pay-plugin'),
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
                echo '<h2><a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=tk-ada-pay-plugin') . '">' . $this->method_title . '</a> > ' . __('Orders done with TrakaDev ADA Gateway', 'tk-ada-pay-plugin') . '</h2>';
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
                                                <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                                    <?php echo _x('#', 'hash before order number', 'woocommerce') . $order->get_order_number(); ?>
                                                </a>

                                            <?php elseif ('order-date' === $column_id) : ?>
                                                <time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></time>

                                            <?php elseif ('order-status' === $column_id) : ?>
                                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>

                                            <?php elseif ('order-total' === $column_id) : ?>
                                                <?php
                                                global $wpdb;
                                                $table = $wpdb->prefix . 'wc_tkap_address';
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
                    echo '<p>' . __('No orders done yet with this gateway', 'tk-ada-pay-plugin') . '</p>';
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
            if (empty($_POST['woocommerce_tk-ada-pay-plugin_mpk'])) {
                WC_Admin_Settings::add_error(__('Error: You require a Master Public Key to generate  payment addresses.', 'tk-ada-pay-plugin'));
                $errors = 1;
            }
            if (!preg_match("/^[A-Za-z0-9_]+$/", $_POST['woocommerce_tk-ada-pay-plugin_mpk'])) {
                WC_Admin_Settings::add_error(__('Error: Invalid Character in Publick Key.', 'tk-ada-pay-plugin'));
                $errors = 1;
            }
            if (empty($_POST['woocommerce_tk-ada-pay-plugin_testmode'])) {
                if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_tk-ada-pay-plugin_blockfrost_key'])) {
                    WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for MAINNET.', 'tk-ada-pay-plugin'));
                    $errors = 1;
                }
                if (empty($_POST['woocommerce_tk-ada-pay-plugin_blockfrost_key'])) {
                    WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for MAINNET to validate transactions.', 'tk-ada-pay-plugin'));
                    $errors = 1;
                }
            } else if ($_POST['woocommerce_tk-ada-pay-plugin_testmode'] == 1) {
                if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['woocommerce_tk-ada-pay-plugin_blockfrost_test_key'])) {
                    WC_Admin_Settings::add_error(__('Error: Invalid Character in BLOCKFROST API KEY for TESTNET.', 'tk-ada-pay-plugin'));
                    $errors = 1;
                }
                if (empty($_POST['woocommerce_tk-ada-pay-plugin_blockfrost_test_key'])) {
                    WC_Admin_Settings::add_error(__('Error: You need a BLOCKFROST API KEY for TESTNET to validate transactions.', 'tk-ada-pay-plugin'));
                    $errors = 1;
                }
            }
            return $errors === 0;
        }
        return false;
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
                echo "<h3 style='text-align:center; background:red; color:white; font-weight:bold;'>" . __("TEST MODE", 'tk-ada-pay-plugin') . "</h3>";
            }
            $instrucciones = $this->description;
            $fiat = $data['cardano'][array_key_first($data['cardano'])];
            $total_ada = round(WC()->cart->get_cart_contents_total() / $fiat, 6);
            echo "<p>$instrucciones</p>";
            echo "<div style='text-align:center;'>";
            echo "<p>" . __("Currency", 'tk-ada-pay-plugin') . " = " . $currency . "</p>";
            echo "<p>" . __("ADA Price", 'tk-ada-pay-plugin') . " = $symbol $fiat</p>";
            echo "<p>" . __("ADA Total", 'tk-ada-pay-plugin') . " = " . $total_ada . "*</p>";
            echo "</div>";
            echo "<p style='text-align: center; font-size:1rem'>* " . __("ADA exchange rate is calculated at the time the order is made", 'tk-ada-pay-plugin') . "</p>";
        } else {
            echo "<br>" . __("Contact the admin to provide you with a payment address.", 'tk-ada-pay-plugin');
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
            $table = $wpdb->prefix . 'wc_tkap_address';
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