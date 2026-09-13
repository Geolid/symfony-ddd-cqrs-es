<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Email
{
    public string $value;

    private function __construct(string $value)
    {
        $value = strtolower(trim($value));
        Assert::email($value, 'An email address must be valid, %s given.');

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
