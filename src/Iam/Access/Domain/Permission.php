<?php

declare(strict_types=1);

namespace Iam\Access\Domain;

use Webmozart\Assert\Assert;

final readonly class Permission
{
    private const string PATTERN = '/^[a-z][a-z0-9_]*:[a-z][a-z0-9_]*$/';

    private string $value;

    private function __construct(string $value)
    {
        Assert::regex($value, self::PATTERN, 'A permission must be formatted "<subdomain>:<action>", %s given.');

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        return 1 === preg_match(self::PATTERN, $value);
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
