<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Webmozart\Assert\Assert;

final readonly class Account
{
    /**
     * @param ?list<non-empty-string> $plainBackupCodes
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        private ?string $password = null,
        private ?string $totpSecret = null,
        private ?array $plainBackupCodes = null,
    ) {
    }

    public function password(): string
    {
        Assert::notNull($this->password);

        return $this->password;
    }

    public function totpSecret(): string
    {
        Assert::notNull($this->totpSecret);

        return $this->totpSecret;
    }

    /**
     * @return list<non-empty-string>
     */
    public function plainBackupCodes(): array
    {
        Assert::notNull($this->plainBackupCodes);

        return $this->plainBackupCodes;
    }
}
