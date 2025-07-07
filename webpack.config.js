/**
 * WordPress Dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
    '@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
    '@woocommerce/settings': ['wc', 'wcSettings']
};

const wcHandleMap = {
    '@woocommerce/blocks-registry': 'wc-blocks-registry',
    '@woocommerce/settings': 'wc-settings'
};

const requestToExternal = (request) => {
    if (wcDepMap[request]) {
        return wcDepMap[request];
    }
};

const requestToHandle = (request) => {
    if (wcHandleMap[request]) {
        return wcHandleMap[request];
    }
};

module.exports = {
    ...defaultConfig,
    ... {
        output: {
            path: path.resolve(__dirname, 'assets/js'),
            filename: '[name].js',
        },
        plugins: [
            ...defaultConfig.plugins.filter(
                (plugin) =>
                    plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
            ),
            new WooCommerceDependencyExtractionWebpackPlugin({
                requestToExternal,
                requestToHandle
            })
        ],
        // Add any overrides to the default here.        
        experiments: {
            // WebAssembly as async module (Proposal)       
            asyncWebAssembly: true,
            // Allow to use await on module evaluation (Proposal)
            topLevelAwait: true,
            layers: true,
        }
    }
}