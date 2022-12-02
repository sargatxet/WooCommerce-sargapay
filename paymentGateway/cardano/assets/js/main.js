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
import { sargapay_generate_payment_address } from "./gen_address.js"

window.onload = () => sargapay_ajax_gen_address()

function sargapay_ajax_gen_address() {
    try {
        jQuery.ajax({
            type: "post",
            url: wp_ajax_sargapay_save_address.ajax_url,
            data: {
                action: "sargapay_save_address",
                action_type: "get_xpub"
            },
            error: function(response) {
                console.log(response)
            },
            success: function(response) {
                // Get xpub and data needed to generate payment addresses
                const unused = response.unused === null ? 0 : response.unused
                const xpub = response.xpub
                const testnet = response.network
                let lastIndex = response.last_unused

                // No address ever generated
                if (lastIndex === null) {
                    lastIndex = 0
                } else {
                    lastIndex = parseInt(response.last_unused)
                    if (lastIndex === 0) {
                        // first address generated
                        lastIndex = 1
                    } else {
                        // more than one addresses were generated
                        lastIndex += 1
                    }
                }
                // IF you have less than 20 unused address you will generate a new one
                if (parseInt(unused) < 20) {
                    sargapay_add_index(xpub, lastIndex, testnet)
                }
            }
        })
    } catch (error) {
        console.log(error)
    }
}

function sargapay_add_index(xpub, lastIndex, testnet) {

    const url = wp_ajax_sargapay_save_address.ajax_url
    console.log("add index")

    // Generate New Address
    const address = sargapay_generate_payment_address(xpub, lastIndex, 1, testnet)

    console.dir(address)

    // Save New Address on DB
    if (address.length > 0 && !address[0].includes("Error:")) {
        try {
            jQuery.ajax({
                type: "post",
                url: url,
                data: {
                    action: "sargapay_save_address",
                    addresses: address,
                    action_type: "save_address",
                },
            })
        } catch (error) {
            console.log(error)
        }
    } else {
        console.log("error address")
    }
}