<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('finance.payer.payer.registered')]
final readonly class PayerRegistered
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
