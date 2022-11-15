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

function sargapay_view_order_cancel_notice($order_id)
{
    //add pending status and show confirmations
    $order = wc_get_order($order_id);
    if ($order->get_payment_method() === "sargapay") {
        if (
            $order->get_status() === "on-hold"
        ) {
            global $wpdb;
            $query_address = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT pay_address, order_amount, testnet FROM {$wpdb->prefix}wc_sargapay_address WHERE order_id=%d",
                    $order_id
                )
            );
            //LOG ERROR DB
            if ($wpdb->last_error) {
                //LOG Error             
                write_log($wpdb->last_error);
            } else if (count($query_address) === 0) {
?>
                <p><?php echo __('ERROR PLEASE CONTACT THE ADMIN TO PROCCED WITH THE ORDER', 'sargapay'); ?></p>
                <?php
                write_log("Emprty Query result in account page order");
            } else {
                if ($query_address[0]->testnet) {
                    $testnet_msg  = __("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay');
                ?>
                    <p style='background:red; font-weight:bold; color:white; text-align:center;'><?php echo esc_html($testnet_msg); ?> </p>
                <?php
                }
                // Get order amount in ada
                $total_ada = $query_address[0]->order_amount;
                // Get payment address
                $payment_address = $query_address[0]->pay_address;
                $date_created_dt = $order->get_date_created();
                // Get the timestamp in seconds
                $date_created_ts = $date_created_dt->getTimestamp();
                $text = __("Time left to make the payment ", 'sargapay');
                $qr = GenerateQR::getInstance();
                ?>
                <p style="text-align: center;"><?php echo esc_html($text); ?></p>
                <p id='sarga-timestamp' style='display:none;'><?php echo esc_html($date_created_ts) ?></p>
                <p id="sarga-countdown" style="text-align: center;"></p>
                <p style="text-align: center;"><b><?php echo esc_html(__('Payment Address', 'sargapay')); ?></b><br><span id="pay_add_p_field_tk_plugin"><?php echo esc_html($payment_address); ?></span>
                    <?php
                    $qr->generate($payment_address);
                    ?>
                </p>
                <p style="text-align: center;"><b><?php echo esc_html(__('Total ADA', 'sargapay')) ?></b><br><span id="pay_amount_span_field_tk_plugin"><?php echo esc_html($total_ada); ?></span></p>
                <!-- Hotwallets -->
                <h4 style='text-align:center; font-weight:bold;'><?php echo esc_html(__('Pay Now', 'sargapay')); ?></h4>
                <div id='loader-container'>
                    <div class='lds-ellipsis'>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <p class='loader-p'>Building Transaction...</p>
                </div>
                <div class='hot_wallets_container'>
                    <button id='hot_wallet_nami' class='wallet-btn'>Nami</button>
                    <button id='hot_wallet_eternl' class='wallet-btn'>Eternl</button>
                    <button id='hot_wallet_flint' class='wallet-btn'>Flint</button>
                </div>
<?
            }
        } else if (
            $order->get_status() === "cancelled"
        ) {
            echo esc_html(__("24 hours have passed and your order was canceled, the payment address is no longer valid.", 'sargapay'));
        }
    }
}
