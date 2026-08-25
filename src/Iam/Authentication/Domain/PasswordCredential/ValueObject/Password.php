<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Password
{
    public const int MIN_LENGTH = 12;
    public const int MAX_LENGTH = 4096;

    public string $value;

    private function __construct(#[\SensitiveParameter] string $value)
    {
        Assert::minLength($value, self::MIN_LENGTH, 'A password must be at least %2$d characters.');
        Assert::maxLength($value, self::MAX_LENGTH, 'A password cannot exceed %2$d characters.');

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
}
