/*WordPress*/
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";

export const fetchSettings = async() => {
    let path = 'sargapay/v1/admin-settings',
        options = {};

    try {
        options = await apiFetch({
            path: path,
            method: 'GET'
        });
        console.log("options sargapay")
        console.dir(options)
    } catch (error) {
        console.log('fetchSettings Errors:', error);
        return {
            sargapay_settings_fetch_settings_errors: true
        }
    }
    return options;
};

export const updateSettings = async(data) => {
    let path = 'sargapay/v1/admin-settings',
        options = {};

    let queryArgs = {
        ...data,
        enabled: data.enabled.localeCompare('yes') == 0 ? 1 : '',
        testmode: data.testmode.localeCompare('yes') == 0 ? 1 : '',
        lightWallets: data.lightWallets.localeCompare('yes') == 0 ? 1 : '',
    }

    delete queryArgs.orders
    delete queryArgs.addrs_count
    delete queryArgs.url

    path = addQueryArgs(path, queryArgs);

    try {
        options = await apiFetch({
            path: path,
            method: 'POST'
        });
        console.log("saved")
        console.dir(options)
        console.log('query')
        console.dir(queryArgs)
    } catch (error) {
        console.log('query')
        console.dir(queryArgs)
        console.log('updateSettings Errors:', error);
        return {
            sargapay_settings_update_settings_errors: true,
        }
    }
    return options;
};