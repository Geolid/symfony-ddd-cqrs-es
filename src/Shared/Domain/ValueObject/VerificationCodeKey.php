<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class VerificationCodeKey
{
    private function __construct(
        public \BackedEnum $purpose,
        public string $subjectId,
    ) {
        Assert::stringNotEmpty($this->subjectId);
    }

    public static function for(\BackedEnum $purpose, string $subjectId): self
    {
        return new self($purpose, $subjectId);
    }

    public function toString(): string
    {
        return \sprintf('%s:%s', $this->purpose->value, $this->subjectId);
    }

    public function equals(self $other): bool
    {
        return $this->purpose === $other->purpose && $this->subjectId === $other->subjectId;
    }
}
