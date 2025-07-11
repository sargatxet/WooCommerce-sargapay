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

if (!defined('WPINC')) {
    die;
}

function sargapay_view_order_cancel_notice($order_id)
{
    //add pending status and show confirmations
    $order = wc_get_order($order_id);
    if ($order->get_payment_method() === "sargapay_cardano") {
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
            if ($wpdb->last_error === "" && count($query_address) === 0) {
?>
                <p><?php echo __('ERROR PLEASE CONTACT THE ADMIN TO PROCEED WITH THE ORDER', 'sargapay'); ?></p>
                <?php
            } else if ($wpdb->last_error === "") {
                if ($query_address[0]->testnet) {
                    $testnet_msg  = __("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay');
                ?>
                    <h3 style='background:red; font-weight:bold; color:white; text-align:center;'><?php echo esc_html($testnet_msg); ?> </h3>
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
                $qr = Sargapay_GenerateQR::getInstance();
                $time_wait = WC()->payment_gateways->payment_gateways()['sargapay_cardano']->time_wait;
                ?> <p style="text-align: center;"><?php echo esc_html($text); ?></p>
                <p id='sarga-timestamp' style='display:none;'><?php echo esc_html($date_created_ts) ?></p>
                <p id='sarga-time-wait' style='display:none;'><?php echo esc_html($time_wait) ?></p>
                <p id="sarga-countdown" style="text-align: center;"></p>
                <p style="text-align: center;"><b><?php echo esc_html(__('Payment Address', 'sargapay')); ?></b><br><span id="pay_add_p_field_tk_plugin"><?php echo esc_html($payment_address); ?></span>
                    <?php
                    $qr->generate($payment_address);
                    ?>
                </p>
                <?php
                $are_light_wallets_enabled = WC()->payment_gateways->payment_gateways()['sargapay_cardano']->lightWallets;
                if ($are_light_wallets_enabled) {
                ?>
                    <!-- Hotwallets -->
                    <h4 style='text-align:center; font-weight:bold;'><?php echo esc_html(__('Pay Now', 'sargapay')); ?></h4>
                    <div id='loader-container'>
                        <div class='lds-ellipsis'>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                        <p class='loader-p'><?php echo esc_html(__('Building Transaction', 'sargapay')) . '...'; ?></p>
                    </div>
                    <div class='hot_wallets_container'>
                        <button id='hot_wallet_nami' class='wallet-btn'>Nami</button>
                        <button id='hot_wallet_eternl' class='wallet-btn'>Eternl</button>
                        <button id='hot_wallet_flint' class='wallet-btn'>Flint</button>
                    </div>
                <?php
                }
                ?>
                <p style="text-align: center;"><b><?php echo esc_html(__('Total ADA', 'sargapay')) ?></b><br><span id="pay_amount_span_field_tk_plugin"><?php echo esc_html($total_ada); ?></span></p>
                <div id='copy_modal' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'><?php echo esc_html(__('Payment Address Copied!', 'sargapay')); ?></p>
                    </div>
                </div>
                <div id='copy_modal_amount' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'><?php echo esc_html(__('Amount Copied!', 'sargapay')) ?></p>
                    </div>
                </div>
                <div style='display:flex; justify-content: space-evenly; margin:15px;'>
                    <button class='button' id='pay_add_button_field_tk_plugin'>
                        <?php echo esc_html(__('Copy Payment Address', 'sargapay')); ?>
                    </button>
                    <button class='button' id='pay_amount_button_field_tk_plugin'>
                        <?php echo esc_html(__('Copy Amount', 'sargapay')); ?>
                    </button>
                </div>
<?php
            }
        } else if (
            $order->get_status() === "cancelled"
        ) {
            $time_wait = WC()->payment_gateways->payment_gateways()['sargapay_cardano']->time_wait;
            esc_html(printf(_nx('%d hour has passed and your order was canceled, the payment address is no longer valid.', '%d hours have passed and your order was canceled, the payment address is no longer valid.', $time_wait, 'Number of Hours', 'sargapay'), $time_wait));
        }
    }
}
