<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payer.payer.erased')]
final readonly class PayerErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
