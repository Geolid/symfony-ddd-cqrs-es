<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Shared\Domain\FingerprintTrait;
use Webmozart\Assert\Assert;

final readonly class Email
{
    use FingerprintTrait;

    private string $value;

    private function __construct(string $value)
    {
        $value = strtolower(trim($value));
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

    public function toString(): string
    {
        return $this->value;
    }
}
