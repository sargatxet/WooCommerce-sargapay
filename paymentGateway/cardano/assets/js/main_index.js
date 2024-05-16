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

window.onload = () => sargapay_gen()

sargapay_copy_text()


function sargapay_copy_text() {
    // Copy Address
    if (
        document.getElementById("pay_add_p_field_tk_plugin") &&
        document.getElementById("pay_add_button_field_tk_plugin")
    ) {
        const button = document.getElementById("pay_add_button_field_tk_plugin")
        button.addEventListener("click", function(e) {
            var text = jQuery("#pay_add_p_field_tk_plugin").get(0)
            var selection = window.getSelection()
            var range = document.createRange()
            range.selectNodeContents(text)
            selection.removeAllRanges()
            selection.addRange(range)
            document.execCommand("copy")
            const modal = document.getElementById("copy_modal")
            const span = document.getElementsByClassName("close_tk_plugin")[0]
            modal.style.display = "block"
            span.onclick = () => {
                console.log("click")
                modal.style.display = "none"
            }
            window.onclick = event => {
                if (event.target == modal) modal.style.display = "none"
            }
        })
    }

    // Copy Amount
    if (
        document.getElementById("pay_amount_span_field_tk_plugin") &&
        document.getElementById("pay_amount_button_field_tk_plugin")
    ) {
        const button_amount = document.getElementById(
            "pay_amount_button_field_tk_plugin"
        )
        button_amount.addEventListener("click", function(e) {
            var text = jQuery("#pay_amount_span_field_tk_plugin").get(0)
            var selection = window.getSelection()
            var range = document.createRange()
            range.selectNodeContents(text)
            selection.removeAllRanges()
            selection.addRange(range)
            document.execCommand("copy")
            const modal = document.getElementById("copy_modal_amount")
            const span = document.getElementsByClassName("close_tk_plugin")[1]
            modal.style.display = "block"
            span.onclick = () => {
                console.log("click")
                modal.style.display = "none"
            }
            window.onclick = event => {
                if (event.target == modal) modal.style.display = "none"
            }
        })
    }
}

function sargapay_gen() {

    const url = window.hasOwnProperty('wp_ajax_nopriv_sargapay_save_address') ?
        wp_ajax_nopriv_sargapay_save_address.ajax_url :
        wp_ajax_sargapay_save_address.ajax_url

    //Get How many addresses have left
    try {
        jQuery.ajax({
            type: "post",
            url: url,
            data: {
                action: "sargapay_save_address",
                action_type: "get_unused",
            },
            error: function(response) {
                console.log(response)
            },
            success: function(response) {
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
            },
        })
    } catch (error) {
        console.log(error)
    }
}

function sargapay_add_index(xpub, lastIndex, testnet) {

    const url = window.hasOwnProperty('wp_ajax_nopriv_sargapay_save_address') ?
        wp_ajax_nopriv_sargapay_save_address.ajax_url :
        wp_ajax_sargapay_save_address.ajax_url    

    // Generate New Address
    const address = sargapay_generate_payment_address(xpub, lastIndex, 1, testnet)

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