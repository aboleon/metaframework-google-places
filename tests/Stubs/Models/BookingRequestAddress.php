<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRequestAddress extends Model
{
    protected $table = 'booking_request_addresses';

    protected $guarded = [];

    protected $fillable = [
        'text_address',
        'street_number',
        'route',
        'postal_code',
        'locality',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'country_code',
        'lat',
        'lon',
        'place_id',
    ];
}
