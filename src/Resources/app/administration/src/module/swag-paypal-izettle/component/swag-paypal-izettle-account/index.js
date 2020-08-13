import template from './swag-paypal-izettle-account.html.twig';
import './swag-paypal-izettle-account.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-account', {
    template,

    inject: [
        'SwagPayPalIZettleSettingApiService',
        'repositoryFactory'
    ],

    props: {
        salesChannel: {
            type: Object,
            require: true
        }
    },

    data() {
        return {
            isLoading: false,
            merchantInfo: null,
            lastRun: null
        };
    },

    computed: {
        accountName() {
            if (!this.merchantInfo) {
                const firstName = this.$tc('swag-paypal-izettle.wizard.connectionSuccess.fakeFirstName');
                const lastName = this.$tc('swag-paypal-izettle.wizard.connectionSuccess.fakeLastName');

                return `${firstName} ${lastName}`;
            }

            return this.merchantInfo.name;
        },

        runRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            Promise.all([
                this.loadMerchantData(),
                this.loadLastRun()
            ]).then(() => {
                this.isLoading = false;
            });
        },

        loadMerchantData() {
            return this.SwagPayPalIZettleSettingApiService.fetchInformation(this.salesChannel)
                .then(({ merchantInformation }) => {
                    this.merchantInfo = merchantInformation;
                });
        },

        loadLastRun() {
            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('finishedAt', null)]));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));
            criteria.setLimit(1);

            return this.runRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.lastRun = result.first();
                this.$forceUpdate();
            });
        }
    }
});
