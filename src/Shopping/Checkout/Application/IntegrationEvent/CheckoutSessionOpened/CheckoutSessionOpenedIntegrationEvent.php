<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionOpened;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Pii\ErasedFieldSentinel;

#[Event('integration.shopping.checkout.checkout_session.opened')]
final readonly class CheckoutSessionOpenedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}>                 $lines
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $checkoutSessionId,
        public string $cartId,
        public string $shopperId,
        public array $lines,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'],
        ]))]
        public array $shippingAddress,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel([
            'recipientName' => 'erased',
            'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'],
        ]))]
        public array $billingAddress,
        public int $totalAmountInCents,
        public \DateTimeImmutable $openedAt,
    ) {
    }
}
