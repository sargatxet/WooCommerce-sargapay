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

// Add Payment Method to Woocommerce

function sargapay_plugin_add_gateway_class($gateways)
{
    $gateways[] = 'SARGAPAY_WC_Gateway';
    return $gateways;
}

function sargapay_get_settings_vars()
{
    $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : false;
    if ($action) {
        if (wp_doing_ajax() && $action === "sargapay_get_settings_vars") {

            // 0=TESTNET 1=MAINNET
            $testmode = WC()->payment_gateways->payment_gateways()['sargapay']->testmode == 1 ? 1 : 0;

            $network = $testmode == 1 ? $network = 0 : $network = 1;

            $APIKEY  = $network === 1 ? WC()->payment_gateways->payment_gateways()['sargapay']->blockfrost_key :
                WC()->payment_gateways->payment_gateways()['sargapay']->blockfrost_test_key;

            wp_send_json(array('apikey' => $APIKEY, 'network' => $network));
        }
    }
    wp_die();
}

//Function to add settings link
function sargapay_settings_link($links)
{
    // Build and escape the URL.
    $url = esc_url(add_query_arg(
        array('page' =>
        'wc-settings', 'tab' => 'checkout', 'section' => 'sargapay'),
        get_admin_url() . 'admin.php'
    ));
    // Create the link.
    $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
    // Adds the link to the end of the array.
    array_push(
        $links,
        $settings_link
    );
    return $links;
}
