(function () {
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { decodeEntities } = window.wp.htmlEntities;
    const { __, sprintf: i18nSprintf } = window.wp.i18n;
    const sprintf =
        typeof i18nSprintf === 'function'
            ? i18nSprintf
            : function (format, value) {
                return String(format).replace('%s', value);
            };
    const { createElement, createPortal, useState, useEffect } = window.wp.element;
    const useSelect =
        window.wp.data && window.wp.data.useSelect
            ? window.wp.data.useSelect
            : function () {
                return false;
            };

    const settings = getSetting('wc-mpesa-payment-gateway_data', {});
    const label = decodeEntities(settings.title) || __('Mpesa for WooCommerce', 'wc-mpesa-payment-gateway');
    const iconUrl = settings.icon || '';
    const waitingSeconds = parseInt(settings.waitingSeconds, 10) || 60;

    const Label = function (props) {
        const PaymentMethodLabel = props.components && props.components.PaymentMethodLabel;
        const icon = iconUrl
            ? createElement('img', {
                src: iconUrl,
                alt: label,
                style: { height: '24px', width: 'auto' },
            })
            : undefined;

        if (PaymentMethodLabel) {
            return createElement(PaymentMethodLabel, { text: label, icon: icon });
        }

        return createElement(
            'span',
            { className: 'wc-mpesa-payment-label' },
            icon,
            ' ',
            label
        );
    };

    const isValidMpesaNumber = function (number) {
        const cleaned = String(number || '').replace(/\s/g, '');
        return /^8[45][0-9]{7}$/.test(cleaned);
    };

    const Description = function () {
        const parts = [decodeEntities(settings.description || '')];
        if (settings.testMode) {
            parts.push(__('TEST MODE ENABLED.', 'wc-mpesa-payment-gateway'));
        }

        return createElement(
            'div',
            { className: 'wc-mpesa-description' },
            parts.filter(Boolean).join(' ')
        );
    };

    const PhoneField = function (props) {
        const [isFocused, setIsFocused] = useState(false);
        const hasValue = String(props.value || '').length > 0;
        const className =
            'wc-block-components-text-input' +
            (isFocused || hasValue ? ' is-active' : '');
        const fieldLabel = __('Mpesa number', 'wc-mpesa-payment-gateway');

        return createElement(
            'div',
            { className: className },
            createElement('input', {
                id: 'wc_mpesa_number',
                name: 'wc_mpesa_number',
                type: 'tel',
                inputMode: 'numeric',
                autoComplete: 'tel',
                value: props.value,
                disabled: !!props.disabled,
                required: true,
                'aria-label': fieldLabel,
                onChange: function (event) {
                    props.onChange(event.target.value);
                },
                onFocus: function () {
                    setIsFocused(true);
                },
                onBlur: function () {
                    setIsFocused(false);
                },
            }),
            createElement(
                'label',
                { htmlFor: 'wc_mpesa_number' },
                fieldLabel
            )
        );
    };

    const WaitingOverlay = function (props) {
        const secondsLeft = props.secondsLeft;
        const phone = props.phone;
        const stillWaiting = secondsLeft <= 0;
        const titleId = 'wc-mpesa-waiting-title';

        const card = createElement(
            'div',
            { className: 'wc-mpesa-waiting-overlay__card' },
            createElement(
                'h2',
                { id: titleId, className: 'wc-mpesa-waiting-overlay__title' },
                __('Confirm the payment on your phone', 'wc-mpesa-payment-gateway')
            ),
            createElement(
                'p',
                { className: 'wc-mpesa-waiting-overlay__text' },
                sprintf(
                    __('We sent an M-Pesa prompt to %s.', 'wc-mpesa-payment-gateway'),
                    phone
                )
            ),
            createElement(
                'div',
                { className: 'wc-mpesa-waiting-overlay__countdown' },
                stillWaiting ? '…' : String(secondsLeft)
            ),
            createElement(
                'p',
                { className: 'wc-mpesa-waiting-overlay__hint' },
                stillWaiting
                    ? __('Still waiting for confirmation…', 'wc-mpesa-payment-gateway')
                    : __('Do not close this page.', 'wc-mpesa-payment-gateway')
            )
        );

        const overlay = createElement(
            'div',
            {
                className: 'wc-mpesa-waiting-overlay',
                role: 'dialog',
                'aria-modal': 'true',
                'aria-live': 'polite',
                'aria-labelledby': titleId,
            },
            card
        );

        if (typeof createPortal === 'function' && document.body) {
            return createPortal(overlay, document.body);
        }

        return overlay;
    };

    const Content = function (props) {
        const eventRegistration = props.eventRegistration || {};
        const emitResponse = props.emitResponse || {};
        const onPaymentSetup = eventRegistration.onPaymentSetup;
        const [number, setNumber] = useState('');
        const [awaitingPhone, setAwaitingPhone] = useState(false);
        const [secondsLeft, setSecondsLeft] = useState(waitingSeconds);

        const isProcessing = useSelect(function (select) {
            const storeKey =
                window.wc.wcBlocksData && window.wc.wcBlocksData.checkoutStore
                    ? window.wc.wcBlocksData.checkoutStore
                    : 'wc/store/checkout';
            try {
                const checkout = select(storeKey);
                return !!(checkout && checkout.isProcessing && checkout.isProcessing());
            } catch (e) {
                return false;
            }
        }, []);

        useEffect(
            function () {
                if (!onPaymentSetup) {
                    return undefined;
                }

                const unsubscribe = onPaymentSetup(function () {
                    const cleaned = String(number || '').replace(/\s/g, '');

                    if (!isValidMpesaNumber(cleaned)) {
                        setAwaitingPhone(false);
                        return {
                            type: emitResponse.responseTypes.ERROR,
                            message: __('Phone number is required!', 'wc-mpesa-payment-gateway'),
                            messageContext: emitResponse.noticeContexts.PAYMENTS,
                        };
                    }

                    setAwaitingPhone(true);
                    return {
                        type: emitResponse.responseTypes.SUCCESS,
                        meta: {
                            paymentMethodData: {
                                wc_mpesa_number: cleaned,
                            },
                        },
                    };
                });

                return unsubscribe;
            },
            [onPaymentSetup, number, emitResponse]
        );

        useEffect(
            function () {
                if (!isProcessing) {
                    setAwaitingPhone(false);
                    setSecondsLeft(waitingSeconds);
                    return undefined;
                }

                if (!awaitingPhone) {
                    return undefined;
                }

                setSecondsLeft(waitingSeconds);
                const timer = setInterval(function () {
                    setSecondsLeft(function (previous) {
                        return previous > 0 ? previous - 1 : 0;
                    });
                }, 1000);

                return function () {
                    clearInterval(timer);
                };
            },
            [isProcessing, awaitingPhone]
        );

        return createElement(
            'div',
            { className: 'wc-mpesa-payment-method' },
            createElement(Description),
            createElement(PhoneField, {
                value: number,
                onChange: setNumber,
            }),
            awaitingPhone && isProcessing
                ? createElement(WaitingOverlay, {
                    secondsLeft: secondsLeft,
                    phone: String(number || '').replace(/\s/g, ''),
                })
                : null
        );
    };

    const Edit = function () {
        return createElement(
            'div',
            { className: 'wc-mpesa-payment-method' },
            createElement(Description),
            createElement(PhoneField, {
                value: '',
                onChange: function () {},
                disabled: true,
            })
        );
    };

    registerPaymentMethod({
        name: 'wc-mpesa-payment-gateway',
        label: createElement(Label, null),
        content: createElement(Content, null),
        edit: createElement(Edit, null),
        canMakePayment: function () {
            return settings.currency === 'MZN';
        },
        ariaLabel: label,
        supports: {
            features: settings.supports || ['products'],
        },
    });
})();
