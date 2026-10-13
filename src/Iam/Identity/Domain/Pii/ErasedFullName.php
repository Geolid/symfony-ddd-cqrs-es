<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Pii;

use Iam\Identity\Domain\ValueObject\FullName;

final readonly class ErasedFullName
{
    public function __invoke(string $subjectId): FullName
    {
        return FullName::fromString('Erased');
    }
}
