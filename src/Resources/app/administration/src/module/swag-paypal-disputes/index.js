import './acl';
import './page/swag-paypal-disputes-list';

const { Module } = Shopware;

Module.register('swag-paypal-disputes', {
    type: 'plugin',
    name: 'paypal-disputes',
    title: 'swag-paypal-disputes.general.mainMenuItemGeneral',
    description: 'swag-paypal-disputes.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#F88962',
    icon: 'default-avatar-multiple',
    favicon: 'icon-module-customers.png',

    routes: {
        index: {
            component: 'swag-paypal-disputes-list',
            path: 'index',
            meta: {
                privilege: 'swag_paypal_disputes.viewer'
            }
        },

        detail: {
            component: 'swag-paypal-disputes-detail',
            path: 'detail/:id',
            props: { default(route) {
                return { disputeId: route.params.id };
            } },
            redirect: {
                name: 'swag.paypal.disputes.detail'
            },
            meta: {
                privilege: 'swag_paypal_disputes.viewer'
            }
        }
    },

    navigation: [{
        id: 'swag-paypal-disputes',
        path: 'swag.paypal.disputes.index',
        label: 'swag-paypal-disputes.general.mainMenuItemGeneral',
        parent: 'sw-customer',
        privilege: 'swag_paypal_disputes.viewer'
    }]
});
