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
            $table = $wpdb->prefix . "wc_sargapay_address";
            $query_address = $wpdb->get_results("SELECT pay_address, order_amount, testnet FROM $table WHERE order_id=$order_id");
            //LOG ERROR DB
            if ($wpdb->last_error) {
                //LOG Error             
                write_log($wpdb->last_error);
            } else if (count($query_address) === 0) {
                echo "<p>" . __('ERROR PLEASE CONTACT THE ADMIN TO PROCCED WITH THE ORDER', 'sargapay') . "</p>";
                write_log("Emprty Query result in account page order");
            } else {
                if ($query_address[0]->testnet) {
                    $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay'));
                    echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                }
                // Get order amount in ada
                $total_ada = $query_address[0]->order_amount;
                // Get payment address
                $payment_address = $query_address[0]->pay_address;
                $date_created_dt = $order->get_date_created();
                // Get the timestamp in seconds
                $date_created_ts = $date_created_dt->getTimestamp();                
                $text = esc_html(__("Time left to make the payment ", 'sargapay'));
                $qr = GenerateQR::getInstance();
                echo '<p style="text-align: center;">' . $text . '</p>';
                echo "<p id='sarga-timestamp' style='display:none;'>$date_created_ts</p>";
                echo '<p id="sarga-countdown" style="text-align: center;"></p>';
                echo '<p style="text-align: center;"><b>' . esc_html(__('Payment Address', 'sargapay')) . '</b><br><span id="pay_add_p_field_tk_plugin">' . $payment_address . "</span>" .
                    $qr->generate($payment_address) .
                    '</p>';
                echo '<p style="text-align: center;"><b>' . esc_html(__('Total ADA', 'sargapay')) . '</b><br><span id="pay_amount_span_field_tk_plugin">' . $total_ada . '</span></p>';

                #Hotwallets
                echo    "<h4 style='text-align:center; font-weight:bold;'>" . esc_html(__('Pay Now', 'sargapay')) . "</h4>";
                echo    "<div id='loader-container'>
                                <div class='lds-ellipsis'>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <p class='loader-p'>Building Transaction...</p>
                            </div>";
                echo    "<div class='hot_wallets_container'>
                                <button id='hot_wallet_nami' class='wallet-btn'>                                    
                                    Nami
                                </button>
                                <button id='hot_wallet_eternl' class='wallet-btn'>                                    
                                    Eternl
                                </button>
                                <button id='hot_wallet_flint' class='wallet-btn'>                                    
                                    Flint
                                </button>
                            </div>";
            }
        } else if (
            $order->get_status() === "cancelled"
        ) {
            echo esc_html(__("24 hours have passed and your order was canceled, the payment address is no longer valid.", 'sargapay'));
        }
    }
}
