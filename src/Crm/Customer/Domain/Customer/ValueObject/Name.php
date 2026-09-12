<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Name
{
    public const int MAX_LENGTH = 100;

    public string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        Assert::notEmpty($value, 'A name cannot be empty, %s given.');
        Assert::maxLength($value, self::MAX_LENGTH, 'A name cannot exceed %2$d characters, %s given.');

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
