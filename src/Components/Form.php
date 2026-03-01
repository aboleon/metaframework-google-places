<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Throwable;

class Form extends Component
{
    public const DEFAULT_FIELD = 'mfw_google_places';

    public const MODE_DEFAULT = 'default';

    public const MODE_SEARCHBAR = 'searchbar';

    /**
     * Additional parameters for the Google Places JS URL.
     *
     * @example ['types' => '(cities)']
     */
    private const READONLY_FIELDS = ['street_number', 'route', 'locality', 'postal_code', 'country'];

    private const COORDINATE_FIELDS = ['lat', 'lon'];

    private const SEARCHBAR_HIDDEN_FIELDS = [
        'street_number',
        'route',
        'postal_code',
        'locality',
        'administrative_area_level_1',
        'administrative_area_level_1_short',
        'administrative_area_level_2',
        'country_code',
        'country',
        'lat',
        'lon',
    ];

    private const MODEL_FIELDS = [
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

    public ?string $defaultTextAddress = null;

    public ?string $error = null;

    public ?object $model = null;

    public Collection $required;

    public function __construct(
        object|string|null $model = null,
        public string $field = self::DEFAULT_FIELD,
        public string $random_id = '',
        public array $params = [],
        public string $placeholder = '',
        public string $tag_required = 'required',
        public ?string $label = null,
        public ?string $notice = null,
        // When true, show the manual-fix checkbox to unlock readonly address fields.
        public bool $fix = false,
        public array $hidden = [
            'administrative_area_level_1_short',
            'administrative_area_level_1',
            'administrative_area_level_2',
            'country_code',
        ],
        public bool $showCoords = false,
        public string $mode = self::MODE_DEFAULT,
    ) {
        $this->model = $this->resolveModel($model);
        $this->mode = $this->normalizeMode($this->mode);
        $this->required = collect($this->params['required'] ?? []);

        if (!$this->model) {
            $this->error = __('mfw-google-places.missing_model');

            return;
        }

        $missingFields = $this->validateModelFields($this->model);
        if ($missingFields) {
            $this->error = __('mfw-google-places.missing_fields', ['fields' => implode(', ', $missingFields)]);

            return;
        }

        $this->random_id = Str::random(4);
        $this->defaultTextAddress = $this->model->text_address ?? $this->model->locality;

        if (!$this->showCoords) {
            $this->hidden = array_merge($this->hidden, self::COORDINATE_FIELDS);
        }

        if ($this->isSearchbarMode()) {
            $this->hidden = array_values(array_unique(array_merge($this->hidden, self::SEARCHBAR_HIDDEN_FIELDS)));
        }
    }

    private function normalizeMode(string $mode): string
    {
        return in_array($mode, [self::MODE_DEFAULT, self::MODE_SEARCHBAR], true)
            ? $mode
            : self::MODE_DEFAULT;
    }

    private function resolveModel(object|string|null $model): ?object
    {
        if (is_object($model)) {
            return $model;
        }

        if (!is_string($model) || $model === '' || !class_exists($model)) {
            return null;
        }

        try {
            return new $model;
        } catch (Throwable) {
            return null;
        }
    }

    private function validateModelFields(object $model): array
    {
        $missing = [];

        foreach (self::MODEL_FIELDS as $field) {
            if (!$this->modelHasField($model, $field)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function modelHasField(object $model, string $field): bool
    {
        if (property_exists($model, $field)) {
            return true;
        }

        if (isset($model->$field)) {
            return true;
        }

        if (method_exists($model, 'getFillable')) {
            $fillable = $model->getFillable();
            if (is_array($fillable) && in_array($field, $fillable, true)) {
                return true;
            }
        }

        if (method_exists($model, 'getAttributes')) {
            $attributes = $model->getAttributes();
            if (is_array($attributes) && array_key_exists($field, $attributes)) {
                return true;
            }
        }

        if (method_exists($model, 'toArray')) {
            $data = $model->toArray();
            if (is_array($data) && array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    public function render(): View
    {
        if ($this->error) {
            return view('mfw-support::components.alert', [
                'type' => 'warning',
                'class' => '',
                'message' => $this->error,
            ]);
        }

        return view('mfw-google-places::components.form');
    }

    public function tagRequired(string $key): string
    {
        return $this->required->contains($key) ? $this->tag_required : '';
    }

    public function isSearchbarMode(): bool
    {
        return $this->mode === self::MODE_SEARCHBAR;
    }

    public function labelRequired(string $key): string
    {
        return $this->required->contains($key) ? ' *' : '';
    }

    public function inputable(string $key): string
    {
        return 'col-' . $key . (in_array($key, $this->hidden) ? ' d-none' : '');
    }

    public function readonlies(string $key): string
    {
        if ($this->defaultTextAddress || !in_array($key, self::READONLY_FIELDS)) {
            return '';
        }

        return ' lockable';
    }
}
