<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

final readonly class TotpEnabledCredential
{
    public function __construct(
        public string $email,
        public string $password,
        public string $totpSecret,
    ) {
    }
}
