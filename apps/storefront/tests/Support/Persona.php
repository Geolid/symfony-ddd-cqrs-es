<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Webmozart\Assert\Assert;

final readonly class Persona
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        private ?string $password = null,
        private ?string $totpSecret = null,
    ) {
    }

    public function password(): string
    {
        Assert::notNull($this->password, 'This persona has no password.');

        return $this->password;
    }

    public function totpSecret(): string
    {
        Assert::notNull($this->totpSecret, 'This persona has no TOTP secret.');

        return $this->totpSecret;
    }

    public function withTotpSecret(string $totpSecret): self
    {
        return clone ($this, ['totpSecret' => $totpSecret]);
    }
}
