<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Password
{
    private string $value;

    private function __construct(#[\SensitiveParameter] string $value)
    {
        Assert::minLength($value, 12, 'A password must be at least %2$d characters.');
        Assert::maxLength($value, 4096, 'A password cannot exceed %2$d characters.');

        $this->value = $value;
    }

    public static function fromString(#[\SensitiveParameter] string $value): self
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
