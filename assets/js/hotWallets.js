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

// Lucid Cardano
import {
    Lucid,
    Blockfrost,
} from "https://unpkg.com/lucid-cardano@0.6.6/web/mod.js"

// Load Text from PHP
let noWalletText = 'Cardano Wallet Not Found!'
let unknowText = 'Something Went Wrong!'
let paidText = 'Paid'
let wrongNetworkText = 'Wrong Network, Please Select the Correct Network'
if (wp_ajax_nopriv_sargapay_get_settings_vars) {
    noWalletText = wp_ajax_nopriv_sargapay_get_settings_vars.noWallet_txt
    unknowText = wp_ajax_nopriv_sargapay_get_settings_vars.unknow_txt
    paidText = wp_ajax_nopriv_sargapay_get_settings_vars.paid_txt
    wrongNetworkText = wp_ajax_nopriv_sargapay_get_settings_vars.error_wrong_network_txt
}

const sargapay_showLoader = () => {
    const body = document.getElementsByTagName("body")
    body[0].style.overflow = "hidden"
    const loader = document.getElementById("loader-container")
    loader.style.display = "flex"
}

const sargapay_hideLoader = () => {
    const body = document.getElementsByTagName("body")
    body[0].style.overflow = ""
    const loader = document.getElementById("loader-container")
    loader.style.display = "none"
}

const sargapay_walletAPI = async(apikey, network, walllet = "nami") => {
    try {
        const addr_p = document.getElementById("pay_add_p_field_tk_plugin")
        const amount_span = document.getElementById("pay_amount_span_field_tk_plugin")

        const address = addr_p.innerText
        const amount = BigInt(amount_span.innerText * 1000000)

        const net = network == 1 ? "Mainnet" : "Testnet"
        const url = `https://cardano-${net.toLowerCase()}.blockfrost.io/api/v0`
        const lucid = await Lucid.new(new Blockfrost(url, apikey), net)

        if (window.cardano[walllet]) {
            sargapay_showLoader()
            const api = await window.cardano[walllet].enable()

            lucid.selectWallet(api)

            const tx = await lucid
                .newTx()
                .payToAddress(address, { lovelace: amount })
                .complete()

            sargapay_hideLoader()

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
            sargapay_hideLoader()
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: noWalletText
            })
        }
    } catch (error) {
        let errorText = unknowText
        sargapay_hideLoader()
        console.log(error)
        if (error.hasOwnProperty("info")) {
            console.log(error.info)
            errorText = error.info
        } else if (error.hasOwnProperty("message")) {
            if (error.message.includes("unreachable") || error.message.includes('Invalid address: Expected address with network'))
                errorText = wrongNetworkText
        }
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: errorText
        })
    }
}



const sargapay_sendAda = async wallet => {
    try {
        // TODO: Get Apikey and Network
        jQuery.ajax({
            type: "post",
            url: wp_ajax_nopriv_sargapay_get_settings_vars.is_user_logged_in == "1" ?
                wp_ajax_sargapay_save_address_vars.ajax_url : wp_ajax_nopriv_sargapay_get_settings_vars.ajax_url,
            data: {
                action: "sargapay_get_settings_vars",
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
                sargapay_walletAPI(response.apikey, response.network, wallet)
            },
        })
    } catch (error) {
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

const sargapay_initHotWallets = () => {
    /**
     *  Wallets Supported
     *  Nami
     *  Flint
     *  Eternl
     */

    if (document.getElementById("hot_wallet_nami")) {
        const btn_nami = document.getElementById("hot_wallet_nami")
        btn_nami.addEventListener("click", async e => await sargapay_sendAda("nami"))
    }

    if (document.getElementById("hot_wallet_flint")) {
        const btn_flint = document.getElementById("hot_wallet_flint")
        btn_flint.addEventListener("click", async e => await sargapay_sendAda("flint"))
    }

    if (document.getElementById("hot_wallet_eternl")) {
        const btn_eternl = document.getElementById("hot_wallet_eternl")
        btn_eternl.addEventListener("click", async e => await sargapay_sendAda("eternl"))
    }
}

sargapay_initHotWallets()