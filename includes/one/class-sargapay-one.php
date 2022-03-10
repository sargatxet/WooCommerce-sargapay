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

use Brick\Math\BigDecimal;

#require_once(plugin_dir_path(__DIR__) . "class-sargapay-dbs.php");

class Sargapay_ONE extends Sargapay_API
{

    private static $address_table, $last_tx_table;

    public function __construct()
    {
        new Sargapay_DBs;
        $tables = Sargapay_DBs::get_sargapay_one_tables();
        SELF::$address_table = $tables->address_table;
        SELF::$last_tx_table = $tables->last_tx_table;
    }

    static function last_block()
    {
        $result      = new stdClass;
        $URL_TESTNET = 'https://api.s0.b.hmny.io';
        $URL_MAINNET = 'https://api.harmony.one';
        $parameters  =  '{
        "jsonrpc": "2.0",
        "method": "hmyv2_blockNumber",
        "params": [],
        "id": 1
        }';
        $header = array('Content-Type:application/json');
        $response_testnet = SELF::API_CALL($URL_TESTNET, $parameters, $header, true);
        $response_mainnet = SELF::API_CALL($URL_MAINNET, $parameters, $header, true);
        if (isset($response_testnet->error) || $response_testnet == null) {
            # LOG ERROR
            write_log(isset($response_testnet->error) ?
                $response_testnet->error :
                "API_CALL FAILED TESTNET LAST BLOCK <br>");
            $result->testnet = -1;
        } else {
            $result->testnet = $response_testnet->result;
        }
        if (isset($response_mainnet->error) || $response_mainnet == null) {
            write_log(isset($response_mainnet->error) ?
                $response_mainnet->error :
                "API_CALL FAILED MAINET LAST BLOCK <br>");
            $result->mainnet = -1;
        } else {
            $result->mainnet = $response_mainnet->result;
        }

        return $result;
    }

    static function get_all_txs($network, $address)
    {
        # API CALL get last 1000 transactions
        $URL = $network == 1 ? 'https://api.s0.b.hmny.io' : 'https://api.harmony.one';
        $header = array('Content-Type:application/json');
        $parameters =  '{
        "jsonrpc": "2.0",
        "method": "hmyv2_getTransactionsHistory",
        "params": [{
            "address": "' . $address . '",
            "pageIndex": 0,
            "pageSize": 1000,
            "fullTx": true,
            "txType": "RECEIVED",
            "order": "DESC"
        }],
        "id": 1
        }';
        $txs = SELF::API_CALL($URL, $parameters, $header, true);
        if (isset($txs->error)) {
            return array();
        }
        return $txs->result->transactions;
    }

    static function validar_pagos()
    {
        if (WC()->payment_gateways->payment_gateways()['sargapay-one-plugin']->enabled) {
            # Get Network
            $network = WC()->payment_gateways->payment_gateways()['sargapay-one-plugin']->network == 1 ? 1 : 0;
            # Get Payment Address
            $address = WC()->payment_gateways->payment_gateways()['sargapay-one-plugin']->pay_address;
            # Get Last transaction checked
            $last_tx_hash_check = SELF::get_last_tx_hash_check($network, $address);
            # Get On Hold Orders
            $on_hold_orders = SELF::get_on_hold_orders($network);
            # Get All Txs
            $all_txs  = SELF::get_all_txs($network, $address);
            $last_tx  = $all_txs[0]->hash;
            $new_txs = [];

            if ($last_tx !== $last_tx_hash_check) {
                foreach ($all_txs as $tx) {
                    if ($last_tx_hash_check === $tx->hash) {
                        break;
                    } else {
                        array_push($new_txs, $tx);
                    }
                }
                #Check for payments ***TODO***
                $payments = SELF::search_payments($on_hold_orders, $new_txs);
                #Update last_tx_hash_check
                SELF::set_last_tx_hash_check($last_tx, $network, $address);
                # Update Status of new Payments
                SELF::new_payments($payments);
            }
            # Check Pendding Orders Confirmations
            SELF::check_confirmations($network);
            # Check if 24 hrs has passed to cancel orders
            SELF::caducaron($on_hold_orders);
        }
    }

    static function check_confirmations()
    {
        # Get All Validating Orders
        global $wpdb;
        $query = 'SELECT order_id, tx_hash, pay_address, network FROM ' . SELF::$address_table . ' WHERE status_order = "validation"';
        $pendding_orders = $wpdb->get_results($query);
        if ($wpdb->last_error) {
            write_log($wpdb->last_error);
            $pendding_orders = [];
        }

        # Get Min Confirmations
        $min_confirmations = WC()->payment_gateways->payment_gateways()['sargapay-one-plugin']->confirmations;
        # Get Last Block from Testnet and Mainnet
        $get_last_blocks    = SELF::last_block();
        $testnet_last_block = $get_last_blocks->testnet;
        $mainnet_last_block = $get_last_blocks->mainnet;

        foreach ($pendding_orders as $order) {
            $hash             = $order->tx_hash;
            $payment_address  = $order->pay_address;
            $network          = $order->network;
            $id               = $order->order_id;
            $last_block       = $network == 1 ? $testnet_last_block : $mainnet_last_block;
            $tx_info          = SELF::get_info_from_tx($hash, $network);
            if (
                $tx_info !== null &&
                $payment_address == $tx_info->pay_address
            ) {
                # check confirmations
                if (($last_block - $tx_info->block) >= $min_confirmations)
                    SELF::update_payment_status($id, "paid");
            }
        }
    }

    static function get_info_from_tx($hash, $network)
    {
        $result = new stdClass;
        $URL = $network == 1 ? 'https://api.s0.b.hmny.io' : 'https://api.harmony.one';
        $parameters = '{
            "jsonrpc": "2.0",
            "method": "hmyv2_getTransactionByHash",
            "params": ["' . $hash . '"],
            "id": 1
        }';
        $header   = array('Content-Type:application/json');
        $response = SELF::API_CALL($URL, $parameters, $header, true);

        if (isset($response->error) || $response->result == null) {
            write_log("ERROR get_info_from_tx");
            write_log(
                isset($response->error) ?
                    $response->error :
                    "No se encontro la Tx en la red"
            );
            return null;
        }
        $result->block       = $response->result->blockNumber;
        $result->pay_address = $response->result->to;
        return $result;
    }

    static function search_payments($on_hold_orders, $all_txs)
    {
        $payments = [];
        foreach ($on_hold_orders as $order) {
            foreach ($all_txs as $key => $tx) {
                $value = round($tx->value / pow(10, 18), 8);
                $id    = $order->order_id;
                $input = hexdec($tx->input);
                if ($order->order_amount == $value && $id == $input) {
                    $payments[$id] = $tx;
                    unset($all_txs[$key]);
                    break;
                }
            }
        }

        return $payments;
    }

    static function update_payment_status($id, $status, $tx = null)
    {
        global $wpdb;
        $data = $tx === null ?
            array('status_order' => $status) :
            array('height' => $tx->blockNumber, 'tx_hash' => $tx->hash, 'status_order' => $status);

        # Update Status on Internal DB
        $where = ['order_id' => $id];
        $updated = $wpdb->update(SELF::$address_table, $data, $where);
        if (false === $updated) write_log($wpdb->last_error);

        if (strcmp($status, "paid") === 0) {
            # Update Status in Woo
            $wc_order = wc_get_order($id);
            $wc_order->update_status('completed');
        }
    }

    static function set_last_tx_hash_check($tx_hash, $network, $pay_address)
    {
        global $wpdb;
        # Save in DB
        $data = array('tx_hash' => $tx_hash, 'pay_address' => $pay_address, 'network' => $network);
        $format = array('%s', '%s', '%d');
        $wpdb->insert(SELF::$last_tx_table, $data, $format);
        if ($wpdb->last_error) write_log($wpdb->last_error);
    }

    static function get_last_tx_hash_check($network, $pay_address)
    {

        global $wpdb;
        # Get de last Tx Hash
        $query = 'SELECT tx_hash FROM ' . SELF::$last_tx_table . ' WHERE network = "' . $network . '" AND pay_address = "' . $pay_address . '" ORDER BY id DESC LIMIT 1';
        $response = $wpdb->get_results($query);
        if ($wpdb->last_error) write_log($wpdb->last_error);
        return isset($response[0]->tx_hash) ? $response[0]->tx_hash : "";
    }

    static function caducaron($on_hold_orders)
    {
        foreach ($on_hold_orders as $order) {
            $order_id = $order->order_id;
            $wc_order = wc_get_order($order_id);
            $date_created_dt = $wc_order->get_date_created();
            # Get the timezone
            $timezone = $date_created_dt->getTimezone();
            # Get the timestamp in seconds
            $date_created_ts = $date_created_dt->getTimestamp();
            # Get current WC_DateTime object instance
            $now_dt = new WC_DateTime();
            # Set the same time zone
            $now_dt->setTimezone($timezone);
            # Get the current timestamp in seconds
            $now_ts = $now_dt->getTimestamp();
            # 24hours in seconds            
            $twenty_four_hours = 24 * 60 * 60;
            # diferencia de segundos desde que se realizo la orden
            $diff_in_seconds = $now_ts - $date_created_ts;
            # revisa si tiene más de 24 hrs 
            $diff_in_seconds > $twenty_four_hours ?
                SELF::cancel_order($order_id, $now_ts) :
                SELF::update_last_checked($order_id, $now_ts);
        }
    }

    static function create_new_order($data)
    {
        global $wpdb;
        /*
        $data = [
            'pay_address'  => string,
            'order_id'     => int,
            'order_amount' => string,
            'one_price'    => float,
            'currency'     => string,
            'network'      => int
        ];
        */
        $format = array('%s', '%d', '%s', '%f', '%s', '%d');
        $wpdb->insert(SELF::$address_table, $data, $format);
        if ($wpdb->last_error) write_log($wpdb->last_error);
    }

    static function get_on_hold_orders($network)
    {
        global $wpdb;
        $query = 'SELECT * FROM ' . SELF::$address_table . ' WHERE status_order = "on-hold" AND network = "' . $network . '"';
        $on_hold_orders = $wpdb->get_results($query);
        if ($wpdb->last_error) {
            write_log($wpdb->last_error);
            return [];
        } else {
            return $on_hold_orders;
        }
    }

    static function update_last_checked($order_id, $now_ts)
    {
        global $wpdb;
        $where = ['order_id' => $order_id];
        $data = ['last_checked' => $now_ts];
        $updated = $wpdb->update(SELF::$address_table, $data, $where);
        if (false === $updated) write_log($wpdb->last_error);
    }

    static function cancel_order($order_id, $now_ts)
    {
        # Cancel Status on Woocomerce        
        $wc_order = wc_get_order($order_id);
        $wc_order->update_status('cancelled');

        # Cancel Status on Internal DB
        global $wpdb;
        $where = ['order_id' => $order_id];
        $data = ['status_order' => 'cancelled', 'last_checked' => $now_ts];
        $updated = $wpdb->update(SELF::$address_table, $data, $where);
        if (false === $updated) write_log($wpdb->last_error);
    }

    static function new_payments($payments)
    {
        if ($payments != []) {
            foreach ($payments as $key => $tx) {
                $id = $key;
                $status = "validation";
                SELF::update_payment_status($id, $status, $tx);
            }
        }
    }

    static function get_total_one($order_id, $total)
    {
        # Decimales de la cripto
        $tag_div = strval(pow(10, 8));
        # Se genera el Tag apartir del order ID
        $tag = BigDecimal::of(strval($order_id), 8)->dividedBy($tag_div, 8);
        # Se le suma al total el tag para indentificar el pago
        $total_one = BigDecimal::of($tag)->plus(strval($total))->__toString();
        return $total_one;
    }
}
