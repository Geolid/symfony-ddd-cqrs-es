<?php

declare(strict_types=1);

namespace Shared\Domain\Gdpr;

final readonly class ErasedValueObjectSentinel
{
    /**
     * @param ErasedFieldSentinel<mixed> $sentinel a single fallback value, or an array of one per
     *                                             constructor argument (nesting another sentinel
     *                                             for a Value Object composed of Value Objects)
     * @param class-string               $class
     */
    public function __construct(
        private ErasedFieldSentinel $sentinel,
        private string $class,
        private string $method,
    ) {
    }

    public function __invoke(string $subjectId): object
    {
        $value = ($this->sentinel)($subjectId);
        $args = \is_array($value) ? $value : [$value];

        return ($this->class)::{$this->method}(...$args);
    }
}
