<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.order.placed')]
final readonly class OrderPlaced implements DomainEventInterface
{
    /**
     * @param list<array{label: string, quantity: int, unitAmountInCents: int}> $lines
     */
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased-address-%s'))]
        public string $buyerAddress,
        public array $lines,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
