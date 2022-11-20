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

function sargapay_save_address()
{
    $addresses  = isset($_POST['addresses']) ? sargapay_recursive_sanitize_text_field($_POST['addresses']) : false;
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : false;
    if (wp_doing_ajax()) {
        if (isset($action_type)) {
            $xpub = WC()->payment_gateways->payment_gateways()['sargapay']->mpk;
            // 0=TESTNET 1=MAINNET
            $testmode = WC()->payment_gateways->payment_gateways()['sargapay']->testmode == 1 ? 1 : 0;
            $network = $testmode == 1 ? $network = 0 : $network = 1;
            // wpdb call to check address index
            global $wpdb;
            $table = $wpdb->prefix . "wc_sargapay_address";
            $last_index_response = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT address_index FROM {$wpdb->prefix}wc_sargapay_address WHERE testnet=%d AND mpk=%s ORDER BY id DESC LIMIT 1",
                    $testmode,
                    $xpub
                )
            );
            if ($wpdb->last_error === "") {
                if ($last_index_response[0]->address_index == null) {
                    $last_index = 0;
                    $was_null = true;
                } else {
                    $last_index = $last_index_response[0]->address_index;
                    $was_null = false;
                }
                if ($action_type == "get_unused") {
                    //Get Unused address from DB 
                    $esc_xpub = esc_sql($xpub);
                    if ($testmode == 1) {
                        $response_query = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wc_sargapay_address WHERE status_order = 'unused' AND testnet = 1 AND mpk = '$esc_xpub'");
                        $response_query = count($response_query);
                    } else {
                        $response_query = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wc_sargapay_address WHERE status_order = 'unused' AND testnet = 0 AND mpk = '$esc_xpub'");                        
                        $response_query = count($response_query);
                    }
                    if ($last_index == 0 && $was_null) {
                        $last_index = null;
                    }
                    wp_send_json(array('unused' => $response_query, 'xpub' => $xpub, 'last_unused' => $last_index, 'network' => $network));
                } else if ($action_type == "get_xpub") {
                    wp_send_json(array('xpub' => $xpub, 'last_unused' => $last_index, 'network' => $network));
                } else if ($action_type == "save_address") {
                    if (!$addresses) {
                        wp_send_json(__('Error no request sent :(', 'sargapay'));
                    } else {
                        if (count($addresses) >= 1) {
                            if ($last_index != 0) {
                                $last_index += 1;
                            } else if ($last_index == 0) {
                                $esc_xpub = esc_sql($xpub);
                                $first_address = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_sargapay_address WHERE testnet = '$testmode' AND mpk = '$esc_xpub'");
                                sargapay_plugin_log("SARGAPAY::". $testmode);
                                sargapay_plugin_log("SARGAPAY2::". $xpub);
                                sargapay_plugin_log("SARGAPAY3::". var_dump($first_address));
                                $first_address = count($first_address);
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
                                        'status_order' => 'unused',
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
                                if ($wpdb->last_error === "") {
                                    $last_index += 1;
                                }
                            }
                            wp_send_json(__(' Addresses Generated and Saved in Database.', 'sargapay'));
                        }
                    }
                }
            }
        }
    }
    wp_die();
}

function sargapay_recursive_sanitize_text_field($array)
{
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $value = sargapay_recursive_sanitize_text_field($value);
        } else {
            $value = sanitize_text_field($value);
        }
    }
    return $array;
}
