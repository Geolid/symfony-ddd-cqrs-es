<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

final class DummyValueObject
{
    private function __construct(
        public string $value,
        public ?self $nested = null,
    ) {
    }

    public static function of(string $value, ?self $nested = null): self
    {
        return new self($value, $nested);
    }
}
