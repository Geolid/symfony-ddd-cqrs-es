<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedPostalAddressSentinel;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[Event('sales.order.order.placed')]
final readonly class OrderPlaced
{
    /**
     * @param list<array{productId: string, label: string, quantity: int, unitPriceInCents: int}> $lines
     */
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddressSentinel())]
        public PostalAddress $shippingAddress,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddressSentinel())]
        public PostalAddress $billingAddress,
        public array $lines,
        public Money $totalAmount,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
