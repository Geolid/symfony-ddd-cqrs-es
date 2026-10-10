<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Pii;

use Iam\Identity\Domain\ValueObject\Reason;

final readonly class ErasedReason
{
    public function __invoke(string $subjectId): Reason
    {
        return Reason::fromString('erased');
    }
}
