<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

use Storefront\Tests\Browser\Capability\AuthenticationTrait;
use Storefront\Tests\Browser\Capability\TwoFactorTrait;
use Storefront\Tests\Browser\Capability\VisitsRouteTrait;
use Zenstruck\Browser\KernelBrowser;

final class StorefrontKernelBrowser extends KernelBrowser
{
    use AuthenticationTrait;
    use TwoFactorTrait;
    use VisitsRouteTrait;
}
