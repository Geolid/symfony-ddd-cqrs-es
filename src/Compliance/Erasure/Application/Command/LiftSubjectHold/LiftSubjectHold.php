<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\LiftSubjectHold;

use Shared\Application\Command\CommandInterface;

final readonly class LiftSubjectHold implements CommandInterface
{
    public function __construct(
        public string $subjectId,
        public string $sourceType,
        public string $sourceId,
    ) {
    }
}
