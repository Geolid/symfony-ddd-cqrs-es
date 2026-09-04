<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('sales.order.order.placed')]
final readonly class OrderPlaced
{
    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $shippingAddress
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $billingAddress
     * @param list<array{productId: string, label: string, quantity: int, unitPriceInCents: int}>                 $lines
     */
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $buyerId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
            'countryCode' => 'ZZ',
        ]))]
        public array $shippingAddress,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'street' => 'erased',
            'postalCode' => '00000',
            'city' => 'erased',
            'countryCode' => 'ZZ',
        ]))]
        public array $billingAddress,
        public array $lines,
        public int $totalAmountInCents,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
