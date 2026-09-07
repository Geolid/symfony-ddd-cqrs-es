<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Finder\Subject;

use Compliance\Erasure\Application\SubjectStatus;

final readonly class SubjectResult
{
    public function __construct(
        public string $id,
        public SubjectStatus $status,
        public ?\DateTimeImmutable $requestedAt,
        public int $activeHoldCount,
    ) {
    }
}
