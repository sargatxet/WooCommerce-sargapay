import { generate_payment_address } from './gen_address.js';

window.onload = function() {
    const btn = document.getElementById("genButton");
    if (btn) {
        btn.addEventListener("click", function(event) {
            event.preventDefault();
            const selected_option = document.getElementById('num_address');
            //validate the seleccted option value is a number
            if (!isNaN(selected_option.value)) {
                //show loading
                show_loader();
                /* Set Amount of address to be generated
                 * if option value is more than 400 the value 
                 * will be set to 400 to prevent the browser to hang*/
                const address_amount = selected_option.value > 400 ? 400 : selected_option.value;
                //Generate pay addresses for network enabled
                ajax_gen_address(address_amount);
            }
        });
    }
    //Generate Check Button 
    const input = document.getElementById('woocommerce_tk-ada-pay-plugin_mpk');
    if (input) {
        let pay_address_mainet = null;
        let pay_address_testnet = null;
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
            button.textContent = 'Probar La Llave Publica';
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
                pay_address_mainet = generate_payment_address(xpub, index_value, 1, 1);
                //Generate Payment Address Testnet
                pay_address_testnet = generate_payment_address(xpub, index_value, 1, 0);
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


function ajax_gen_address(num_address) {
    jQuery.ajax({
        type: "post",
        url: wp_ajax_save_address_vars.ajax_url,
        data: {
            action: "save_address",
            action_type: "get_xpub"
        },
        error: function(response) {
            console.log(response);
            hide_loader();
        },
        success: function(response) {
            // Get xpub and data needed to generate payment addresses            
            let lastIndex = response.last_unused;
            if (lastIndex == null) {
                lastIndex = 0;
            } else if (lastIndex != 0) {
                lastIndex = parseInt(lastIndex) + 1;
            }
            const xpub = response.xpub;
            const testnet = response.network;
            const addresses = generate_payment_address(xpub, lastIndex, num_address, testnet);
            if (addresses.length > 0 && !addresses[0].includes("Error:")) {
                jQuery.ajax({
                    type: "post",
                    url: wp_ajax_save_address_vars.ajax_url,
                    data: {
                        action: "save_address",
                        addresses: addresses,
                        action_type: "save_address"
                    },
                    error: function(response) {
                        console.log(response);
                        //hide loading
                        hide_loader();
                    },
                    success: function(response) {
                        //hide loading
                        hide_loader();
                        alert(response);
                    }
                });
            } else {
                alert(addresses);
            }
        }
    });
}

function show_loader() {
    const button = document.getElementById("genButton");
    button.disabled = true;
    if (!document.getElementById('loader_cardano')) {
        //Create Loading
        const div = document.createElement("div");
        div.id = 'loader_cardano';
        div.style.position = 'absolute';
        div.style.zIndex = "1000";
        div.style.marginLeft = '40%';
        div.style.border = '16px solid #b3b3cc';
        div.style.borderRadius = '50%';
        div.style.borderTop = '16px solid #3498db';
        div.style.width = '50px';
        div.style.height = '50px';
        div.style.animation = 'spin 2s linear infinite';
        div.animate([
            // keyframes    
            { transform: 'rotate(0deg)' },
            { transform: 'rotate(360deg)' }
        ], {
            // timing options
            duration: 2000,
            iterations: Infinity
        });
        button.after(div);
    }
}

function hide_loader() {
    const button = document.getElementById("genButton");
    button.disabled = false;
    const div = document.getElementById("loader_cardano");
    div.remove();
}