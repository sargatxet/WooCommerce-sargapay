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

window.onload = function() {
    //Generate Check Button
    const input = document.getElementById("woocommerce_sargapay_mpk")
    if (input) {
        let pay_address_mainet = null
        let pay_address_testnet = null
            // Add Div to Public Key input
        const div = document.createElement("div")
        input.after(div)
            // Generate input field
        const input_field = document.createElement("input")
        input_field.id = "gen_index"
        input_field.type = "number"
        input_field.value = 0
        input_field.name = "gen_index"
        input_field.style.width = "10%"
            // Generate Label for Input Field
        const label_input = document.createElement("label")
        label_input.htmlFor = "gen_index"
        label_input.textContent = "Index"
        label_input.style.paddingRight = "5px"
            // Generate Button to test public key
        const button = document.createElement("button")
        button.className = "button"
        const tittle = document.querySelector(
            "#mainform > table > tbody > tr:nth-child(2) > th > label"
        )
        if (tittle.textContent.localeCompare("Título")) {
            button.textContent = "Probar La Llave Pública"
        } else {
            button.textContent = "Test Public Key"
        }
        //Generate Check Button 
        sargapay_gen_extra_fields();
        sargapay_ajax_gen_address(1)
    }
}

function sargapay_ajax_gen_address() {
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
}


function sargapay_gen_extra_fields() {
    const input = document.getElementById('woocommerce_sargapay_mpk');
    const ap_k_main = document.getElementById('woocommerce_sargapay_blockfrost_key');
    const ap_k_test = document.getElementById('woocommerce_sargapay_blockfrost_test_key');
    let pay_address_mainet = null;
    let pay_address_testnet = null;
    if (ap_k_main) sargapay_show_p(ap_k_main);
    if (ap_k_test) sargapay_show_p(ap_k_test);
    if (input) {
        // Show Password btn        
        sargapay_show_p(input);
        // Add Div to Public Key input
        const div = document.createElement("div");
        input.after(div);
        // Generate input field 
        const input_field = document.createElement('input');
        input_field.id = "gen_index";
        input_field.type = "number";
        input_field.value = 0;
        input_field.name = "gen_index";
        input_field.style.width = "10%";
        // Generate Label for Input Field
        const label_input = document.createElement('label');
        label_input.htmlFor = 'gen_index';
        label_input.textContent = "Index";
        label_input.style.paddingRight = '5px';
        // Generate Button to test public key        
        const button = document.createElement("button");
        button.className = "button";
        const tittle = document.querySelector("#mainform > table > tbody > tr:nth-child(2) > th > label");
        if (tittle.textContent.localeCompare("Título")) {
            button.textContent = 'Probar La Llave Pública';
        } else {
            button.textContent = "Test Public Key";
        }
        button.id = 'check_button_option';
        // Add input field        
        div.append(label_input);
        div.append(input_field);
        // Add button to Div
        div.append(button);

        button.addEventListener("click", function(event) {
            event.preventDefault();
            if ((input.value).trim().length > 0) {
                //check if index is set 
                let index_value = 0;
                const index = document.getElementById("gen_index");
                if (!isNaN(index.value)) {
                    if (parseInt(index.value) >= 0) {
                        index_value = index.value;
                    } else {
                        index.value = 0;
                    }
                }
                const xpub = input.value.trim();
                //Generate Payment Address Mainet
                pay_address_mainet = sargapay_generate_payment_address(xpub, index_value, 1, 1);
                //Generate Payment Address Testnet
                pay_address_testnet = sargapay_generate_payment_address(xpub, index_value, 1, 0);
                if (!document.getElementById('address_testnet_p')) {
                    const address_testnet = document.createElement("p");
                    address_testnet.style.fontWeight = 'bold';
                    address_testnet.id = "address_testnet_p";
                    address_testnet.textContent = "Testnet = " + pay_address_testnet[0];
                    div.append(address_testnet);
                } else {
                    const address_testnet = document.getElementById('address_testnet_p');
                    address_testnet.textContent = "Testnet = " + pay_address_testnet[0];
                    div.append(address_testnet);
                }
                if (!document.getElementById('address_mainet_p')) {
                    const address_mainnet = document.createElement("p");
                    address_mainnet.id = "address_mainet_p";
                    address_mainnet.style.fontWeight = 'bold';
                    address_mainnet.textContent = "Mainnet = " + pay_address_mainet[0];
                    div.append(address_mainnet);
                } else {
                    const address_mainnet = document.getElementById('address_mainet_p');
                    address_mainnet.textContent = "Mainnet = " + pay_address_mainet[0];
                    div.append(address_mainnet);
                }
            }
        });
    }
}

function sargapay_show_p(input) {
    const btn = document.createElement("span");
    btn.className = "button dashicons dashicons-visibility";
    btn.style.paddingRight = "25px";
    input.before(btn);
    btn.addEventListener("click", function(e) {
        e.preventDefault();
        if (input.type === "password") {
            input.type = "text";
            btn.className = "button dashicons dashicons-hidden";
        } else {
            input.type = "password";
            btn.className = "button dashicons dashicons-visibility";
        }
    });
}

function sargapay_add_index(xpub, lastIndex, testnet) {

    const url = wp_ajax_sargapay_save_address.ajax_url
    console.log("add index")

    // Generate New Address
    const address = sargapay_generate_payment_address(xpub, lastIndex, 1, testnet)

    console.dir(address)

    // Save New Address on DB
    if (address.length > 0 && !address[0].includes("Error:")) {
        jQuery.ajax({
            type: "post",
            url: url,
            data: {
                action: "sargapay_save_address",
                addresses: address,
                action_type: "save_address",
            },
        })
    } else {
        console.log("error address")
    }
}