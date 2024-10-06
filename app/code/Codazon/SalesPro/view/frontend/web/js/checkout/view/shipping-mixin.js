/**
 * @copyright  Codazon. All rights reserved.
 * @author     Nicolas
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Magento_Checkout/js/model/step-navigator',
    'mage/translate',
    'ko',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/action/set-payment-information',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-save-processor/payload-extender',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, quote, registry, stepNavigator, $t, ko, checkoutDataResolver, addressConverter, getPaymentInformation, checkoutData, addressList,
    createShippingAddress, selectShippingAddress, createBillingAddress, selectBillingAddress, setShippingInformationAction, setPaymentInformationAction,
    storage, payloadExtender, paymentService, customer, resourceUrlManager, methodConverter, fullScreenLoader, errorProcessor
) {
    'use strict';
    var widget;
    return function (Component) {
        return Component.extend({
            defaults: {
                template: 'Codazon_SalesPro/checkout/shipping'
            },
            initialize: function () {
                var self = this;
                this.ingoreValidationMessage = true;
                this._adjustFunctions();
                this._super();
                widget = this;
                this._prepareData();
                return this;
            },
            _prepareData: function() {
                var self = this;
                $(window).on('refreshShippingInfomation', function () {
                    widget.setShippingInformation();
                });
                this.prepareFormEvents();
                
            },
            _adjustFunctions: function () {
                stepNavigator.setHash = function (hash) {
                    window.location.hash = '';
                };
                stepNavigator.oldIsProcessed = stepNavigator.isProcessed;
                stepNavigator.isProcessed = function (code) {
                    if (code == 'shipping') {
                        return true;
                    } else {
                        stepNavigator.oldIsProcessed(code);
                    }
                }
            },
            /* visible: function() {
                return (!quote.isVirtual());
            }, */
            canDisplayed: function() {
                return (!quote.isVirtual());
            },
            selectShippingMethod: function (shippingMethod) {
                this._super(shippingMethod);
                widget.setShippingInformation();
                return true;
            },
            hasShippingMethod: function () {
                return window.checkoutConfig.selectedShippingMethod !== null;
            },
            saveNewAddress: function() {
                this._super();
                widget.setShippingInformation();
            },
            collectAddress: function () {
                this.source.set('params.invalid', false);
                var addressData = this.source.get('shippingAddress');
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;
                var newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                if (!quote.billingAddress()) {
                    selectBillingAddress(createBillingAddress(addressData));
                }
                this.resolveBillingAddress(newShippingAddress);
                return this.updatePaymentInformation(true);
            },
            resolveBillingAddress: function(shippingAddress) {
                quote.billingAddress(null);
                checkoutDataResolver.resolveBillingAddress();
                var bA = quote.billingAddress(); bA = bA ? bA : quote.shippingAddress();
                bA = bA ? bA : {};
                if (!bA.countryId) {
                    bA.countryId = shippingAddress.countryId;
                    if (bA.countryId == undefined) bA.countryId = '';
                }
                if (!bA.regionCode) {
                    bA.regionCode = shippingAddress.regionCode;
                    if (bA.regionCode == undefined) bA.regionCode = '';
                }
                if (!bA.region) bA.region = shippingAddress.region;
                quote.billingAddress(bA);
            },
            getCheckoutMethod: function () {
                return customer.isLoggedIn() ? 'customer' : 'guest';
            },
            getCdzUrlForSetShippingInformation: function (quote) {
                var params = this.getCheckoutMethod() == 'guest' ? { cartId: quote.getQuoteId() } : {},
                urls = {
                    'guest': '/guest-carts/:cartId/cdz-shipping-information',
                    'customer': '/carts/mine/cdz-shipping-information'
                };
                return resourceUrlManager.getUrl(urls, params);
            },
            updatePaymentInformation: function (notResolve) {
                if (!quote.shippingMethod()) {
                    if (!notResolve) this.resolveBillingAddress(quote.shippingAddress());
                    registry.async('checkoutProvider')(function (checkoutProvider) {
                        var shippingAddressData = checkoutData.getShippingAddressFromData();
                        if (shippingAddressData) {
                            checkoutProvider.set(
                                'shippingAddress',
                                $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                            );
                        }
                    });
                    var payload = {
                        addressInformation: {
                            'shipping_address': quote.shippingAddress(),
                            'billing_address': quote.billingAddress()
                            /* 'shipping_method_code': quote.shippingMethod()['method_code'],
                            'shipping_carrier_code': quote.shippingMethod()['carrier_code'] */
                        }
                    };
                    payloadExtender(payload);
                    fullScreenLoader.startLoader();
                    return storage.post(
                        this.getCdzUrlForSetShippingInformation(quote),
                        JSON.stringify(payload)
                    ).done(
                        function (response) {
                            quote.setTotals(response.totals);
                            paymentService.setPaymentMethods(methodConverter(response['payment_methods']));
                            fullScreenLoader.stopLoader();
                        }
                    ).fail(
                        function (response) {
                            errorProcessor.process(response);
                            fullScreenLoader.stopLoader();
                        }
                    );
                }
            },
            prepareFormEvents: function() {
                var self = this, t, rfs = () => $(window).trigger('refreshShippingInfomation'), refreshShipInfo = () => {
                    if (t) clearTimeout(t);
                    t = setTimeout(() => {
                        if (!$('.action-select-shipping-item').length) {
                            if (!widget.validateShippingInformation() || !quote.shippingMethod()) {
                                let cl = widget.collectAddress();
                                cl ? cl.done(rfs) : rfs();
                            } else {
                                rfs();
                            }
                        } else {
                            rfs();
                        }
                    }, 100);
                };
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    if (self.visible()) {
                        var it = setInterval(function() {
                            var $shippingForm = $('#co-shipping-form');
                            if ($shippingForm.length) {
                                self.updatePaymentInformation();
                                clearInterval(it);
                                $('form[data-role=email-with-possible-login] input[name=username], .opc #shipping [name=username]').on('change', refreshShipInfo);
                                $shippingForm.on('change', 'input,select', refreshShipInfo);
                                $('body').on('click','.new-shipping-address-modal .action-save-address', self.updatePaymentInformation.bind(self));
                            }
                        }, 100);
                    }
                });
            },
            validateShippingInformation: function() {
                if (window.noValidateShippingAddress) {
                    return true;
                } else {
                    return this._super();
                }
            }
        });
    };
});