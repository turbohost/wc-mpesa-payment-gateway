(function () {
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { decodeEntities } = window.wp.htmlEntities;
    const { __ } = window.wp.i18n;
    const { createElement, useState, useEffect } = window.wp.element;

    const settings = getSetting('wc-mpesa-payment-gateway_data', {});
    const label = decodeEntities(settings.title) || __('Mpesa for WooCommerce', 'wc-mpesa-payment-gateway');

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
        return createElement(
            'div',
            { className: 'wc-mpesa-phone-field', style: { marginTop: '0.75em' } },
            createElement(
                'label',
                { htmlFor: 'wc_mpesa_number' },
                __('Mpesa number', 'wc-mpesa-payment-gateway'),
                ' ',
                createElement('span', { className: 'required' }, '*')
            ),
            createElement('input', {
                id: 'wc_mpesa_number',
                name: 'wc_mpesa_number',
                type: 'tel',
                value: props.value,
                placeholder: settings.placeholder || __('ex: 84 123 XXXX', 'wc-mpesa-payment-gateway'),
                disabled: !!props.disabled,
                onChange: function (event) {
                    props.onChange(event.target.value);
                },
                style: { width: '100%', marginTop: '0.25em' },
            })
        );
    };

    const Content = function (props) {
        const eventRegistration = props.eventRegistration || {};
        const emitResponse = props.emitResponse || {};
        const onPaymentSetup = eventRegistration.onPaymentSetup;
        const [number, setNumber] = useState('');

        useEffect(
            function () {
                if (!onPaymentSetup) {
                    return undefined;
                }

                const unsubscribe = onPaymentSetup(function () {
                    const cleaned = String(number || '').replace(/\s/g, '');

                    if (!isValidMpesaNumber(cleaned)) {
                        return {
                            type: emitResponse.responseTypes.ERROR,
                            message: __('Phone number is required!', 'wc-mpesa-payment-gateway'),
                            messageContext: emitResponse.noticeContexts.PAYMENTS,
                        };
                    }

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

        return createElement(
            'div',
            { className: 'wc-mpesa-payment-method' },
            createElement(Description),
            createElement(PhoneField, {
                value: number,
                onChange: setNumber,
            })
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
        label: label,
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
