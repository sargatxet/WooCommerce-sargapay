<?php

class ConfirmPayment
{

    function check_all_pendding_orders()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wc_sarga_address';
        $orders = $wpdb->get_results("SELECT id, pay_address, order_id, order_amount, testnet FROM $table WHERE status='on-hold' OR status = 'validation'");
        if ($wpdb->last_error) {
            //LOG Error
            write_log($wpdb->last_error);
        } else {
            if (count($orders) !== 0) {
                for ($i = 0; $i < count($orders); $i++) {
                    $network = $orders[$i]->testnet == 1 ? 0 : 1;
                    $order = wc_get_order($orders[$i]->order_id);
                    // TIME SINCE ORDER WAS CREATED
                    // Get order date created
                    $date_created_dt = $order->get_date_created();
                    // Get the timezone
                    $timezone = $date_created_dt->getTimezone();
                    // Get the timestamp in seconds
                    $date_created_ts = $date_created_dt->getTimestamp();
                    // Get current WC_DateTime object instance
                    $now_dt = new WC_DateTime();
                    // Set the same time zone
                    $now_dt->setTimezone($timezone);
                    // Get the current timestamp in seconds
                    $now_ts = $now_dt->getTimestamp();
                    // 24hours in seconds            
                    $twenty_four_hours = 24 * 60 * 60;
                    // Get the difference (in seconds)
                    $diff_in_seconds = $now_ts - $date_created_ts;
                    $confirmation_obj = $this->get_confirmations(
                        $orders[$i]->pay_address,
                        $orders[$i]->order_amount,
                        $network,
                        $date_created_ts
                    );
                    // if error = 0 and confirmations greater than 0 update confirmations 
                    if ($confirmation_obj->error === 0) {
                        //update confirmation  
                        if ($confirmation_obj->confirmations > 0) {
                            if ($confirmation_obj->confirmations > WC()->payment_gateways->payment_gateways()['sargapay-plugin']->confirmations) {
                                $data = ['status' => 'paid', 'last_checked' => $now_ts];
                                $order->update_status('completed');
                            } else {
                                $data = ['status' => 'validation', 'last_checked' => $now_ts];
                            }
                            $where = ['id' => $orders[$i]->id];
                            $updated = $wpdb->update($table, $data, $where);
                            if (false === $updated) {
                                // DB LOG ERROR.
                                write_log($wpdb->last_error);
                            }
                        } else {
                            if ($diff_in_seconds > $twenty_four_hours) {
                                $order->update_status('cancelled');
                                $data = ['status' => 'cancelled', 'last_checked' => $now_ts];
                            } else {
                                $data = ['last_checked' => $now_ts];
                            }
                            $where = ['id' => $orders[$i]->id];
                            $updated = $wpdb->update($table, $data, $where);
                            if (false === $updated) {
                                // DB LOG ERROR.
                                write_log($wpdb->last_error);
                            }
                        }
                    } else if ($confirmation_obj->error == 404) {
                        if ($diff_in_seconds > $twenty_four_hours) {
                            $order->update_status('cancelled');
                            $data = ['status' => 'cancelled', 'last_checked' => $now_ts];
                        } else {
                            $data = ['last_checked' => $now_ts];
                        }
                        $where = ['id' => $orders[$i]->id];
                        $updated = $wpdb->update($table, $data, $where);
                        if (false === $updated) {
                            // DB LOG ERROR.
                            write_log($wpdb->last_error);
                        }
                    } else {
                        //LOG ERROR
                        if ($diff_in_seconds > $twenty_four_hours) {
                            $order->update_status('cancelled');
                            $data = ['status' => 'cancelled', 'last_checked' => $now_ts];
                        }
                        write_log("Error Call BLockForst API " . $confirmation_obj->error);
                    }
                }
            }
        }
    }

    function get_confirmations($payment_address, $payment_amount, $network, $order_was_made)
    {
        $confirmations = 0;
        $transaction_amount = 0;
        $has_ada = false;
        // GET ALL TRANSACTIONS OF THE ADDRESS
        if ($network == 1) {
            $url_network = 'https://cardano-mainnet.blockfrost.io/api/v0/';
            $api_key = WC()->payment_gateways->payment_gateways()['sargapay-plugin']->blockfrost_key;
            $stake_key = substr($payment_address, 53, -6);
        } else {
            $url_network = 'https://cardano-testnet.blockfrost.io/api/v0/';
            $api_key = WC()->payment_gateways->payment_gateways()['sargapay-plugin']->blockfrost_test_key;
            $stake_key = substr($payment_address, 58, -6);
        }
        $result = new stdClass;
        // First Api call to see if address is in cardano blockchain
        $url = $url_network . 'addresses/' . $payment_address;
        $response_data = $this->curl_api_call($url, $api_key);
        // Check if the api key
        if (isset($response_data->status_code)) {
            // 404 Error means there is not register on blockchain
            $result->error = $response_data->status_code;
            $result->error_msg = $response_data->message;
            return $result;
        } else {
            // get the amount of lovelance in address       
            foreach ($response_data->amount as $key) {
                // puede quedar en 0 por que se gasto el dinero pero existe el registro del deposito
                if ($key->unit === "lovelace" && $key->quantity >= 0) {
                    $has_ada = true;
                }
            }
            if ($has_ada) {
                // Get all Transactions  
                $url = $url_network . 'addresses/' . $payment_address . '/transactions?order=desc';
                $response_data = $this->curl_api_call($url, $api_key);
                // Api Return tx_hash - Hash de la transacción                               
                // loop for every transaction to get tx hash
                // Iterar cada transacción para obtener el hash 
                foreach ($response_data as $key) {
                    // Get Block for each transactions 
                    // Obtenemos el bloque de cada transacción
                    $url = $url_network . 'txs/' . $key->tx_hash;
                    $response_data = $this->curl_api_call($url, $api_key);
                    // get the time each block was created
                    // obtener el tiempo de creación de los bloques
                    $url = $url_network . 'blocks/' . $response_data->block;
                    $response_data = $this->curl_api_call($url, $api_key);
                    $confirmations = $response_data->confirmations;
                    // Check if the transaction was made after the order
                    // Revisa si la transacción fue hecha despues de la orden                
                    if ($order_was_made <= $response_data->time) {
                        $url = $url_network . 'txs/' . $key->tx_hash . "/utxos";
                        $response_data = $this->curl_api_call($url, $api_key);
                        $internal_deposit = false;
                        // Loop for each input to find if the imput came from the same wallet
                        foreach ($response_data->inputs as $key) {                            
                            if (
                                $key->address === $payment_address ||
                                $stake_key === substr($key->address, 53, -6) ||
                                $stake_key === substr($key->address, 58, -6)
                            ) {
                                $internal_deposit = true;
                                break;
                            }
                        }
                        if (!$internal_deposit) {
                            // Loop for each output to find deposits to the payment address
                            // iterar cada salida para encontrar los depositos a la dirección de pago 
                            foreach ($response_data->outputs as $key) {
                                if ($key->address === $payment_address) {
                                    foreach($key->amount as $asset){
                                        if($asset->unit === "lovelace"){
                                            $transaction_amount += round(intval($asset->quantity) / 1000000, 6);
                                        }
                                    }                                    
                                    // check if current amount is the same or more than the order amount
                                    // Revisar si el monto de las transacciones superan el de la orden                        
                                    if ($transaction_amount >= $payment_amount) {
                                        //return Confirmations 
                                        $result->error = 0;
                                        $result->confirmations = $confirmations;
                                        return $result;
                                    }
                                }
                            }
                        } else {
                            break;
                        }
                    }
                }
            }
            $result->error = 0;
            $result->confirmations = 0;
            return $result;
        }
    }

    function curl_api_call($url, $api_key)
    {
        $curl_confirmations = curl_init();
        curl_setopt($curl_confirmations, CURLOPT_URL, $url);
        curl_setopt($curl_confirmations, CURLOPT_HTTPHEADER, array(
            'project_id: ' . $api_key
        ));
        curl_setopt($curl_confirmations, CURLOPT_RETURNTRANSFER, true);
        $curl_data = curl_exec($curl_confirmations);
        $response_data = json_decode($curl_data);
        curl_close($curl_confirmations);
        return $response_data;
    }
}
