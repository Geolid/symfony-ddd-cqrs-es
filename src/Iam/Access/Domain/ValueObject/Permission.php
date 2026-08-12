<?php

declare(strict_types=1);

namespace Iam\Access\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Permission
{
    public const string PATTERN = '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*:[a-z][a-z0-9_]*$/';

    private string $value;

    private function __construct(string $value)
    {
        Assert::regex($value, self::PATTERN, 'A permission must be formatted "<subdomain>.<bc>:<action>", %s given.');
        Assert::maxLength($value, 64, 'A permission cannot exceed %2$d characters, %s given.');

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
