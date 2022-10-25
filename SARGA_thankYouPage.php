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

/** 
 * Add QR and Payment address in Thank You Page    
 * Add Warning header if testmode is on
 **/

function plugin_thank_you_text($thank_you_title, $order)
{
    if (isset($order)) {
        if ($order->get_payment_method() === "sargapay-plugin") {
            $message = '<div style="font-weight:bold; text-align:center; color:white; background:black;">' . esc_html(__('Remember that you have 24 hours to pay for your order before it\'s automatically canceled.', 'sargapay-plugin')) . '</div>';
            $order_id = $order->get_id();
            global $wpdb;
            $table = $wpdb->prefix . "wc_sarga_address";
            $query_address = $wpdb->get_results("SELECT pay_address, order_amount, testnet FROM $table WHERE order_id=$order_id");
            //ERROR DB
            if ($wpdb->last_error) {
                //LOG Error             
                write_log($wpdb->last_error);
            } else if (count($query_address) === 0) {
                $message = "<p>" . esc_html(__('ERROR PLEASE CONTACT ADMIN TO PROCCED WITH THE ORDER', 'sargapay-plugin')) . "</p>";
                write_log("ERROR DB query empty in Thank You Page ");
                return $thank_you_title . "<br>" . $message . '<br><br>';
            } else {
                if ($query_address[0]->testnet) {
                    $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay-plugin'));
                    echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                }
                // Get order amount in ada
                $total_ada = $query_address[0]->order_amount;
                // Get payment address
                $payment_address = $query_address[0]->pay_address;
                $qr = GenerateQR::getInstance();
                // Qr Button
                echo    "<div id='copy_modal' class='modal_tk_plugin'>
                                <div class='modal_tk_plugin_content'>
                                    <span class='close_tk_plugin'>&times;</span>
                                    <p style='text-align:center;'>" . esc_html(__('Payment Address Copied!', 'sargapay-plugin')) . "</p>
                                </div>
                            </div>";

                echo    "<div style='text-align:center; font-weight:bold;'>
                                <h4>" . esc_html(__('Payment Address', 'sargapay-plugin')) . "</h4>
                                <p id='pay_add_p_field_tk_plugin' style='width:100%; overflow-wrap:anywhere;'>" . esc_html($payment_address) . "</p>"
                    . $qr->generate($payment_address) .
                    '</div>';

                # Hot Wallets    
                #Header
                echo    "<h4 style='text-align:center; font-weight:bold;'>" . esc_html(__('Pay Now', 'sargapay-plugin')) . "</h4>";
                # Loader
                echo    "<div id='loader-container'>
                                <div class='lds-ellipsis'>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                                <p class='loader-p'>Building Transaction...</p>
                            </div>";
                # Wallets Buttons
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

                // Amount Button     
                echo    "<div id='copy_modal_amount' class='modal_tk_plugin'>
                                <div class='modal_tk_plugin_content'>
                                    <span class='close_tk_plugin'>&times;</span>
                                    <p style='text-align:center;'>" . esc_html(__('Amount Copied!', 'sargapay-plugin')) . "</p>
                                </div>
                            </div>";

                echo    '<p style="text-align: center;"><b>' . esc_html(__('ADA Total', 'sargapay-plugin')) . '</b><br><span id="pay_amount_span_field_tk_plugin">' . esc_html($total_ada) . '</span></p>' .
                    "<div style='display:flex; justify-content: space-evenly; margin:15px;'>
                                <button class='button' id='pay_add_button_field_tk_plugin'>" . esc_html(__('Copy Payment Address', 'sargapay-plugin')) . "</button><button class='button' id='pay_amount_button_field_tk_plugin'>" . esc_html(__('Copy Amount', 'sargapay-plugin')) . "</button>
                            </div>";

                // SEND EMAIL  
                // Create QR PNG FILE
                $url_img = $qr->QR_URL($payment_address);
                // Email config
                $email = $order->get_billing_email();
                $subject = __("Payment Instructions ", 'sargapay-plugin') . get_bloginfo('name');
                $file_name = $payment_address . ".png";
                $testnet_bool = $query_address[0]->testnet;
                // Email Sent                   
                send_email_woocommerce_style($email, $subject, $testnet_bool, $total_ada, $payment_address, $url_img, $file_name);
                return $thank_you_title . "<br>" . $message . '<br><br>';
            }
        }
    }
    return $thank_you_title;
}
