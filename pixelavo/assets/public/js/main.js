(function ($) {
    'use strict';

    // Global Variables
    const {ajax_url, nonce} = pixelavo;

    /**
     * Get File Extension form a URL
     * @param {string} url 
     * @returns {string} File extension
     */
    window.getFileExtension = function(url) {
        // Handle null/undefined input
        if (!url) return '';
        // Remove query parameters and hash fragments
        const cleanUrl = url.split(/[?#]/)[0];
        // Get the last segment after the final dot
        const extension = cleanUrl.split('.').pop().toLowerCase();
        // Return empty string if no extension found
        return extension === cleanUrl ? '' : extension;
    }
    /**
     * Get File Name from a URL, with optional extension stripping
     * @param {string} url - The URL or file path
     * @param {boolean} [stripExtension=true] - Whether to remove the file extension
     * @returns {string} File name with or without extension
     */
    window.getFileName = function(url, stripExtension = true) {
        // Handle null/undefined input
        if (!url) return '';
        // Remove query parameters and hash fragments
        const cleanUrl = url.split(/[?#]/)[0];
        // Get the part after the last slash
        const fileName = cleanUrl.split('/').pop();
        // Handle cases where the URL ends with a slash
        if (!fileName) return '';
        // If stripExtension is true, remove the extension
        if (stripExtension) {
            const nameParts = fileName.split('.');
            return nameParts.length > 1 ? nameParts.slice(0, -1).join('.') : fileName;
        }
        return fileName;
    };

    /**
     * Get Page Scroll Position Percentage
     * @returns {number} scroll parentage number
     */
    window.getPageScrollPercentage = function() {
        const documentHeight = Math.max(
            document.body.scrollHeight, 
            document.documentElement.scrollHeight,
            document.body.offsetHeight, 
            document.documentElement.offsetHeight
        );
        const windowHeight = window.innerHeight;
        const scrollTop = window.scrollY || window.pageYOffset;
        // Add boundary check to prevent potential division by zero or negative values
        const scrollPercent = documentHeight > windowHeight 
            ? Math.min(100, Math.max(0, (scrollTop / (documentHeight - windowHeight)) * 100))
            : 0;
        return Math.round(scrollPercent);
    }

    /**
     * Extracts and processes form field data into a structured object.
     *
     * @param {jQuery} form - jQuery object representing the form element to process
     * @returns {Object.<string, string>} Object containing form field data.
     */
    window.getFormEntryData = function(form) {
        const formData = {};
        form.find('input, select, textarea').each(function() {
            const $field = $(this);
            let fieldName = $field.attr('name');
            // Skip if no name or if it's a submit/button/hidden
            if (!fieldName || $field.attr('type') === 'submit' || $field.attr('type') === 'button' || $field.attr('type') === 'hidden' || !$field.val()) {
                return;
            }
            // Extract the last part from name attribute (e.g., "first_name" from "names[first_name]")
            const matches = fieldName.match(/\[([^\]]+)\]$/);
            if (matches) {
                fieldName = matches[1];
            }
            // Handle different input types
            if ($field.attr('type') === 'checkbox') {
                if ($field.is(':checked')) {
                    formData[fieldName] = $field.val();
                }
            } else if ($field.attr('type') === 'radio') {
                if ($field.is(':checked')) {
                    formData[fieldName] = $field.val();
                }
            } else if ($field.attr('type') === 'file') {
                const files = $field[0].files;
                if (files.length > 0) {
                    formData[fieldName] = files[0].name;
                }
            } else {
                // For regular inputs, select, and textarea
                formData[fieldName] = $field.val();
            }
        });
        return formData;
    }

    /**
    * Get form title by ID and form type.
    * 
    * @param {number} id - The ID of the form
    * @param {string} form - The type of form ('cf7', 'wpforms', or 'fluentform')
    * @returns {Promise<string>} Returns a promise that resolves with the form title
    */
    window.getFormTitle = (id, form) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ajax_url,
                method: "POST",
                data: {
                    'action': 'pixelavo_get_form_title',
                    'nonce': nonce,
                    'formId': id,
                    'form': form
                },
                success: function(res) {
                    resolve(res.data);
                },
                error: function(error) {
                    reject(error);
                }
            });
        });
    }

    /**
     * Fire Event
     * @param {object} param - Event parameters
     * @param {string} param.track - Event track (track, trackCustom)
     * @param {string} param.name - Event name
     * @param {object} param.data - Event data
     * @param {string} param.id - Event ID
     */
    window.fireEvent = function({track, name, data, id, action = false}) {
        const eventID = id || name.replace(' ', '') + '.' + new Date()?.getTime();
        fbq(track, name, data, { eventID });
        $.ajax({
            url: ajax_url,
            method: "POST",
            data: {
                'action': action ? action : 'pixelavo_event',
                'nonce': nonce,
                'track': track,
                'event_name': name,
                'event_id': eventID,
                'data': data,
            },
            success: (function ({ data }) {
            }),
            error: (function(xhr, status, error) {
                console.error('Pixelavo: Failed to fire server event -', name, error);
            })
        })
    }
    
    /**
     * WooCommerce remove from cart ajax common function
     * @param {number} product_id 
     * @param {number} quantity
     */
    window.fireRemoveFromCartEvent = function(product_id, quantity) {
        $.ajax({
            url: pixelavo.ajax_url,
            type: "POST",
            data: {
                'action': 'pixelavo_ajax_remove_from_cart',
                'nonce': pixelavo.nonce,
                'product_id': product_id,
                'product_quantity': quantity,
                'common_params': pixelavo.common_params
            },
            success: (function ({ data }) {
                fbq('track', 'RemoveFromCart', data.data, { eventID: data.event_id });
            }),
            error: (function(xhr, status, error) {
                console.error('Pixelavo: Failed to fire RemoveFromCart server event -', error);
            })
        })
    }

    const {fireEvent, fireRemoveFromCartEvent, getFileExtension, getFileName, getPageScrollPercentage, getFormEntryData, getFormTitle} = window;

    if(pixelavo_event && pixelavo_event.length > 0) {
        pixelavo_event.forEach((item) => {
            const {track, data, event, eventID, isEventDelay} = item
            if(event === 'ViewContent' && isEventDelay) {
                const {eventDelay} = item
                setTimeout(function() {
                    fireEvent({track, name: event, data: {...data, ...pixelavo.common_params}, action: 'pixelavo_view_content_delay_server_event'});
                }, eventDelay * 1000)
            } else {
                fbq(track, event, data, { eventID: eventID });
            }
        })
    }
    
    /**
     * Easy Digital Downloads Remove From Cart
     */
    $('form#edd_checkout_cart_form .edd_cart_remove_item_btn').on('click', function(e) {
        const $this = $(this),
            $cartItem = $this.closest('.edd_cart_item'),
            $downloadId = $cartItem.data('download-id');
        $.ajax({
            url: pixelavo.ajax_url,
            type: "POST",
            data: {
                'action': 'pixelavo_edd_ajax_remove_from_cart',
                'ajax_nonce': pixelavo.nonce,
                'download_id': $downloadId,
                'download_quantity': 1,
                'common_params': pixelavo.common_params
            },
            success: (function ({ data }) {
                fbq('track', 'RemoveFromCart', data.data, { eventID: data.event_id });
                console.log('EDD RemoveFromCart event run successfully');
            })
        })
    });
        
    /**
     * WooCommerce remove from cart
     * Mini cart event
     */
    $('body').on('removed_from_cart', function (e, fragments, cart_hash, button) {
        const product_id = button[0].dataset.product_id
        const cartItem = button.closest('.mini_cart_item, .woocommerce-mini-cart-item');
        let quantity = 1;
        const quantityText = cartItem?.find('.quantity').text();
        if(quantityText) {
            const match = quantityText.match(/(\d+)\s×/);
            if(match) {
                quantity = parseInt(match[1]);
            }
        }
        fireRemoveFromCartEvent(parseInt(product_id), quantity);
    })
    /**
     * WooCommerce remove from cart
     * Cart page classic
     */
    $('body').on('click', '.woocommerce-cart-form .remove', function (e) {
        const target = $(e.currentTarget);
        const product_id = target[0].dataset.product_id;
        const cartItem = target.closest('.woocommerce-cart-form__cart-item, .cart_item');
        const quantity = cartItem?.find('.quantity input.qty').val();
        fireRemoveFromCartEvent(parseInt(product_id), quantity);
    });

    /**
     * Other/Extra Events
     * Page Scroll Event
     */
    if(pixelavo?.other_events) {
        const other_events = JSON.parse(pixelavo?.other_events);
        
        if(other_events.page_scroll === 'on') {
            let $eventFired = false;
            $(document).on('scroll', function() {
                const scrollPosition = getPageScrollPercentage();
                if(scrollPosition >= +pixelavo.other_event_options.page_scroll.page_scroll_value && !$eventFired) {
                    fireEvent({track: 'trackCustom', name: 'PageScroll', data: pixelavo.common_params})
                    $eventFired = true;
                }
            })
        }

        if(other_events.time_on_page === 'on') {
            setTimeout(function() {
                fireEvent({track: 'trackCustom', name: 'TimeOnPage', data: pixelavo.common_params})
            }, +pixelavo.other_event_options.time_on_page.time_on_page_value * 1000);
        }

        if(other_events.download_files && other_events.download_files === 'on') {
            let eventFired = '';
            $(document).on('click', 'a', function (e) {
                const _this = $(this);
                let url = _this.attr('href');
                // Prevent multiple event firing for the same link/file
                if(eventFired !== url) {
                    if(typeof url !== 'string') return;
                    url = url.trim();
                    const extension = getFileExtension(url),
                        fileName = getFileName(url),
                        param = {
                            ...pixelavo.common_params,
                            download_url: url,
                            download_type: extension,
                            download_name: fileName,
                        };
                    if(extension.length > 0 && pixelavo.other_event_options.download_files.download_files_extensions.includes(extension)) {
                        fireEvent({track: 'trackCustom', name: 'Download', data: param})
                        eventFired = url;
                    }
                }
            });
        }

        if(other_events?.form_submission && other_events?.form_submission === 'on') {
            const prepareFormTitleForEventName = (string) => {
                return string
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join('')
            }
            $(document).on('fluentform_submission_success', async function(event, data) {
                const formId = data.config.id;
                let form_title = formId;
                try {
                    form_title = await getFormTitle(formId, 'fluentform');
                    form_title = prepareFormTitleForEventName(form_title);
                } catch(error) {
                    console.error(error);
                }
                const { fluent_form } = pixelavo.other_event_options.form;
                if(fluent_form.includes("all_forms") || fluent_form.includes(formId.toString())) {
                    const param = {
                        ...pixelavo.common_params,
                        form: 'Fluent Forms',
                        form_id: formId
                    };
                    fireEvent({track: 'trackCustom', name: `FormSubmission_${form_title}`, data: param})
                }
            });

            document.addEventListener('wpcf7mailsent', async function(event) {
                const formId = event.detail.contactFormId;
                let form_title = formId;
                try {
                    form_title = await getFormTitle(formId, 'cf7');
                    form_title = prepareFormTitleForEventName(form_title);
                } catch(error) {
                    console.error(error);
                }
                const { contact_form_7 } = pixelavo.other_event_options.form;
                if(contact_form_7.includes("all_forms") || contact_form_7.includes(formId.toString())) {
                    const param = {
                        ...pixelavo.common_params,
                        form: 'Contact Form 7',
                        form_id: formId
                    };
                    fireEvent({track: 'trackCustom', name: `FormSubmission_${form_title}`, data: param})
                }
            });

            $(document).on('wpformsAjaxSubmitSuccess', async function(event, details) {
                const $form = $(event.target);
                const formId = $form.data('formid');
                let form_title = formId;
                try {
                    form_title = await getFormTitle(formId, 'wpform');
                    form_title = prepareFormTitleForEventName(form_title);
                } catch(error) {
                    console.error(error);
                }
                const { wp_forms } = pixelavo.other_event_options.form;
                if(wp_forms.includes("all_forms") || wp_forms.includes(formId.toString())) {
                    const param = {
                        ...pixelavo.common_params,
                        form: 'WPForms',
                        form_id: formId
                    };
                    fireEvent({track: 'trackCustom', name: `FormSubmission_${form_title}`, data: param})
                }
            });

            // HT Contact Form - AJAX submissions
            document.addEventListener('htform:submitted', async function(event) {
                const formId = event.detail.formId;
                let form_title = formId;
                try {
                    form_title = await getFormTitle(formId, 'ht_contactform');
                    form_title = prepareFormTitleForEventName(form_title);
                } catch(error) {
                    console.error(error);
                }
                const { ht_contactform } = pixelavo.other_event_options.form;
                if(ht_contactform && (ht_contactform.includes("all_forms") || ht_contactform.includes(formId.toString()))) {
                    const param = {
                        ...pixelavo.common_params,
                        form: 'HT Contact Form',
                        form_id: formId
                    };
                    fireEvent({track: 'trackCustom', name: `FormSubmission_${form_title}`, data: param})
                }
            });

            // HT Contact Form - Standard POST submissions (page reloaded with ?form_success={id})
            const htFormSuccessParam = new URLSearchParams(window.location.search).get('form_success');
            if (htFormSuccessParam) {
                (async function() {
                    const htForm = document.querySelector(`.ht-form[data-form-id="${htFormSuccessParam}"]`);
                    if (htForm) {
                        const formId = htFormSuccessParam;
                        let form_title = formId;
                        try {
                            form_title = await getFormTitle(formId, 'ht_contactform');
                            form_title = prepareFormTitleForEventName(form_title);
                        } catch(error) {
                            console.error(error);
                        }
                        const { ht_contactform } = pixelavo.other_event_options.form;
                        if(ht_contactform && (ht_contactform.includes("all_forms") || ht_contactform.includes(formId.toString()))) {
                            const param = {
                                ...pixelavo.common_params,
                                form: 'HT Contact Form',
                                form_id: formId
                            };
                            fireEvent({track: 'trackCustom', name: `FormSubmission_${form_title}`, data: param})
                        }
                    }
                })();
            }
        }
        
        // Login / CompleteRegistration are fired SERVER-SIDE (Conversions API).
        // The browser fires fbq ONLY, reusing the server-generated event_id so
        // Facebook deduplicates the pair. The event descriptors ride in the
        // `pixelavo_srv_evt` cookie (survives post-login redirect hops); we read
        // it, fire, then clear it so it runs once. No AJAX/CAPI, no credentials.
        (function fireServerAuthEvents() {
            const match = document.cookie.match(/(?:^|;\s*)pixelavo_srv_evt=([^;]*)/);
            if(!match) return;
            let queue;
            try { queue = JSON.parse(decodeURIComponent(match[1])); } catch(e) { queue = null; }
            // Clear immediately so the events fire exactly once.
            document.cookie = 'pixelavo_srv_evt=; Max-Age=0; path=/';
            if(!Array.isArray(queue)) return;
            queue.forEach((ev) => {
                if(!ev || !ev.name || !ev.id) return;
                fbq(ev.track === 'track' ? 'track' : 'trackCustom', ev.name, pixelavo.common_params, { eventID: ev.id });
            });
        })();

        if(other_events.tel_click === 'on') {
            document.addEventListener('click', function(e) {
                const target = e.target;
                if(target.tagName === 'A' && target.href.startsWith('tel:')) {
                    fireEvent({track: 'trackCustom', name: 'TelClick', data: pixelavo.common_params})
                }
            })
        }
        
    }

})(jQuery); 

/**
 * WooCommerce remove from cart
 * Block based cart page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if wp object and required methods exist
    if (typeof wp === 'undefined' || !wp?.data?.select || !wp?.data?.subscribe) return;
    
    let previousCart = wp?.data?.select('wc/store/cart')?.getCartData() || {};
    wp?.data?.subscribe(() => {
        const currentCart = wp?.data?.select('wc/store/cart')?.getCartData() || {};
        if(previousCart?.items?.length > currentCart?.items?.length) {
            const removedItem = previousCart?.items?.find(prevItem => 
                !currentCart?.items?.some(currItem => currItem?.key === prevItem?.key)
            );
            if(removedItem) {
                window.fireRemoveFromCartEvent(removedItem?.id, removedItem?.quantity);
            }
        }
        previousCart = currentCart;
    });
});