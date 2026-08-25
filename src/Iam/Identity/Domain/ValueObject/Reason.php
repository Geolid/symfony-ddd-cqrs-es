<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Reason
{
    public const int MAX_LENGTH = 255;

    public string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        Assert::notEmpty($value, 'A reason cannot be empty, %s given.');
        Assert::maxLength($value, self::MAX_LENGTH, 'A reason cannot exceed %2$d characters, %s given.');

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
