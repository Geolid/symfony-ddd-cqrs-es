<?php

declare(strict_types=1);

namespace Shared\Domain\Gdpr;

final readonly class ErasedValueObjectSentinel
{
    /**
     * @param ErasedFieldSentinel<string> $sentinel
     * @param class-string                $class
     */
    public function __construct(
        private ErasedFieldSentinel $sentinel,
        private string $class,
        private string $method,
    ) {
    }

    public function __invoke(string $subjectId): object
    {
        return ($this->class)::{$this->method}(($this->sentinel)($subjectId));
    }
}
