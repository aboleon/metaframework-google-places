/**
 * Google Places Autocomplete using PlaceAutocompleteElement (New API)
 * Migrated from legacy google.maps.places.Autocomplete
 */

async function initializeGooglePlaces() {
    const metaframeworkGooglePlaces = document.querySelectorAll('.gmapsbar');

    if (!metaframeworkGooglePlaces.length) {
        return;
    }

    // Import the places library
    const { PlaceAutocompleteElement } = await google.maps.importLibrary('places');

    metaframeworkGooglePlaces.forEach(function (element) {
        setupGooglePlacesBar(element, PlaceAutocompleteElement);
    });
}

function setupGooglePlacesBar(element, PlaceAutocompleteElement) {
    const mapsbarId = element.getAttribute('id');
    const paramsElement = document.getElementById('params_' + mapsbarId);
    const autocompleteInput = element.querySelector('.g_autocomplete');
    const manualFixToggle = element.querySelector('.mfw-google-places-manual-fix');

    if (!autocompleteInput) {
        console.warn('Google Places: No .g_autocomplete input found in', mapsbarId);
        return;
    }

    // Build options for PlaceAutocompleteElement
    let apiOptions = {
        types: ['geocode']
    };

    if (paramsElement) {
        const optionsText = paramsElement.textContent.trim();
        if (optionsText.length) {
            try {
                const parsedOptions = JSON.parse(optionsText);
                // Map legacy options to new API format
                if (parsedOptions.types) {
                    apiOptions.types = parsedOptions.types;
                }
                if (parsedOptions.componentRestrictions && parsedOptions.componentRestrictions.country) {
                    // New API uses includedRegionCodes instead of componentRestrictions
                    const countries = parsedOptions.componentRestrictions.country;
                    apiOptions.includedRegionCodes = Array.isArray(countries) ? countries : [countries];
                }
            } catch (e) {
                console.warn('Google Places: Failed to parse options', e);
            }
        }
    }

    // Map legacy 'types' format to new 'includedPrimaryTypes' if needed
    // The new API accepts: 'geocode', 'address', 'establishment', 'regions', 'cities'
    const typeMapping = {
        'geocode': 'geocode',
        'address': 'address',
        'establishment': 'establishment',
        '(regions)': 'regions',
        '(cities)': 'cities'
    };

    if (apiOptions.types) {
        const mappedTypes = apiOptions.types.map(t => typeMapping[t] || t);
        apiOptions.includedPrimaryTypes = mappedTypes;
        delete apiOptions.types;
    }

    // Get current input value and placeholder
    const currentValue = autocompleteInput.value;
    const placeholder = autocompleteInput.getAttribute('placeholder') || '';
    const inputName = autocompleteInput.getAttribute('name');
    const inputId = autocompleteInput.getAttribute('id');

    // Create the PlaceAutocompleteElement with placeholder attribute
    const placeAutocomplete = new PlaceAutocompleteElement(apiOptions);

    // Set placeholder attribute directly on the element (supported by the component)
    placeAutocomplete.setAttribute('placeholder', placeholder);

    // Style the autocomplete element - light color scheme, no dark border
    placeAutocomplete.style.cssText = 'width: 100%; color-scheme: light; border: 1px solid #dee2e6; border-radius: 0.375rem;';

    // Create a hidden input to store the text_address value for form submission
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = inputName;
    hiddenInput.value = currentValue;
    hiddenInput.id = inputId + '_hidden';

    // Replace the original input with the PlaceAutocompleteElement
    const inputParent = autocompleteInput.parentNode;
    inputParent.insertBefore(placeAutocomplete, autocompleteInput);
    inputParent.insertBefore(hiddenInput, autocompleteInput);
    autocompleteInput.remove();

    if (manualFixToggle) {
        applyManualFixState(element, manualFixToggle.checked);
        manualFixToggle.addEventListener('change', () => {
            applyManualFixState(element, manualFixToggle.checked);
        });
    }

    // Try to set existing value in the internal input (closed shadow DOM workaround)
    if (currentValue) {
        // Wait for the component to render, then try to access internal input
        setTimeout(() => {
            // Try to find internal input via undocumented property (may break in future)
            const internalProps = Object.keys(placeAutocomplete).filter(k =>
                placeAutocomplete[k] &&
                typeof placeAutocomplete[k] === 'object' &&
                placeAutocomplete[k].tagName === 'INPUT'
            );

            if (internalProps.length > 0) {
                placeAutocomplete[internalProps[0]].value = currentValue;
            } else {
                // Fallback: try common undocumented property names
                for (const prop of ['Dg', 'Fg', 'Eg', 'Hg', 'input']) {
                    if (placeAutocomplete[prop] && placeAutocomplete[prop].setAttribute) {
                        try {
                            placeAutocomplete[prop].value = currentValue;
                            break;
                        } catch (e) {}
                    }
                }
            }
        }, 100);
    }

    // Component mapping for address extraction
    const componentForm = {
        street_number: 'longText',
        route: 'longText',
        locality: 'longText',
        administrative_area_level_1: 'longText',
        administrative_area_level_2: 'longText',
        country: 'longText',
        postal_code: 'shortText'
    };

    // Listen for place selection
    placeAutocomplete.addEventListener('gmp-select', async (event) => {
        const { placePrediction } = event;

        if (!placePrediction) {
            return;
        }

        // Convert to Place and fetch detailed fields
        const place = placePrediction.toPlace();

        try {
            await place.fetchFields({
                fields: [
                    'displayName',
                    'formattedAddress',
                    'addressComponents',
                    'location'
                ]
            });

            // Clear all previous values except the autocomplete
            clearAddressFields(element);

            // Update hidden input with formatted address
            const predictionText = placePrediction?.text?.text || placePrediction?.text || '';
            hiddenInput.value = predictionText || place.formattedAddress || '';

            const placeIdField = element.querySelector('.place_id');
            if (placeIdField) {
                placeIdField.value = placePrediction.placeId || placePrediction.id || place.id || place.placeId || '';
            }

            // Process address components
            const addressComponents = place.addressComponents || [];
            let address = [];

            addressComponents.forEach(component => {
                const types = component.types || [];

                types.forEach(addressType => {
                    if (componentForm[addressType]) {
                        const valueKey = componentForm[addressType];
                        const value = component[valueKey] || component.longText || '';
                        const fieldElement = element.querySelector('.' + addressType);

                        if (fieldElement) {
                            fieldElement.value = value;
                            fieldElement.disabled = false;
                        }
                    }

                    // Build street address
                    if (addressType === 'street_number' || addressType === 'route') {
                        address.push(component.longText || '');
                    }

                    // Handle country code separately
                    if (addressType === 'country') {
                        const countryCodeField = element.querySelector('.country_code');
                        if (countryCodeField) {
                            countryCodeField.value = component.shortText || '';
                            countryCodeField.disabled = false;
                        }
                    }

                    // Handle continent if present
                    if (addressType === 'continent') {
                        const continentField = element.querySelector('.continent');
                        if (continentField) {
                            continentField.value = component.longText || '';
                            const event = new Event('change', { bubbles: true });
                            continentField.dispatchEvent(event);
                        }
                    }
                });
            });

            // Set address type from first component
            if (addressComponents.length > 0 && addressComponents[0].types && addressComponents[0].types.length > 0) {
                const addressTypeField = element.querySelector('.address_type');
                if (addressTypeField) {
                    addressTypeField.value = addressComponents[0].types[0];
                }
            }

            const localizedLocality = extractLocalityFromPrediction(placePrediction, element);
            if (localizedLocality) {
                const localityField = element.querySelector('.locality');
                if (localityField) {
                    localityField.value = localizedLocality;
                    localityField.disabled = false;
                }
            }

            // Set lat/lng
            if (place.location) {
                const latField = element.querySelector('.mfw_geo_lat');
                const lonField = element.querySelector('.mfw_geo_lon');

                if (latField) {
                    latField.value = place.location.lat();
                    const event = new Event('change', { bubbles: true });
                    latField.dispatchEvent(event);
                }

                if (lonField) {
                    lonField.value = place.location.lng();
                    const event = new Event('change', { bubbles: true });
                    lonField.dispatchEvent(event);
                }
            }

            // Execute callback if defined
            const callback = element.parentElement?.dataset?.callback;
            if (callback && typeof window[callback] === 'function') {
                window[callback](element);
            }

        } catch (error) {
            console.error('Google Places: Error fetching place details', error);
        }
    });

    // Handle errors
    placeAutocomplete.addEventListener('gmp-error', (event) => {
        console.error('Google Places Autocomplete error:', event);
    });
}

function applyManualFixState(element, enabled) {
    const lockableElements = element.querySelectorAll('.lockable');
    lockableElements.forEach(lockable => {
        const targets = lockable.matches('input, textarea, select')
            ? [lockable]
            : Array.from(lockable.querySelectorAll('input, textarea, select'));

        targets.forEach(target => {
            if (enabled) {
                target.removeAttribute('readonly');
            } else {
                target.setAttribute('readonly', 'readonly');
            }
        });
    });
}

function extractLocalityFromPrediction(placePrediction, element) {
    if (!placePrediction) {
        return '';
    }

    const structured = placePrediction.structuredFormat || {};
    const secondaryText = structured.secondaryText?.text || structured.secondaryText || '';
    const mainText = structured.mainText?.text || structured.mainText || '';

    let candidate = '';

    if (secondaryText) {
        const parts = secondaryText.split(',').map(part => part.trim()).filter(Boolean);
        if (parts.length > 1) {
            parts.pop();
        }
        candidate = parts.join(', ').trim();
    }

    if (!candidate && mainText) {
        candidate = mainText.trim();
    }

    if (!candidate) {
        return '';
    }

    const postalCodeField = element.querySelector('.postal_code');
    if (postalCodeField && postalCodeField.value) {
        const code = postalCodeField.value.trim();
        if (code) {
            candidate = candidate.replace(new RegExp('^' + escapeRegExp(code) + '\\s*'), '');
        }
    }

    candidate = candidate.replace(/^[0-9]+\\s+/, '').trim();

    return candidate;
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&');
}

function clearAddressFields(element) {
    const fieldsToReset = [
        'street_number',
        'route',
        'locality',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'country',
        'country_code',
        'postal_code'
    ];

    fieldsToReset.forEach(fieldClass => {
        const field = element.querySelector('.' + fieldClass);
        if (field) {
            field.value = '';
            field.disabled = false;
        }
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGooglePlaces);
} else {
    initializeGooglePlaces();
}
