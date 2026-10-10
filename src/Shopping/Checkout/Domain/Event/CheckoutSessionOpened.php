<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedPostalAddress;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\TaxedAmount;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;

#[Event('shopping.checkout.checkout_session.opened')]
final readonly class CheckoutSessionOpened
{
    /**
     * @param list<CheckoutItem> $items
     */
    public function __construct(
        #[DataSubjectId]
        public string $id,
        public string $cartId,
        public string $customerId,
        public array $items,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddress())]
        public PostalAddress $shippingAddress,
        #[SensitiveData(fallbackCallable: new ErasedPostalAddress())]
        public PostalAddress $billingAddress,
        public TaxedAmount $total,
        public \DateTimeImmutable $openedAt,
    ) {
    }
}
