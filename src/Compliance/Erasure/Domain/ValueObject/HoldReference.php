<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\ValueObject;

final readonly class HoldReference
{
    private function __construct(
        public string $sourceType,
        public string $sourceId,
    ) {
    }

    public static function for(string $sourceType, string $sourceId): self
    {
        return new self($sourceType, $sourceId);
    }

    public function equals(self $other): bool
    {
        return $this->sourceType === $other->sourceType && $this->sourceId === $other->sourceId;
    }

    public function toString(): string
    {
        return \sprintf('%s:%s', $this->sourceType, $this->sourceId);
    }
}
