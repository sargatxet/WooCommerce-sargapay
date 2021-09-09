import { generate_payment_address } from './gen_address.js';

window.onload = () => wp_gen();

copy_text();

function copy_text() {
    // Copy Address
    if (document.getElementById('pay_add_p_field_tk_plugin') &&
        document.getElementById('pay_add_button_field_tk_plugin')) {
        const button = document.getElementById('pay_add_button_field_tk_plugin');
        button.addEventListener("click", function(e) {
            var text = jQuery("#pay_add_p_field_tk_plugin").get(0);
            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(text);
            selection.removeAllRanges();
            selection.addRange(range);
            document.execCommand("copy");
            const modal = document.getElementById("copy_modal");
            const span = document.getElementsByClassName("close_tk_plugin")[0];
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
            var text = jQuery("#pay_amount_span_field_tk_plugin").get(0);
            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(text);
            selection.removeAllRanges();
            selection.addRange(range);
            document.execCommand("copy");
            const modal = document.getElementById("copy_modal_amount");
            const span = document.getElementsByClassName("close_tk_plugin")[1];
            modal.style.display = "block";
            span.onclick = () => modal.style.display = "none";
            window.onclick = (event) => {
                if (event.target == modal) modal.style.display = "none";
            }
        });
    }
}


function wp_gen() {
    //Get How many addresses have left
    const unused = null;
    jQuery.ajax({
        type: "post",
        url: wp_ajax_save_address_vars.ajax_url,
        data: {
            action: "save_address",
            action_type: "get_unused"
        },
        error: function(response) {
            console.log(response);
        },
        success: function(response) {
            const unused = response.unused;
            const xpub = response.xpub;
            const testnet = response.network;
            let lastIndex = response.last_unused;
            // No address ever generated
            if (lastIndex === null) {
                lastIndex = 0;
            } else {
                lastIndex = parseInt(response.last_unused)
                if (lastIndex === 0) {
                    // first address generated
                    lastIndex = 1;
                } else {
                    // more than one addresses were generated
                    lastIndex += 1;
                }
            }
            // IF you have less than 20 unused address you will generate a new one            
            if (parseInt(unused) < 20) {
                wp_add_index(xpub, lastIndex, testnet);
            }
        }
    });
}

function wp_add_index(xpub, lastIndex, testnet) {
    // Generate New Address
    const address = generate_payment_address(xpub, lastIndex, 1, testnet);
    // Save New Address on DB
    if (address.length > 0 && !address[0].includes("Error:")) {
        jQuery.ajax({
            type: "post",
            url: wp_ajax_save_address_vars.ajax_url,
            data: {
                action: "save_address",
                addresses: address,
                action_type: "save_address"
            }
        });
    }
}