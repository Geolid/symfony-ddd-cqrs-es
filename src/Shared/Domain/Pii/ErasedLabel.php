<?php

declare(strict_types=1);

namespace Shared\Domain\Pii;

use Shared\Domain\ValueObject\Label;

final readonly class ErasedLabel
{
    public function __invoke(string $subjectId): Label
    {
        $value = (new ErasedFieldSentinel('erased-%s'))($subjectId);
        \assert(\is_string($value));

        return Label::fromString($value);
    }
}
