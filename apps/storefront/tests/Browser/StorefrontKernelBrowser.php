<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

use Zenstruck\Browser\KernelBrowser;

final class StorefrontKernelBrowser extends KernelBrowser implements AuthenticationExtensionInterface, RegistrationExtensionInterface
{
    use AuthenticationExtension;
    use RegistrationExtension;
}
