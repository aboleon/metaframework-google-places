<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use MetaFramework\GooglePlaces\Validation\GoogleAddressValidation;

class GooglePlacesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return (new GoogleAddressValidation)
            ->setPrefix('address')
            ->setRequiredFields([])
            ->rules();
    }
}
