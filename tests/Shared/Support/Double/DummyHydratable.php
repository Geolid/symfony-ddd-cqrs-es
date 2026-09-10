<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

final class DummyHydratable
{
    /**
     * @param list<DummyNestedObject>|null $objects
     */
    public function __construct(
        public ?\DateTimeImmutable $dateTime = null,
        public ?bool $boolean = null,
        public ?int $integer = null,
        public ?string $string = null,
        public ?DummyNestedObject $object = null,
        public ?array $objects = null,
        public ?DummyEnum $enum = null,
        public int|string|null $union = null,
    ) {
    }
}
