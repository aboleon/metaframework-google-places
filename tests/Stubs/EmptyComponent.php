<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Tests\Stubs;

use Illuminate\View\Component;

class EmptyComponent extends Component
{
    public function render(): \Closure
    {
        return static fn (): string => '';
    }
}
