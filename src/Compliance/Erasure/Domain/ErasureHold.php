<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain;

use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;

final readonly class ErasureHold
{
    public function __construct(
        public ErasureHoldReference $reference,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
