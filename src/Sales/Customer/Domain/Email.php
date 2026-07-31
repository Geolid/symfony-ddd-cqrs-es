<?php

declare(strict_types=1);

namespace Sales\Customer\Domain;

use Webmozart\Assert\Assert;

final readonly class Email
{
    private string $value;

    private function __construct(string $value)
    {
        $value = mb_strtolower(trim($value));
        Assert::notEmpty($value, 'An email address cannot be empty, %s given.');
        Assert::email($value, 'An email address is expected, %s given.');

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

    public function fingerprint(): string
    {
        return hash('sha256', $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
