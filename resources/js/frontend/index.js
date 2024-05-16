

import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting('sargapay_cardano_data', {});

const defaultLabel = 'SargaPay';

const label = decodeEntities(settings.title) || defaultLabel;

/**
 * ICon Component
 */
const Icon = () => {
	return settings.icon
		? <img src={settings.icon} style={{ float: 'right', marginRight: '20px' }} />
		: ''
}

/**
 * Content component
 */
const Content = () => {
	return decodeEntities(settings.description || '');
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = () => {
	return (
        <span style={{ width: '100%' }}>
            {label}
            <Icon />
        </span>
    )
}

/**
 * SaRGA payment method config object.
 */
const SARGAPAY = {
	name: "sargapay_cardano",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod(SARGAPAY);