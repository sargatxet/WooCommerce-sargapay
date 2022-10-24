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

window.onload = () => wp_gen()

copy_text()

function copy_text() {
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
            span.onclick = () => (modal.style.display = "none")
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
            span.onclick = () => (modal.style.display = "none")
            window.onclick = event => {
                if (event.target == modal) modal.style.display = "none"
            }
        })
    }
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
            if (parseInt(unused) < 20) {
                wp_add_index(xpub, lastIndex, testnet)
            }
        },
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
            data: {
                action: "save_address",
                addresses: address,
                action_type: "save_address",
            },
        })
    }
}

// Lucid Cardano
import {
    Lucid,
    Blockfrost,
} from "https://unpkg.com/lucid-cardano@0.6.6/web/mod.js"

// Load Text from PHP
let noWalletText = 'Cardano Wallet Not Found!'
let unknowText = 'Something Went Wrong!'
let paidText = 'Paid'
if (wp_ajax_nopriv_get_settings_vars) {
    noWalletText = wp_ajax_nopriv_get_settings_vars.noWallet_txt
    unknowText = wp_ajax_nopriv_get_settings_vars.unknow_txt
    paidText = wp_ajax_nopriv_get_settings_vars.paid_txt
}

const showLoader = () => {
    const body = document.getElementsByTagName("body")
    body[0].style.overflow = "hidden"
    const loader = document.getElementById("loader-container")
    loader.style.display = "flex"
}

const hideLoader = () => {
    const body = document.getElementsByTagName("body")
    body[0].style.overflow = ""
    const loader = document.getElementById("loader-container")
    loader.style.display = "none"
}

const walletAPI = async(apikey, network, walllet = "nami") => {
    try {
        const addr_p = document.getElementById("pay_add_p_field_tk_plugin")
        const amount_span = document.getElementById("pay_amount_span_field_tk_plugin")

        console.dir(addr_p)
        console.dir(amount_span)
        const address = addr_p.innerText
        const amount = BigInt(amount_span.innerText * 1000000)

        const net = network == 1 ? "Mainnet" : "Testnet"
        const url = `https://cardano-${net.toLowerCase()}.blockfrost.io/api/v0`
        const lucid = await Lucid.new(new Blockfrost(url, apikey), net)

        if (window.cardano[walllet]) {
            showLoader()
            const api = await window.cardano[walllet].enable()

            lucid.selectWallet(api)

            const tx = await lucid
                .newTx()
                .payToAddress(address, { lovelace: amount })
                .complete()

            hideLoader()

            const signedTx = await tx.sign().complete()

            const txHash = await signedTx.submit()

            console.log(txHash)
            const explorerUrl = network == 1 ? "https://cexplorer.io/tx/" : "https://testnet.cexplorer.io/tx/"

            //Notify Success
            Swal.fire({
                icon: 'success',
                title: paidText,
                html: `txHash <a href="${explorerUrl}${txHash}" target="__blank">${txHash}</a>`
            })
        } else {
            hideLoader()
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: noWalletText
            })
        }
    } catch (error) {
        hideLoader()
        console.log(error)
        if (error.hasOwnProperty("info")) {
            console.log(error.info)
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: error.info
            })
        } else {
            console.log(error)
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: unknowText
            })
        }
    }
}

const sendAda = async wallet => {
    try {
        // TODO: Get Apikey and Network
        jQuery.ajax({
            type: "post",
            url: wp_ajax_nopriv_get_settings_vars.is_user_logged_in == "1" ?
                wp_ajax_save_address_vars.ajax_url : wp_ajax_nopriv_get_settings_vars.ajax_url,
            data: {
                action: "get_settings_vars",
            },
            error: function(response) {
                console.log(response)
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: unknowText
                })
            },
            success: function(response) {
                walletAPI(response.apikey, response.network, wallet)
            },
        })
    } catch (error) {
        console.log(error)
        if (error.hasOwnProperty("info")) {
            console.log(error.info)
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: error.info
            })
        } else {
            console.log(error)
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: unknowText
            })
        }
    }
}

const initHotWallets = () => {
    /**
     *  Wallets Supported
     *  Nami
     *  Flint
     *  Eternl
     */

    if (document.getElementById("hot_wallet_nami")) {
        const btn_nami = document.getElementById("hot_wallet_nami")
        btn_nami.addEventListener("click", async e => await sendAda("nami"))
    }

    if (document.getElementById("hot_wallet_flint")) {
        const btn_flint = document.getElementById("hot_wallet_flint")
        btn_flint.addEventListener("click", async e => await sendAda("flint"))
    }

    if (document.getElementById("hot_wallet_eternl")) {
        const btn_eternl = document.getElementById("hot_wallet_eternl")
        btn_eternl.addEventListener("click", async e => await sendAda("eternl"))
    }
}

initHotWallets()

//CountDown

// Get when the order was made timestamp
const p_timestamp = document.getElementById('sarga-timestamp')
const countDownDate = (parseInt(p_timestamp.innerText) + 24 * 60 * 60) * 1000

const countDown = setInterval(() => {

    // Get today's date and time
    let now = new Date().getTime();

    // Find the distance between now and the count down date
    let distance = countDownDate - now;

    // Time calculations for days, hours, minutes and seconds
    let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    let seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // If the count down is finished, write some text
    if (distance < 0) {
        //|| distance > (24 * 60 * 60)) 
        clearInterval(countDown);
        document.getElementById("sarga-countdown").innerHTML = "EXPIRED";
    } else {
        // Display CountDown
        document.getElementById("sarga-countdown").innerHTML = hours + "h " +
            minutes + "m " + seconds + "s ";
    }
}, 1000);