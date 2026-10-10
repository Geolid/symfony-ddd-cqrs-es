<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedPostalAddress;

#[Event('integration.shopping.checkout.checkout_session.completed')]
final readonly class CheckoutSessionCompletedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<array{productId: string, label: string, unitPriceInCents: int, taxAmountInCents: int, quantity: int}>          $items
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        #[DataSubjectId]
        public string $checkoutSessionId,
        public string $cartId,
        public string $customerId,
        public array $items,
        public string $currency,
        #[SensitiveData(fallbackCallable: static function (string $subjectId): array {
            return PostalAddressMapper::toArray((new ErasedPostalAddress())());
        })]
        public array $shippingAddress,
        #[SensitiveData(fallbackCallable: static function (string $subjectId): array {
            return PostalAddressMapper::toArray((new ErasedPostalAddress())());
        })]
        public array $billingAddress,
        public string $paymentId,
        public \DateTimeImmutable $completedAt,
    ) {
    }
}
