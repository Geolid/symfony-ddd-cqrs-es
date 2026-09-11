<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\Pii\ErasedValueObjectSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\Cart\Entity\Line;

#[Event('shopping.checkout.checkout_session.opened')]
final readonly class CheckoutSessionOpened
{
    /**
     * @param list<Line> $lines
     */
    public function __construct(
        #[DataSubjectId]
        public string $id,
        public string $cartId,
        public string $shopperId,
        public array $lines,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(
            new ErasedFieldSentinel([
                'erased',
                new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased', '00000', 'erased', 'ZZ']), Address::class, 'of'),
            ]),
            PostalAddress::class,
            'of',
        ))]
        public PostalAddress $shippingAddress,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(
            new ErasedFieldSentinel([
                'erased',
                new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased', '00000', 'erased', 'ZZ']), Address::class, 'of'),
            ]),
            PostalAddress::class,
            'of',
        ))]
        public PostalAddress $billingAddress,
        public int $totalAmountInCents,
        public \DateTimeImmutable $openedAt,
    ) {
    }
}
