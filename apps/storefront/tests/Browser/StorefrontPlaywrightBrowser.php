<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

use Storefront\Tests\Browser\Capability\AuthenticationTrait;
use Storefront\Tests\Browser\Capability\VisitsRouteTrait;
use Zenstruck\Browser\PlaywrightBrowser;

final class StorefrontPlaywrightBrowser extends PlaywrightBrowser
{
    use AuthenticationTrait;
    use VisitsRouteTrait;
}
