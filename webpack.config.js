/**
 * WordPress Dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    ... {
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