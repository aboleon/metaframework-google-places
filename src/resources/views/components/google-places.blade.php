{{-- Pour activer la recherche Google Maps Places - class=gmpasbar --}}
@php
    $error = $errors->any();
    $resolver = config('mfw-google-places.countries_resolver');
    $countryName = '';
    if ($error) {
        $countryName = old($field . '.country');
    } elseif (!empty($geo->country)) {
        $countryName = $geo->country;
    } elseif (
        !empty($geo->country_code) &&
        $resolver &&
        class_exists($resolver) &&
        method_exists($resolver, 'getCountryNameByCode')
    ) {
        $countryName = $resolver::getCountryNameByCode($geo->country_code);
    }
@endphp
<div class="clearfix gmapsbar {{ $field }}" id="mapsbar_{{ $random_id }}">
    <div class="locationField" data-error="">

        @if ($label)
            <label for="geo_text_address_{{ $random_id }}"
                class="form-label">{{ $label . $labelRequired('text_address') }}</label>
        @endif
        <input type="text" name="{{ $field }}[text_address]"
            value="{{ old($field . '.text_address', $defaultTextAddress) }}"
            class="g_autocomplete form-control {{ $tagRequired('text_address') }}"
            id="geo_text_address_{{ $random_id }}"
            placeholder="{{ $placeholder ?: __('mfw-google-places.type_address') }}" {{ $tagRequired('text_address') }}>

        @if ($notice)
            <small class="d-block text-secondary pt-1">{{ $notice }}</small>
        @endif
        <x-mfw-inputable::validation-error field="{{ $field }}[text_address]" />
    </div>

    <div class="row {{ $field }}_fields my-3">
        <div class="col-sm-4 {{ $inputable('street_number') }} mb-3">
            <x-mfw-inputable::input
                class="field street_number{{ $tagRequired('street_number') . $readonlies('street_number') }}"
                :label="__('mfw-google-places.street_number')" name="{{ $field }}[street_number]"
                value="{{ old($field . '.street_number', $geo->street_number) }}" :params="['placeholder' => __('mfw-google-places.street_number')]" :required="$tagRequired('street_number')"
                :readonly="$readonlies('street_number')" />

        </div>
        <div class="col-sm-8 {{ $inputable('route') }} mb-3">
            <x-mfw-inputable::input class="field route{{ $tagRequired('route') . $readonlies('route') }}"
                :label="__('mfw-google-places.route') . $labelRequired('route')" name="{{ $field }}[route]" value="{{ old($field . '.route', $geo->route) }}"
                :params="['placeholder' => __('mfw-google-places.route')]" :readonly="$readonlies('route')" />
        </div>
        <div class="col-sm-4 {{ $inputable('postal_code') }} mb-3">
            <x-mfw-inputable::input
                class="field postal_code{{ $tagRequired('postal_code') . $readonlies('postal_code') }}"
                :label="__('mfw-google-places.postal_code') . $labelRequired('postal_code')" name="{{ $field }}[postal_code]"
                value="{{ old($field . '.postal_code', $geo->postal_code) }}" :params="['placeholder' => __('mfw-google-places.postal_code')]" :readonly="$readonlies('postal_code')" />
        </div>
        <div class="col-sm-8 {{ $inputable('locality') }} mb-3">
            <x-mfw-inputable::input class="field locality{{ $tagRequired('locality') . $readonlies('locality') }}"
                :label="__('mfw-google-places.locality') . $labelRequired('locality')" name="{{ $field }}[locality]"
                value="{{ old($field . '.locality', $geo->locality) }}" :params="['placeholder' => __('mfw-google-places.locality')]" :readonly="$readonlies('locality')" />
        </div>
        <div class="col-sm-4 {{ $inputable('administrative_area_level_2') }} mb-3">
            <x-mfw-inputable::input
                class="field administrative_area_level_2 {{ $tagRequired('administrative_area_level_2') }}"
                :label="__('mfw-google-places.district') . $labelRequired('administrative_area_level_2')" name="{{ $field }}[administrative_area_level_2]"
                value="{{ old($field . '.administrative_area_level_2', $geo->administrative_area_level_2) }}" />
        </div>
        <div class="col-sm-8 {{ $inputable('administrative_area_level_1') }} mb-3">
            <x-mfw-inputable::input
                class="field administrative_area_level_1 {{ $tagRequired('administrative_area_level_1') }}"
                :label="__('mfw-google-places.region') . $labelRequired('administrative_area_level_1')" name="{{ $field }}[administrative_area_level_1]"
                value="{{ old($field . '.administrative_area_level_1', $geo->administrative_area_level_1) }}" />
        </div>
        <div class="col-sm-8 {{ $inputable('administrative_area_level_1_short') }} mb-3">
            <x-mfw-inputable::input
                class="field administrative_area_level_1_short {{ $tagRequired('administrative_area_level_1_short') }}"
                :label="__('mfw-google-places.region') . $labelRequired('administrative_area_level_1_short')" name="{{ $field }}[administrative_area_level_1_short]"
                value="{{ old($field . '.administrative_area_level_1_short', $geo->administrative_area_level_1_short) }}" />
        </div>
        <div class="col-sm-2 {{ $inputable('country_code') }} mb-3">
            <x-mfw-inputable::input class="field country_code {{ $tagRequired('country_code') }}" :label="__('mfw-google-places.country_code') . $labelRequired('country_code')"
                name="{{ $field }}[country_code]"
                value="{{ old($field . '.country_code', $geo->country_code) }}" />
        </div>
        <div class="col-sm-5 {{ $inputable('country') }} mb-3">
            <x-mfw-inputable::input class="field country {{ $tagRequired('country') }}" :label="__('mfw-google-places.country') . $labelRequired('country')"
                name="{{ $field }}[country]" value="{{ $countryName }}" :readonly="$readonlies('country')" />
        </div>
        <div class="w-100"></div>
        <div class="col-md-6 {{ $inputable('lat') }} mb-3">
            <x-mfw-inputable::input class="field lat mfw_google_places_lat" :label="__('mfw-google-places.latitude')"
                name="{{ $field }}[lat]" value="{{ old($field . '.lat', $geo->lat + 0) }}" />
        </div>
        <div class="col-md-6 {{ $inputable('lon') }} mb-3">
            <x-mfw-inputable::input class="field lon mfw_google_places_lon" :label="__('mfw-google-places.longitude')"
                name="{{ $field }}[lon]" value="{{ old($field . '.lon', $geo->lon + 0) }}" />
        </div>
    </div>
    @if ($fix)
        <div class="{{ $field }}_manual_fix mt-3">
            <x-mfw-inputable::checkbox class="mfw-google-places-manual-fix" name="{{ $field }}[manual_fix]"
                :label="__('mfw-google-places.manually_fix_address')" />
        </div>
    @endif
    <input type="hidden" class="place_id" name="{{ $field }}[place_id]"
        value="{{ $error ? old($field . '.place_id') : $geo->place_id ?? '' }}" />
    <input type="hidden" class="address_type" name="{{ $field }}[address_type]" />
    <input type="hidden" class="continent" name="{{ $field }}[continent]" />
</div>
@if ($params)
    <span id="params_mapsbar_{{ $random_id }}" class="d-none">{!! collect($params)->toJson() !!}</span>
@endif
@once
    @push('css')
        <style>
            /* Style Google Places PlaceAutocompleteElement to match Bootstrap form-control */
            gmp-place-autocomplete {
                display: block;
                width: 100%;
                --gmp-input-border: none;
            }
        </style>
    @endpush
    @push('js')
        @php($placesKey = config('mfw-google-places.google.places_api_key'))
        @if ($placesKey)
            <script>
                (g => {
                    var h, a, k, p = "The Google Maps JavaScript API",
                        c = "google",
                        l = "importLibrary",
                        q = "__ib__",
                        m = document,
                        b = window;
                    b = b[c] || (b[c] = {});
                    var d = b.maps || (b.maps = {}),
                        r = new Set,
                        e = new URLSearchParams,
                        u = () => h || (h = new Promise(async (f, n) => {
                            await (a = m.createElement("script"));
                            e.set("libraries", [...r]);
                            for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                            e.set("callback", c + ".maps." + q);
                            a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                            d[q] = f;
                            a.onerror = () => h = n(Error(p + " could not load."));
                            a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                            m.head.append(a)
                        }));
                    d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() =>
                        d[l](f, ...n))
                })({
                    key: "{{ $placesKey }}",
                    v: "weekly"
                });
            </script>
        @endif
        <script src="{{ asset('vendor/mfw-google-places/components/google-places-geolocate.js') }}"></script>
    @endpush
@endonce
