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
     * @param list<array{productId: string, label: string, quantity: int, unitAmountInCents: int}> $lines
     */
    public function __construct(
        #[DataSubjectId(name: 'billing_retention')]
        public string $id,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingFirstName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingLastName,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingStreet,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('00000'))]
        public string $shippingPostalCode,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $shippingCity,
        #[SensitiveData(subjectIdName: 'billing_retention', fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $billingFirstName,
        #[SensitiveData(subjectIdName: 'billing_retention', fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $billingLastName,
        #[SensitiveData(subjectIdName: 'billing_retention', fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $billingStreet,
        #[SensitiveData(subjectIdName: 'billing_retention', fallbackCallable: new ErasedFieldSentinel('00000'))]
        public string $billingPostalCode,
        #[SensitiveData(subjectIdName: 'billing_retention', fallbackCallable: new ErasedFieldSentinel('Erased'))]
        public string $billingCity,
        public array $lines,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
