<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\LiftHold;

use Shared\Application\Command\CommandInterface;

final readonly class LiftHold implements CommandInterface
{
    public function __construct(
        public string $subjectId,
        public string $sourceType,
        public string $sourceId,
    ) {
    }
}
