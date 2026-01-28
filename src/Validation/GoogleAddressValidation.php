<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Validation;

use MetaFramework\GooglePlaces\Components\GooglePlaces;

class GoogleAddressValidation
{
    private string $prefix = GooglePlaces::DEFAULT_FIELD . '.';

    /**
     * @var array<int, string>
     */
    private array $requiredFields = [];

    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix . '.';

        return $this;
    }

    /**
     * @param  array<int, string>  $fields
     */
    public function setRequiredFields(array $fields): static
    {
        $this->requiredFields = $fields;

        return $this;
    }

    /**
     * @return array<string, array<int,string>>
     */
    public function rules(): array
    {
        $rules = [
            $this->prefix . 'street_number' => ['nullable', 'string'],
            $this->prefix . 'route' => ['nullable', 'string'],
            $this->prefix . 'locality' => ['nullable', 'string'],
            $this->prefix . 'postal_code' => ['nullable', 'string'],
            $this->prefix . 'country_code' => ['nullable', 'string'],
            $this->prefix . 'administrative_area_level_1' => ['nullable', 'string'],
            $this->prefix . 'administrative_area_level_1_short' => ['nullable', 'string'],
            $this->prefix . 'administrative_area_level_2' => ['nullable', 'string'],
            $this->prefix . 'text_address' => ['required', 'string'],
            $this->prefix . 'place_id' => ['nullable', 'string'],
            $this->prefix . 'lat' => ['nullable', 'numeric'],
            $this->prefix . 'lon' => ['nullable', 'numeric'],
        ];

        $required = array_unique(array_filter($this->requiredFields));
        foreach ($required as $field) {
            $key = $this->prefix . $field;
            if (!isset($rules[$key])) {
                $rules[$key] = ['required'];

                continue;
            }

            $current = $rules[$key];
            $current = is_array($current) ? $current : [$current];
            $current = array_values(array_filter($current, static fn ($rule) => $rule !== 'nullable'));
            array_unshift($current, 'required');
            $rules[$key] = $current;
        }

        return $rules;
    }

    /**
     * @return array<string,mixed>
     */
    public function messages(): array
    {
        return [
            $this->prefix . 'street_number.string' => __('validation.string', ['attribute' => strval(__('mfw-google-places.street_number'))]),
            $this->prefix . 'route.string' => __('validation.string', ['attribute' => strval(__('mfw-google-places.route'))]),
            $this->prefix . 'locality.string' => __('validation.string', ['attribute' => strval(__('mfw-google-places.locality'))]),
            $this->prefix . 'postal_code.string' => __('validation.string', ['attribute' => strval(__('mfw-google-places.postal_code'))]),
            $this->prefix . 'country_code.string' => __('validation.string', ['attribute' => strval(__('mfw-google-places.country_code'))]),
            $this->prefix . 'text_address.required' => __('validation.required', ['attribute' => strval(__('mfw-google-places.type_address'))]),
            $this->prefix . 'lat.numeric' => __('validation.numeric', ['attribute' => strval(__('mfw-google-places.latitude'))]),
            $this->prefix . 'lon.numeric' => __('validation.numeric', ['attribute' => strval(__('mfw-google-places.longitude'))]),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function logic(): array
    {
        return [
            'rules' => $this->rules(),
            'messages' => $this->messages(),
        ];
    }
}
