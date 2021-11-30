
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

function save_address()
{
    $addresses  = isset($_POST['addresses']) ? $_POST['addresses'] : false;
    $action_type = isset($_POST['action_type']) ? $_POST['action_type'] : false;
    if (wp_doing_ajax()) {
        if (isset($_POST['action_type'])) {
            $xpub = WC()->payment_gateways->payment_gateways()['sargapay-plugin']->mpk;
            // 0=TESTNET 1=MAINNET
            $testmode = WC()->payment_gateways->payment_gateways()['sargapay-plugin']->testmode == 1 ? 1 : 0;
            $network = $testmode == 1 ? $network = 0 : $network = 1;
            // wpdb call to check address index
            global $wpdb;
            $table = $wpdb->prefix . "wc_sarga_address";
            $last_index_response = $wpdb->get_results("SELECT address_index FROM $table WHERE testnet=$testmode AND mpk='$xpub' ORDER BY id DESC LIMIT 1");
            if ($wpdb->last_error) {
                //LOG Error
                write_log($wpdb->last_error);
            } else {
                if ($last_index_response[0]->address_index == null) {
                    $last_index = 0;
                    $was_null = true;
                } else {
                    $last_index = $last_index_response[0]->address_index;
                    $was_null = false;                    
                }
                if ($action_type == "get_unused") {
                    //Get Unused address from DB 
                    if ($testmode == 1) {
                        $response_query = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'unused' AND testnet = 1 AND mpk = '$xpub'");
                    } else {
                        $response_query = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'unused' AND testnet = 0 AND mpk = '$xpub'");
                    }
                    if($last_index == 0 && $was_null){
                        $last_index = null;
                    }                   
                    wp_send_json(array('unused' => $response_query, 'xpub' => $xpub, 'last_unused' => $last_index, 'network' => $network));
                } else if ($action_type == "get_xpub") {
                    wp_send_json(array('xpub' => $xpub, 'last_unused' => $last_index, 'network' => $network));
                } else if ($action_type == "save_address") {
                    if (!$addresses) {
                        write_log("Empty request in get_xpub ajax call");
                        wp_send_json(__('Error no request sent :(', 'sargapay-plugin'));
                    } else {
                        if (count($addresses) >= 1) {
                            if ($last_index != 0) {
                                $last_index += 1;
                            } else if ($last_index == 0) {
                                $first_address = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE testnet = $testmode AND mpk = '$xpub'");
                                if ($first_address == 1) {
                                    $last_index = 1;                                    
                                }
                            }
                            foreach ($addresses as $address) {
                                $dataDB =
                                    array(
                                        'mpk' => $xpub,
                                        'address_index' => $last_index,
                                        'pay_address' => $address,
                                        'status' => 'unused',
                                        'last_checked' => 0,
                                        'assigned_at' => 0,
                                        'order_id' => 0,
                                        'order_amount' => 0.00,
                                        'ada_price' => 0.00,
                                        'currency' => 0,
                                        'testnet' => $testmode
                                    );
                                $format = array('%s', '%d', '%s', '%s', '%d', '%d', '%d', '%f', '%f', '%s', '%d');
                                $wpdb->insert($table, $dataDB, $format);
                                if ($wpdb->last_error) {
                                    //LOG Error
                                    write_log($wpdb->last_error);
                                } else {
                                    $last_index += 1;
                                }
                            }
                            wp_send_json(__(' Adresses Generated and Saved in Database.', 'sargapay-plugin'));
                        }
                    }
                }
            }
        }
    }
    wp_die();
}
