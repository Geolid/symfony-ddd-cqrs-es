<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

final readonly class RegisteredIdentity
{
    public function __construct(
        public string $id,
        public string $email,
    ) {
    }
}
