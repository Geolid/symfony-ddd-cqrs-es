<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Sales\Ordering\Domain\Order\Entity\OrderLine;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\Pii\ErasedValueObjectSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\TaxedAmount;

#[Event('sales.ordering.order.confirmed')]
final readonly class OrderConfirmed
{
    /**
     * @param list<OrderLine> $lines
     */
    public function __construct(
        #[DataSubjectId]
        public OrderId $id,
        public string $cartId,
        public string $customerId,
        public string $checkoutSessionId,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(
            new ErasedFieldSentinel([
                'erased',
                new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased', '00000', 'erased', 'ZZ']), Address::class, 'of'),
            ]),
            PostalAddress::class,
            'of',
        ))]
        public PostalAddress $shippingAddress,
        public array $lines,
        public TaxedAmount $total,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
