<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

/**
 * `RegistrationExtension`'s own contract, carried by both browser backends — lets a caller
 * type a parameter/return on exactly this Extension, without pulling in an unrelated one.
 */
interface RegistrationExtensionInterface
{
    public function goToRegister(string $email): static;
}
