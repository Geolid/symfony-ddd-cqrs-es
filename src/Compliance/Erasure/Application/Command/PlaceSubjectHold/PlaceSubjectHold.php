<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\PlaceSubjectHold;

use Shared\Application\Command\CommandInterface;

final readonly class PlaceSubjectHold implements CommandInterface
{
    public function __construct(
        public string $subjectId,
        public string $sourceType,
        public string $sourceId,
    ) {
    }
}
