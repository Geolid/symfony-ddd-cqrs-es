<?php

declare(strict_types=1);

namespace Shared\Domain\Gdpr;

final readonly class ErasedFieldSentinel
{
    public function __construct(private string $template)
    {
    }

    public function __invoke(string $subjectId): string
    {
        return \sprintf($this->template, $subjectId);
    }
}
