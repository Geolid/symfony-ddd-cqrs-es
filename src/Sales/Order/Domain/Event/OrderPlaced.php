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
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $shippingAddress
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $billingAddress
     * @param list<array{productId: string, label: string, quantity: int, unitAmountInCents: int}>         $lines
     */
    public function __construct(
        #[DataSubjectId(name: 'billing_retention')]
        public string $id,
        #[DataSubjectId]
        public string $customerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'Erased',
            'lastName' => 'Erased',
            'street' => 'Erased',
            'postalCode' => '00000',
            'city' => 'Erased',
        ]))]
        public array $shippingAddress,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'firstName' => 'Erased',
            'lastName' => 'Erased',
            'street' => 'Erased',
            'postalCode' => '00000',
            'city' => 'Erased',
        ]), subjectIdName: 'billing_retention')]
        public array $billingAddress,
        public array $lines,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
