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

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/** 
 * Add QR and Payment address in Thank You Page    
 * Add Warning header if testmode is on
 **/

function sargapay_thank_you_text($thank_you_title, $order)
{
    if (isset($order)) {
        if ($order->get_payment_method() === "sargapay_cardano") {
            $time_wait = WC()->payment_gateways->payment_gateways()['sargapay_cardano']->time_wait;
            $order_id = $order->get_id();
            global $wpdb;
            $query_address = $wpdb->get_results($wpdb->prepare(
                "SELECT pay_address, order_amount, testnet FROM {$wpdb->prefix}wc_sargapay_address WHERE order_id=%d",
                $order_id
            ));
            //ERROR DB
            if ($wpdb->last_error === "" && count($query_address) === 0) {
?>
                <p><?php echo esc_html(__('ERROR PLEASE CONTACT ADMIN TO PROCEED WITH THE ORDER', 'sargapay')); ?></p>
                <?php
                return $thank_you_title;
            } else if ($wpdb->last_error === "") {
                if ($query_address[0]->testnet) {
                ?>
                    <p style='background:red; font-weight:bold; color:white; text-align:center;'>
                        <?php echo esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay')); ?>
                    </p>
                <?php
                }
                // Get order amount in ada
                $total_ada = $query_address[0]->order_amount;
                // Get payment address
                $payment_address = $query_address[0]->pay_address;
                $qr = Sargapay_GenerateQR::getInstance();
                // Qr Button
                ?>
                <div id='copy_modal' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'><?php echo esc_html(__('Payment Address Copied!', 'sargapay')); ?></p>
                    </div>
                </div>
                <div style='text-align:center; font-weight:bold;'>
                    <h4><?php echo esc_html(__('Payment Address', 'sargapay')); ?></h4>
                    <p id='pay_add_p_field_tk_plugin' style='width:100%; overflow-wrap:anywhere;'>
                        <?php echo esc_html($payment_address); ?>
                    </p>
                    <?php $qr->generate($payment_address); ?>
                </div>
                <?php
                $are_light_wallets_enabled = WC()->payment_gateways->payment_gateways()['sargapay_cardano']->lightWallets;
                if ($are_light_wallets_enabled) {
                ?>
                    <!-- # Hot Wallets Header -->
                    <h4 style='text-align:center; font-weight:bold;'><?php echo esc_html(__('Pay Now', 'sargapay')) ?></h4>
                    <!-- # Loader -->
                    <div id='loader-container'>
                        <div class='lds-ellipsis'>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                        <p class='loader-p'><?php echo esc_html(__('Building Transaction', 'sargapay')) . "..."; ?></p>
                    </div>
                    <!-- # Wallets Buttons -->
                    <div class='hot_wallets_container'>
                        <button id='hot_wallet_nami' class='wallet-btn'>Nami</button>
                        <button id='hot_wallet_eternl' class='wallet-btn'>Eternl</button>
                        <button id='hot_wallet_flint' class='wallet-btn'>Flint</button>
                    </div>
                <?php  } ?>
                <!-- Amount Button -->
                <div id='copy_modal_amount' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'><?php echo esc_html(__('Amount Copied!', 'sargapay')) ?></p>
                    </div>
                </div>

                <p style="text-align: center;">
                    <b><?php echo esc_html(__('ADA Total', 'sargapay')) ?></b><br>
                    <span id="pay_amount_span_field_tk_plugin"><?php echo esc_html($total_ada) ?></span>
                </p>
                <div style='display:flex; justify-content: space-evenly; margin:15px;'>
                    <button class='button' id='pay_add_button_field_tk_plugin'>
                        <?php echo esc_html(__('Copy Payment Address', 'sargapay')); ?>
                    </button>
                    <button class='button' id='pay_amount_button_field_tk_plugin'>
                        <?php echo esc_html(__('Copy Amount', 'sargapay')); ?>
                    </button>
                </div>
                <div style="font-weight:bold; text-align:center; color:white; background:black;">
                    <p>
                        <?php echo sprintf(_nx('Remember that you have %d hour to pay for your order before it\'s automatically canceled.', 'Remember that you have %d hours to pay for your order before it\'s automatically canceled.', $time_wait, 'Number of Hours', 'sargapay'), $time_wait); ?>
                    </p>
                </div>
            <?php
                // SEND EMAIL  
                // Create QR PNG FILE
                $url_img = $qr->QR_URL($payment_address);
                // Email config
                $email = $order->get_billing_email();
                $subject = __("Payment Instructions ", 'sargapay') . get_bloginfo('name');
                $file_name = $payment_address . ".png";
                $testnet_bool = $query_address[0]->testnet;
                // Email Sent       
                require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-send-email.php';
                sargapay_send_email_woocommerce_style($email, $subject, $testnet_bool, $total_ada, $payment_address, $url_img, $file_name);
                return $thank_you_title;
            } else {
            ?>
                <p><?php echo esc_html(__('ERROR PLEASE CONTACT ADMIN TO PROCEED WITH THE ORDER', 'sargapay')); ?></p>
<?php
                return $thank_you_title;
            }
        }
    }
    return $thank_you_title;
}
