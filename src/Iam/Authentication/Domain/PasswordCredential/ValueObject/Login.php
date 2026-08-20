<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Login
{
    public string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        Assert::notEmpty($value, 'A login cannot be empty, %s given.');
        Assert::maxLength($value, 50, 'A login cannot exceed %2$d characters, %s given.');

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
