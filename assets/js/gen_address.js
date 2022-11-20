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
import * as Cardano from "./cardano-serialization-lib-asmjs/cardano_serialization_lib.js"
import { bech32 } from "./bech32.js"
import { Buffer } from "./buffer-es6/index.js"

export const sargapay_generate_payment_address = (
    xpub,
    lastindex,
    num_address,
    network_param
) => {
    const result = []
        // Testnet = 0 Mainnet = 1
    const network =
        network_param == 0 ?
        Cardano.NetworkInfo.testnet().network_id() :
        Cardano.NetworkInfo.mainnet().network_id()
    try {
        // public key yoroi format
        var xpub_bech = Cardano.Bip32PublicKey.from_bytes(
            Buffer.from(xpub, "hex")
        ).to_bech32()
    } catch (error) {
        // public key xpub format
        if (xpub.startsWith("xpub")) {
            xpub_bech = xpub
        } else {
            /* decode the public key using bech32 and encoded with
             * xpub header to be used in cardano-serialization-lib */
            try {
                const decode_xpub = bech32.decode(xpub, 150)
                xpub_bech = bech32.encode("xpub", decode_xpub.words, 150)
            } catch (error) {
                xpub_bech = null
                result.push(error)
            }
        }
    }
    if (xpub_bech !== null) {
        const max_address = parseInt(lastindex) + parseInt(num_address)
        const init_index = parseInt(lastindex)
        const accountKey = Cardano.Bip32PublicKey.from_bech32(xpub_bech)
        for (let i = init_index; i < max_address; i++) {
            // derive the public key to generate the payment address
            const utxoPubKey = accountKey.derive(0).derive(i)
                // Generate Stake Key
            const stakeKey = accountKey.derive(2).derive(0)
                // Generate Enterprise Address
            const baseAddr = Cardano.BaseAddress.new(
                    network,
                    Cardano.StakeCredential.from_keyhash(utxoPubKey.to_raw_key().hash()),
                    Cardano.StakeCredential.from_keyhash(stakeKey.to_raw_key().hash())
                )
                // add generated payaddress in bech32 format
            result.push(baseAddr.to_address().to_bech32())
        }
    }
    return result
}