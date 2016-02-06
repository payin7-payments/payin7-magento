var Payin7Payments = {

    cfg: null,
    lastOrderData: null,

    initialize: function (cfg) {
        this.cfg = this.cfg || (cfg || {});

        var me = this;

        jQuery(function ($) {
            $('.link-finalize-payin7-order').click(function () {
                var mme = $(this);

                if (!mme.hasClass('op')) {
                    mme.addClass('op').removeClass('blink');

                    me.finalizeOrder({
                        orderId: mme.attr('data-order-id'),
                        resubmit: true
                    }, {
                        dialog: {
                            canClose: true,
                            confirmCloseCancel: false,
                            animated: true
                        }
                    }, function () {
                        mme.removeClass('op')
                            .addClass('blink');
                    });
                }
            });
        });
    },

    _log: function (msg) {
        if (!this._isDebug()) {
            return;
        }

        console.log(msg);
    },

    _showMessage: function (msg, msgType, callback) {
        msgType = msgType || 'info';

        msg = (msgType == 'err' ? 'Error: ' : '') + msg;

        alert(msg);

        if (callback != undefined) {
            callback();
        }
    },

    _redirect: function (url) {
        this._log('Redirecting to: ' + url);
        window.location = url;
    },

    _isDebug: function () {
        return this.cfg['debug'] || false;
    },

    _isSandbox: function () {
        return this.cfg['sandbox'] || false;
    },

    _locaLApiUrl: function (method) {
        return this.cfg['localApiUrl'] + method;
    },

    _payin7ApiUrl: function (method) {
        return this.cfg['payin7ApiUrl'] + method;
    },

    _get: function (url, params, callback) {
        return this._ajx(url, 'GET', params, callback);
    },

    _post: function (url, params, callback) {
        return this._ajx(url, 'POST', params, callback);
    },

    _ajx: function (url, method, params, callback) {

        var extraParams = {};
        var me = this;

        jQuery.ajax(url, {
            type: method,
            dataType: 'json',
            data: jQuery.extend(extraParams, params)
        }).done(function (data) {

            var success = data['success'] || false;
            var response = data['response'] || {};
            var errorCode = (data['error'] != undefined ? data['error']['code'] : null);
            var errorMessage = (data['error'] != undefined ? data['error']['message'] : null);
            var redirectUrl = data['redirect_url'] || null;

            if (callback != undefined) {
                callback(success,
                    response,
                    errorMessage,
                    errorCode);
            }

            if (!success && redirectUrl) {
                if (errorMessage) {
                    me._showMessage(errorMessage, 'err', function () {
                        me._redirect(redirectUrl);
                    });
                } else {
                    me._redirect(redirectUrl);
                }
            }
        }).fail(function (response, a, b) {

            if (callback != undefined) {
                callback(false, null, b);
            }
        });
    },

    _isMobileBrowser: function () {
        var check = false;
        (function (a) {
            if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) {
                check = true;
            }
        })(navigator.userAgent || navigator.vendor || window.opera);
        return check;
    },

    _isTabletBrowser: function () {
        var check = false;
        (function (a) {
            if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) {
                check = true;
            }
        })(navigator.userAgent || navigator.vendor || window.opera);
        return check;
    },

    _isIE: function () {
        var myNav = navigator.userAgent.toLowerCase();
        return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
    },

    _supportsSvg: function () {
        return document.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#BasicStructure', '1.1');
    },

    _windowProxy: null,

    _closeFancy: function () {
        jQuery('.fancybox-overlay').remove();
        jQuery.fancybox.close(true);
    },

    _windowProxyCallback: function (event) {
        // TODO: validate origin here

        var me = Payin7Payments;
        var orderData = me.lastOrderData;
        var isCheckout = orderData['isCheckout'] || false;

        if (orderData) {
            var d = event.data;
            me._log(d.action);
            me._log(d.data);

            if (d.action == 'cancel_order') {
                if (orderData['cancelUrl'] !== undefined) {
                    jQuery.fancybox.showLoading();
                    me._redirect(orderData['cancelUrl']);
                } else {
                    me._closeFancy();
                }
            } else if (d.action == 'complete_order') {
                if (orderData['completeUrl'] !== undefined) {
                    jQuery.fancybox.showLoading();
                    me._redirect(orderData['completeUrl']);
                } else {
                    if (isCheckout) {
                        me._closeFancy();
                    } else {
                        jQuery.fancybox.showLoading();

                        me._post(me._locaLApiUrl('markcomplete'), {
                            order_id: orderData['orderId']
                        }, function (success, response, errMessage, errCode) {
                            jQuery.fancybox.hideLoading();

                            if (success) {
                                window.location.reload();
                            } else {
                                me._showMessage(errMessage);
                                me._closeFancy();
                            }
                        });
                    }
                }
            } else if (d.action == 'retry_payment') {
                //
            }
        }
    },

    _attachRemoteBindings: function (orderData, cfg) {
        if (!this._windowProxy) {
            this._windowProxy = new Porthole.WindowProxy(
                cfg['origin'] + 'proxy.html', cfg['iframeName']);

            this._windowProxy.addEventListener(this._windowProxyCallback);
        }
    },

    _detachRemoteBindings: function () {
        if (this._windowProxy) {
            this._windowProxy.removeEventListener(this._windowProxyCallback);
            this._windowProxy = null;
        }
    },

    _showOrderCompletionDialog: function (orderData, options, callback) {
        var animated = options && options['animated'] !== undefined ? options['animated'] : true;
        var canClose = options && options['canClose'] !== undefined ? options['canClose'] : true;
        var confirmCloseCancel = options && options['confirmCloseCancel'] !== undefined ? options['confirmCloseCancel'] : false;
        var cancelOnMaskClick = options && options['cancelOnMaskClick'] !== undefined ? options['cancelOnMaskClick'] : true;
        var title = options ? (options['title'] || this.cfg['orders']['frameTitle'] || null) : null;

        var isMobile = this._isMobileBrowser();
        var isTablet = this._isTabletBrowser();

        var margin = (isMobile || isTablet ? 0 : undefined);
        var width = (isMobile || isTablet ? '100%' : undefined);
        var height = (isMobile || isTablet ? '100%' : undefined);

        var parser = document.createElement('a');
        parser.href = orderData['orderUrl'];
        var orderOriginUrl = parser.protocol + '//' + parser.hostname + (parser.port ? ':' + parser.port : '') + '/';

        var me = this;
        var _canCloseConfirm = false;

        this.lastOrderData = orderData;

        jQuery.fancybox({
            arrows: false,
            closeBtn: canClose,
            padding: 0,
            margin: margin,
            width: width,
            height: height,
            scrollOutside: false,
            openEffect: (animated ? 'fade' : 'none'),
            closeEffect: (animated ? 'fade' : 'none'),
            openSpeed: (animated ? null : 'fast'),
            openOpacity: (animated ? null : false),
            closeOpacity: (animated ? null : false),
            modal: !canClose,
            wrapCSS: 'payin7-fancybox-wrapper',
            type: 'iframe',
            href: orderData['orderUrl'],
            title: (this.cfg['debug'] ? '<button class="dbg" onclick="jQuery(\'.fancybox-iframe\').attr( \'src\', function ( i, val ) { return val; });">reload</button>' : null),
            iframe: {
                scrolling: 'auto',
                preload: false
            },
            helpers: {
                overlay: {
                    closeClick: cancelOnMaskClick,
                    speedOut: 200,
                    showEarly: false,
                    locked: true
                }
            },
            beforeClose: function () {
                if (confirmCloseCancel) {
                    if (!_canCloseConfirm) {
                        swal({
                                title: 'Are you sure?',
                                text: 'The order will be cancelled and you will be taken back to "Checkout"',
                                type: 'warning',
                                showCancelButton: true,
                                confirmButtonClass: "btn-danger",
                                confirmButtonText: "Yes",
                                cancelButtonText: "No",
                                closeOnConfirm: true,
                                closeOnCancel: true
                            },
                            function (isConfirm) {
                                if (isConfirm) {
                                    _canCloseConfirm = true;
                                    jQuery.fancybox.close(true);
                                    me._redirect(orderData['cancelUrl']);
                                }
                            });

                        return false;
                    }
                }
            },
            beforeShow: function () {
                if (!me.cfg['debug']) {
                    jQuery("body *:not(.fancybox-overlay, .fancybox-overlay *)").addClass("payin7-overlay-blur");
                }
            },
            afterShow: function () {
                me._attachRemoteBindings(orderData, {
                    origin: orderOriginUrl,
                    iframeName: jQuery('.fancybox-inner iframe').attr('name')
                });
            },
            afterClose: function () {
                me.lastOrderData = null;
                me._detachRemoteBindings();

                if (!me.cfg['debug']) {
                    jQuery("body *:not(.fancybox-overlay, .fancybox-overlay *)").removeClass("payin7-overlay-blur");
                }

                if (callback) {
                    callback();
                }
            }
        });
    },

    finalizeOrder: function (orderData, options, callback) {
        var resubmit = orderData['resubmit'] || false;
        var orderId = orderData['orderId'] || null;
        var me = this;

        if (!orderId) {
            callback(false);
            return;
        }

        if (resubmit) {
            jQuery.fancybox.showLoading();

            this._post(this._locaLApiUrl('submitorder'), {
                order_id: orderId
            }, function (success, response, errMessage, errCode) {
                jQuery.fancybox.hideLoading();

                if (success) {
                    orderData['orderUrl'] = orderData['orderUrl'] || response['orderUrl'];

                    me._showOrderCompletionDialog(orderData,
                        (options['dialog'] || undefined),
                        callback);
                } else {
                    me._showMessage(errMessage);

                    if (callback !== undefined) {
                        callback(false);
                    }
                }
            });
        } else {
            me._showOrderCompletionDialog(orderData,
                (options['dialog'] || undefined),
                callback);
        }
    }
};