<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\PlaceSubjectErasureHold;

use Shared\Application\Command\CommandInterface;

final readonly class PlaceSubjectErasureHold implements CommandInterface
{
    public function __construct(
        public string $subjectId,
        public string $sourceType,
        public string $sourceId,
    ) {
    }
}
