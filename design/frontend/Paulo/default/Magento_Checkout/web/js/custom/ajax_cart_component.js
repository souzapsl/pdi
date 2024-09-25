define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'domReady!'
], function($, ko, Component, urlBuilder, defaultProcessor ) {
    "use strict";

    return Component.extend({
        initialize: function () {
            this._super();
            this.init();
            return this;
        },

        incrementCart: function (data, event, itemId) {
            event.preventDefault();
            if (event.type === 'click') {
                let fieldQty = $('#cart-'+itemId+'-qty');
                let trashQty = $('#trash-'+itemId+'-qty');
                let fieldDecrement = $('#decrement-'+itemId+'-qty');
                let inputValue = parseInt(fieldQty.val());
                let inputMax = 50;
                let incrementValue = inputValue + 1;
                if (inputValue >= 0 && inputValue < inputMax) {
                    fieldQty.val(incrementValue);
                    this.changeElement(fieldDecrement, 'show');
                    this.changeElement(trashQty, 'hide');
                    this.ajaxCartUpdate();
                }
            }
        },

        decrementCart: function (data, event, itemId) {
            event.preventDefault();
            if (event.type === 'click') {
                let fieldQty = $('#cart-'+itemId+'-qty');
                let trashQty = $('#trash-'+itemId+'-qty');
                let fieldDecrement = $('#decrement-'+itemId+'-qty');
                let inputValue = parseInt(fieldQty.val());
                let inputMin = isNaN(parseInt(fieldQty.attr("min"))) ? 1 : parseInt(fieldQty.attr("min"));
                if (!((inputValue -1) < inputMin)) {
                    let decrementValue = inputValue -1;
                    fieldQty.val(decrementValue);
                    this.ajaxCartUpdate();
                    if (decrementValue === 1) {
                        this.changeElement(fieldDecrement, 'hide');
                        this.changeElement(trashQty, 'show');
                    }
                }
            }
        },

        deleteItem: function (data, event, itemId) {
            event.preventDefault();
            event.stopPropagation();
            if (event.type === 'click') {
                let trashQty = $('#trash-'+itemId+'-qty');
                let divRemove =  trashQty.parents('tbody.cart.item');
                let dataPost = JSON.parse(trashQty.attr('data-post'));
                let form = $('#form-validate');
                let formKey = form.find("input[name='form_key']").val();
                let data = dataPost.data;
                data.form_key = formKey;
                $.ajax({
                    url: dataPost.action,
                    data: data,
                    showLoader: true,
                    method: "POST"
                }).done(function (res) {
                    if (res.success) {
                        divRemove.remove();
                        if (res.itemsCount) {
                            defaultProcessor.estimateTotals({});
                        } else {
                            window.location.href = res.url;
                        }
                    }else{
                        this.validateCartMessage();
                    }
                });
                return false;
            }
        },

        ajaxCartUpdate: function() {
            const pathName = window.location.pathname;
            let self = this;
            if (pathName.includes('checkout/cart')) {
                const form = $('#form-validate');
                const cartUrl = '/checkout/cart/';
                $.ajax({
                    url: '/checkout/cart/updateItemQty/',
                    method: "POST",
                    data: form.serialize(),
                    showLoader: true,
                    success: function (res) {
                        if (res.success) {
                            $.ajax({
                                url: form.attr('action'),
                                method: "POST",
                                data: form.serialize(),
                                showLoader: true,
                                success: function (res) {
                                    if (res.success) {
                                        res.items.forEach((item) => {
                                            let childId = $(`#cart-${item.id}-qty`);
                                            childId.parents('tr.item-info').find('td.col.price').find('.price').html(item.price);
                                            childId.parents('tr.item-info').find('td.col.subtotal').find('.price').html(item.subtotal);
                                        });
                                        defaultProcessor.estimateTotals({});
                                        self.validateCartMessage();
                                    } else {
                                        self.redirectTo(res.url);
                                    }
                                },
                                error: function () {
                                    self.redirectTo(cartUrl);
                                }
                            });
                        } else {
                            self.validateCartMessage();
                            setTimeout(() => {
                                self.redirectTo(cartUrl);
                            }, 3000);
                        }
                    },
                    error: function () {
                        self.redirectTo(cartUrl);
                    }
                });
            }
        },

        validateCartMessage: function () {
            let parentElement = $('.page .messages');
            parentElement.html('');
            setTimeout(() => {
                urlBuilder.setBaseUrl(BASE_URL);
                $.ajax({
                    url: urlBuilder.build('customcart/cart/validatecartmessages'),
                    type: 'GET',
                    dataType: "JSON",
                    cache: false,
                    async: false,
                }).done(function (res) {
                    let messages = res.data.messages
                    messages = messages[0]
                    messages.forEach(function (message) {
                        if (typeof message === 'string' && message.length) {
                            parentElement.append(
                                "<div data-placeholder='messages'>" +
                                "<div class='message-error error message' data-ui-id='message-error'>" +
                                "<div>"+message+"</div>" +
                                "</div>" +
                                "</div>"
                            );
                        }
                    })
                });
            }, 1000);
        },

        redirectTo: function(cartUrl) {
            window.location.href = cartUrl;
        },

        updateChange: function () {
            let self = this;
            $(document).on('change', '.qty-item', function (event) {
                let fieldQty = $(this);
                let itemId = fieldQty.attr('data-item-id');
                let trashQty = $('#trash-'+itemId+'-qty');
                let fieldDecrement = $('#decrement-'+itemId+'-qty');

                if (event.currentTarget.value <= 0) {
                    event.currentTarget.value = 1
                }
                event.currentTarget.value = event.currentTarget.value.replace(/[^0-9]/g, '');
                if (event.currentTarget.value === '1') {
                    self.changeElement(fieldDecrement, 'hide');
                    self.changeElement(trashQty, 'show');
                } else {
                    self.changeElement(fieldDecrement, 'show');
                    self.changeElement(trashQty, 'hide');
                }
                self.ajaxCartUpdate();
            });
        },

        changeElement: function (element, action) {
            if (action === 'hide') {
                element.hide();
            }
            if (action === 'show') {
                element.show();
            }
        },

        updateKeydown: function () {
            let self = this;
            $(document).on('keydown', '.qty-item', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    event.stopPropagation();
                    setTimeout(() => {
                        let fieldQty = $(this);
                        let itemId = fieldQty.attr('data-item-id');
                        let trashQty = $('#trash-'+itemId+'-qty');
                        let fieldDecrement = $('#decrement-'+itemId+'-qty');
                        if (fieldQty.is(':focus')) {
                            if (event.currentTarget.value <= 0) {
                                event.currentTarget.value = 1
                            }
                            if (event.currentTarget.value === '1') {
                                self.changeElement(fieldDecrement, 'hide');
                                self.changeElement(trashQty, 'show');
                            } else {
                                self.changeElement(fieldDecrement, 'show');
                                self.changeElement(trashQty, 'hide');
                            }
                            self.ajaxCartUpdate();
                            fieldQty.blur();
                        }
                    }, 500);
                }
                let keys = ['Backspace', 'Tab', 'Enter'];
                if ((event.key.match(/[^0-9]/g) && keys.indexOf(event.key) === -1)) {
                    return event.preventDefault()
                }
            });
        },

        init: function() {
            this.updateChange();
            this.updateKeydown();
        }
    });
});
