<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\ValueObject;

use Webmozart\Assert\Assert;

final readonly class KeyId
{
    public const string PREFIX = 'key_';

    public string $value;

    private function __construct(string $value)
    {
        Assert::length($value, 20, 'A key ID must be exactly %2$d characters, %s given.');
        Assert::startsWith($value, self::PREFIX, 'A key ID must start with "'.self::PREFIX.'", %s given.');

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
