<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

final readonly class Credential
{
    public function __construct(
        public string $id,
        public string $email,
        public string $password,
        public string $fullName,
    ) {
    }
}
