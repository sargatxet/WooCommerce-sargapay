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

import { generate_payment_address } from "./gen_address.js"

window.onload = () => {
    copy_btns();
    wp_gen();
}


function copy_btns() {
    // Copy Address
    if (document.getElementById('pay_add_p_field_tk_plugin') &&
        document.getElementById('pay_add_button_field_tk_plugin')) {
        const button = document.getElementById('pay_add_button_field_tk_plugin');
        button.addEventListener("click", function(e) {
            const address = document.getElementById("pay_add_p_field_tk_plugin");
            copy(address.textContent);
            const modal = document.getElementById("copy_modal");
            const span = document.getElementsByClassName("close_tk_plugin")[0];
            const address_sp = document.getElementById("address_copiado_sp")
            address_sp.innerText = address.textContent;
            modal.style.display = "block";
            span.onclick = () => modal.style.display = "none";
            window.onclick = (event) => {
                if (event.target == modal) modal.style.display = "none";
            }
        });
    }

    // Copy Amount     
    if (document.getElementById('pay_amount_span_field_tk_plugin') &&
        document.getElementById('pay_amount_button_field_tk_plugin')) {
        const button_amount = document.getElementById('pay_amount_button_field_tk_plugin');
        button_amount.addEventListener("click", function(e) {
            const amount = document.getElementById("pay_amount_span_field_tk_plugin");
            copy(amount.textContent);
            const modal = document.getElementById("copy_modal_amount");
            const amount_sp = document.getElementById("amount_copiado_sp");
            amount_sp.innerText = amount.textContent;
            const span = document.getElementsByClassName("close_tk_plugin")[1];
            modal.style.display = "block";
            span.onclick = () => modal.style.display = "none";
            window.onclick = (event) => {
                if (event.target == modal) modal.style.display = "none";
            }
        });
    }
    // Copy Input Data
    if (document.getElementById('input_data_span_field_tk_plugin') &&
        document.getElementById('input_data_button_field_tk_plugin')) {
        const button_input = document.getElementById('input_data_button_field_tk_plugin');
        button_input.addEventListener("click", function(e) {
            const input_data = document.getElementById("input_data_span_field_tk_plugin");
            copy(input_data.textContent);
            const modal = document.getElementById("copy_modal_input_data");
            const amount_sp = document.getElementById("input_data_copiado_sp");
            amount_sp.innerText = input_data.textContent;
            const span = document.getElementsByClassName("close_tk_plugin")[2];
            modal.style.display = "block";
            span.onclick = () => modal.style.display = "none";
            window.onclick = (event) => {
                if (event.target == modal) modal.style.display = "none";
            }
        });
    }

}

function copy(text) {
    const input = document.createElement('input');
    input.setAttribute('value', text);
    document.body.appendChild(input);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(text);
    document.body.removeChild(input);
}

function timeToSec(time) {

    let data = time.split(":");

    let hourstoSec = parseInt(data[0]) * 60 * 60;
    let minstoSec = parseInt(data[1]) * 60;
    let secs = parseInt(data[2]);
    let total = hourstoSec + minstoSec + secs;
    return total;

}
if (document.getElementById("timer_order") !== null) {

    const time = document.getElementById("timer_order").innerText;
    const time_left = timeToSec(time) * 1000;
    const init_time = new Date().getTime();
    const time_limit = time_left + init_time;
    var countDown = setInterval(() => {
        // Get Time Left  
        var now = new Date().getTime();
        var distance = time_limit - now;
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        document.getElementById("timer_order").innerHTML = hours + "h " +
            minutes + "m " + seconds + "s ";
        // If the count down is finished
        if (distance < 0) {
            clearInterval(countDown);
            document.getElementById("timer_order").innerHTML = "EXPIRED";
        }

    }, 1000);
}

function wp_gen() {
    //Get How many addresses have left
    const unused = null
    jQuery.ajax({
        type: "post",
        url: wp_ajax_save_address_vars.ajax_url,
        data: {
            action: "save_address",
            action_type: "get_unused",
            nonce: wp_ajax_save_address_vars.nonce,
        },
        error: function(response) {
            console.log(response)
        },
        success: function(response) {
            const unused = response.unused
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
            if (parseInt(unused) < 20 && xpub !== "") {
                wp_add_index(xpub, lastIndex, testnet)
                console.dir(testnet);
            }
        }
    })
}

function wp_add_index(xpub, lastIndex, testnet) {
    // Generate New Address
    const address = generate_payment_address(xpub, lastIndex, 1, testnet)
        // Save New Address on DB
    if (address.length > 0 && !address[0].includes("Error:")) {
        jQuery.ajax({
            type: "post",
            url: wp_ajax_save_address_vars.ajax_url,
            nonce: wp_ajax_save_address_vars.nonce,
            data: {
                action: "save_address",
                addresses: address,
                action_type: "save_address"
            }
        })
    }
}