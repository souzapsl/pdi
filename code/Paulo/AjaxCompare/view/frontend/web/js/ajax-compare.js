define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'underscore',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'Magento_Catalog/js/product/view/product-info-resolver',
    'domReady!'
], function (ko, Component, $, modal, $t, _, idsResolver, productInfoResolver) {
    'use strict';
    return Component.extend({
        valueSku: ko.observable(null),
        options: {
            popupWrapperSelector: '#popup-compare-ajax',
            closePopupModal: '.action-close',
            processStart: 'processStart',
            processStop : 'processStop',
            addToCompareButtonSelector: '.tocompare',
            addToCompareSkuButton: '#add-to-compare',
            addToCompareSkuField: '#modal-sku-field',
            addToCompareButtonClass: 'disabled',
            addToCompareTextAdding: '',
            addToCompareButtonTextAdded: '',
            addToCompareButtonTextDefault: '',
            removeFromCompareButton: '.delete-from-compare',
            formKeyInputSelector: 'input[name="form_key"]',
            showLoader: true,
            dividerFiles: 'here',
            processStartAdd: null,
            processStopAdd: null,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            addToCartButtonSelector: '.action.tocart',
            addToCartButtonDisabledClass: 'disabled',
            addToCartButtonTextWhileAdding: '',
            addToCartButtonTextAdded: '',
            addToCartButtonTextDefault: '',
            productInfoResolver: productInfoResolver
        },

        initialize: function () {
            this._super();

            this.valueSku.subscribe(function (value) {
                if (value) {
                    $('#add-to-compare').prop('disabled', false);
                } else {
                    $('#add-to-compare').prop('disabled', true);
                }
            });

            this.init();
        },

        init: function () {
            const self = this;

            $('body').on('click', self.options.removeFromCompareButton, function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.removeFromCompare($(this));
            });

            $('body').on('click', self.options.addToCompareButtonSelector, function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.addCompare($(this));
            });

            $('body').on('click', '.link-compare-list', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.openModalCompare();
            });

            $('body').on('keypress', self.options.addToCompareSkuField, function(e) {
                if (e.keyCode === 13) {
                    self.addCompareSku();
                }
            });

            $('body').on('click', '.add-to-cart-compare', function(e) {
                e.preventDefault();
                let form = $(this).parent('form');
                self.submitForm(form);
            });

        },

        reapplyBindings: function () {
            ko.applyBindings(this, document.querySelector('.comparison'));
        },

        closePopup: function () {
            $(this.options.popupWrapperSelector).fadeOut('slow');
            $(this.options.closePopupModal).trigger('click');
        },

        cleanAreas: function () {
            this.cleanMessages();
            this.cleanSku();
            this.enableButtons();
        },

        enableButtons: function () {
            $(this.options.addToCartButtonSelector).prop('disabled', false);
        },

        cleanMessages: function () {
            $('.compare-messages').html('');
        },

        cleanSku: function () {
            $('#modal-sku-field').val('');
        },

        createMessage: function (message, type) {
            let elementDiv = "" +
                "<div class='message-"+type+" "+type+" message' data-ui-id='message-"+type+"'>" +
                "<div>"+message+"</div>" +
                "</div>";
            $('.compare-messages').html(elementDiv);
        },

        showModalCompare: function() {
            const comparePopup = $(this.options.popupWrapperSelector);
            const modaloption = {
                type: 'popup',
                modalClass: 'modal-popup_ajaxcompare_paulo',
                responsive: true,
                innerScroll: true,
                clickableOverlay: true,
                closed: function () {
                    $('.modal-popup_ajaxcompare_paulo').remove();
                }
            };
            modal(modaloption, comparePopup);
            comparePopup.modal('openModal');
        },

        addCompare: function (el) {
            const self = this,
                comparePopup = $(self.options.popupWrapperSelector),
                body = $('body'),
                parent = el.parent(),
                post = el.data('post'),
                params = post.data;

            if(parent.hasClass(self.options.addToCompareButtonClass)) {
                return;
            }
            $.ajax({
                url: post.action,
                data: params,
                type: 'POST',
                dataType: 'json',
                showLoader: self.options.showLoader,
                beforeSend: function () {
                    self.disableAddToCompareButton(parent);
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStart);
                    }
                },
                success: function (res) {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStop);
                    }
                    if (res.success) {
                        if (!comparePopup.length) {
                            body.append('<div class="popup-compare-ajax" id="popup-compare-ajax">'+res.popup+'</div>');
                        }
                        self.showModalCompare();
                        self.reapplyBindings();
                        self.cleanAreas();
                    }

                    if (res.message) {
                        self.createMessage($t(res.message), res.message_type);
                    }
                }
            }).done(function(){
                self.enableAddToCompareButton(parent);
            });
        },

        openModalCompare: function () {
            const self = this,
                comparePopup = $(self.options.popupWrapperSelector),
                body = $('body');

            $.ajax({
                url: '/ajaxcompare/compare/view',
                type: 'POST',
                dataType: 'json',
                showLoader: self.options.showLoader,
                beforeSend: function () {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStart);
                    }
                },
                success: function (res) {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStop);
                    }
                    if (res.success) {
                        if (!comparePopup.length) {
                            body.append('<div class="popup-compare-ajax" id="popup-compare-ajax">'+res.popup+'</div>');
                        } else {
                            body.find(self.options.popupWrapperSelector).html(res.popup);
                        }
                        self.showModalCompare();
                        self.reapplyBindings();
                        self.cleanAreas();
                    }
                }
            });
        },

        addCompareSku: async function () {
            const self = this,
                comparePopup = $(self.options.popupWrapperSelector),
                sku = $(self.options.addToCompareSkuField).val(),
                body = $('body');
            self.cleanMessages();
            if (sku === '') {
                self.createMessage($t('Fill in the SKU field'), 'error');
                return;
            }

            $.ajax({
                url: '/catalog/product_compare/add/',
                data: {sku : sku},
                type: 'POST',
                dataType: 'json',
                showLoader: self.options.showLoader,
                beforeSend: function () {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStart);
                    }
                },
                success: function (res) {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStop);
                    }
                    if (res.success) {
                        if (!comparePopup.length) {
                            body.append('<div class="popup-compare-ajax" id="popup-compare-ajax">'+res.popup+'</div>');
                        } else {
                            body.find(self.options.popupWrapperSelector).html(res.popup);
                        }
                        self.reapplyBindings();
                        self.cleanAreas();
                    }
                    if (res.message) {
                        self.createMessage($t(res.message), res.message_type);
                    }
                }
            });
        },

        removeFromCompare: function (el) {
            const self = this,
                body = $('body'),
                comparePopup = $(self.options.popupWrapperSelector),
                formKey = $(self.options.formKeyInputSelector).val(),
                post = el.data('post'),
                params = post.data,
                url = post.action;

            if (formKey) {
                params.form_key = formKey;
            }

            $.ajax({
                url: url,
                data: params,
                type: 'POST',
                dataType: 'json',
                showLoader: self.options.showLoader,
                beforeSend: function () {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStart);
                    }
                },
                success: function (res) {
                    if (self.options.showLoader) {
                        body.trigger(self.options.processStop);
                    }
                    if (res.success) {
                        if (!comparePopup.length) {
                            body.append('<div class="popup-compare-ajax" id="popup-compare-ajax">'+res.popup+'</div>');
                        } else {
                            body.find(self.options.popupWrapperSelector).html(res.popup);
                        }
                        if (res.itemsCount) {
                            self.reapplyBindings();
                            self.cleanAreas();
                        }
                    }
                    if (res.message) {
                        self.createMessage($t(res.message), res.message_type);
                    }
                }
            });
        },

        /**
         * @param {String} form
         */
        disableAddToCompareButton: function (form) {
            const addToCompareTextAdding = this.options.addToCompareTextAdding || $t('Adding...'),
                addToCompareButton = $(form).find(this.options.addToCompareButtonSelector);
            addToCompareButton.addClass(this.options.addToCompareButtonClass);
            addToCompareButton.find('span').text(addToCompareTextAdding);
            addToCompareButton.prop('title', addToCompareTextAdding);
        },

        /**
         * @param form
         */
        enableAddToCompareButton: function (form) {
            const self = this,
                addToCompareButtonTextAdded = this.options.addToCompareButtonTextAdded || $t('Added'),
                addToCompareButton = $(form).find(this.options.addToCompareButtonSelector);
            addToCompareButton.find('span').text(addToCompareButtonTextAdded);
            addToCompareButton.attr('title', addToCompareButtonTextAdded);
            setTimeout(function () {
                const addToCompareButtonTextDefault = self.options.addToCompareButtonTextDefault || $t('Add to Compare');
                addToCompareButton.removeClass(self.options.addToCompareButtonClass);
                addToCompareButton.find('span').text(addToCompareButtonTextDefault);
                addToCompareButton.prop('title', addToCompareButtonTextDefault);
            }, 1000);
        },

        _redirect: function (url) {
            var urlParts, locationParts, forceReload;

            urlParts = url.split('#');
            locationParts = window.location.href.split('#');
            forceReload = urlParts[0] === locationParts[0];

            window.location.assign(url);

            if (forceReload) {
                window.location.reload();
            }
        },

        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStartAdd && this.options.processStopAdd;
        },

        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        submitForm: function (form) {
            this.ajaxSubmit(form);
        },

        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                productIds = idsResolver(form),
                productInfo = self.options.productInfoResolver(form),
                formData;

            $(self.options.minicartSelector).trigger('contentLoading');
            self.disableAddToCartButton(form);
            formData = new FormData(form[0]);

            $.ajax({
                url: form.prop('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,

                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStartAdd);
                    }
                },

                /** @inheritdoc */
                success: function (res) {
                    var eventData, parameters;

                    $(document).trigger('ajax:addToCart', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });

                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStopAdd);
                    }

                    if (res.backUrl) {
                        eventData = {
                            'form': form,
                            'redirectParameters': []
                        };
                        // trigger global event, so other modules will be able add parameters to redirect url
                        $('body').trigger('catalogCategoryAddToCartRedirect', eventData);

                        if (eventData.redirectParameters.length > 0 &&
                            window.location.href.split(/[?#]/)[0] === res.backUrl
                        ) {
                            parameters = res.backUrl.split('#');
                            parameters.push(eventData.redirectParameters.join('&'));
                            res.backUrl = parameters.join('#');
                        }

                        self._redirect(res.backUrl);

                        return;
                    }

                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(res.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);
                },

                /** @inheritdoc */
                error: function (res) {
                    $(document).trigger('ajax:addToCart:error', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });
                },

                /** @inheritdoc */
                complete: function (res) {
                    if (res.state() === 'rejected') {
                        location.reload();
                    }
                }
            });
        },

        /**
         * @param {String} form
         */
        disableAddToCartButton: function (form) {
            var addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $t('Adding...'),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
            addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
            addToCartButton.prop('title', addToCartButtonTextWhileAdding);
        },

        /**
         * @param {String} form
         */
        enableAddToCartButton: function (form) {
            var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $t('Added'),
                self = this,
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.find('span').text(addToCartButtonTextAdded);
            addToCartButton.prop('title', addToCartButtonTextAdded);

            setTimeout(function () {
                var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $t('Add to Cart');

                addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                addToCartButton.find('span').text(addToCartButtonTextDefault);
                addToCartButton.prop('title', addToCartButtonTextDefault);
            }, 1000);
        },

    });
});
