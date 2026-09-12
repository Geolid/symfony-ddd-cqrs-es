<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\CompleteCheckoutSession;

use Shared\Application\Command\CommandInterface;

final readonly class CompleteCheckoutSession implements CommandInterface
{
    /**
     * @param list<array{productId: string, label: string, unitPriceInCents: int, quantity: int}>                                 $items
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        public string $id,
        public string $cartId,
        public string $shopperId,
        public array $items,
        public array $shippingAddress,
        public array $billingAddress,
        public string $paymentId,
    ) {
    }
}
