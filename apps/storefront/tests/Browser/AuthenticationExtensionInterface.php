<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

/**
 * `AuthenticationExtension`'s own contract, carried by both browser backends — lets a caller
 * type a parameter/return on exactly this Extension, without pulling in an unrelated one.
 */
interface AuthenticationExtensionInterface
{
    public function signInAs(string $email, string $password): static;

    public function assertSignedIn(): static;

    public function assertNotSignedIn(): static;
}
