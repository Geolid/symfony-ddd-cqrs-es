<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

use Zenstruck\Browser\PlaywrightBrowser;

final class StorefrontPlaywrightBrowser extends PlaywrightBrowser implements AuthenticationExtensionInterface, RegistrationExtensionInterface
{
    use AuthenticationExtension;
    use RegistrationExtension;
}
