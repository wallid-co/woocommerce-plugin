(function() {
    'use strict';
    
    var registered = false;
    var retryCount = 0;
    var maxRetries = 50; // Maximum 5 seconds (50 * 100ms)
    
    function registerWallidPaymentMethod() {
        // Prevent multiple registrations
        if (registered) {
            return;
        }
        
        // Check if dependencies are available - use wcBlocksRegistry (not blocksRegistry)
        if (typeof wc === 'undefined' || 
            typeof wc.wcBlocksRegistry === 'undefined' || 
            typeof wc.wcSettings === 'undefined' ||
            typeof wp === 'undefined' || 
            typeof wp.element === 'undefined') {
            // Retry with exponential backoff for first few attempts, then slower
            retryCount++;
            if (retryCount < maxRetries) {
                var delay = retryCount < 5 ? 10 : 100; // Fast retries initially (10ms), then slower (100ms)
                setTimeout(registerWallidPaymentMethod, delay);
            }
            return;
        }
        
        // Mark as registered to prevent duplicate registrations
        registered = true;
        
        // Use wcBlocksRegistry (not blocksRegistry) per WooCommerce docs
        const { registerPaymentMethod } = wc.wcBlocksRegistry;
        const { getSetting } = wc.wcSettings;
        const { decodeEntities } = wp.htmlEntities;
        const { createElement } = wp.element;
        
        /**
         * Payment method data
         * According to WooCommerce docs, data is available as {name}_data
         * So for 'wallid_payment', it's 'wallid_payment_data'
         */
        const settings = getSetting('wallid_payment_data', {});
        
        const labelText = decodeEntities(settings.title || 'Pay by bank');
        
        /**
         * Payment Method Label Component
         * Following WooCommerce Blocks pattern - simple label with optional icon
         */
        const PaymentMethodLabel = () => {
            return createElement(
                'div',
                { className: 'wc-block-components-payment-method-label' },
                settings.icon && createElement(
                    'span',
                    {
                        className: 'wc-block-components-payment-method-icon',
                        dangerouslySetInnerHTML: { __html: settings.icon }
                    }
                ),
                createElement('span', null, labelText)
            );
        };
        
        /**
         * Payment Method Content Component
         */
        const PaymentMethodContent = ({ description }) => {
            if (!description) {
                return null;
            }
            return createElement(
                'div',
                { className: 'wc-block-components-payment-method-content' },
                createElement(
                    'div',
                    { className: 'wc-block-components-payment-method-description' },
                    description
                )
            );
        };
        
        /**
         * Wallid Payment Method
         * Following WooCommerce Blocks PaymentMethodConfiguration pattern
         */
        try {
            const paymentMethodConfig = {
                name: 'wallid_payment',
                label: createElement(PaymentMethodLabel, {}),
                content: createElement(PaymentMethodContent, { description: settings.description }),
                edit: createElement(PaymentMethodContent, { description: settings.description }),
                ariaLabel: labelText,
                canMakePayment: () => {
                    // Return true if gateway is available
                    return true;
                },
                supports: {
                    features: settings.supports || ['products'],
                },
            };
            
            registerPaymentMethod(paymentMethodConfig);
        } catch (error) {
            console.error('Wallid Payment Gateway: Registration error');
            registered = false; // Allow retry on error
        }
    }
    
    // Start registration immediately - don't wait for DOMContentLoaded
    // This ensures registration happens as early as possible
    registerWallidPaymentMethod();
    
    // Also try on DOMContentLoaded as backup
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (!registered) {
                registerWallidPaymentMethod();
            }
        });
    }
})();
