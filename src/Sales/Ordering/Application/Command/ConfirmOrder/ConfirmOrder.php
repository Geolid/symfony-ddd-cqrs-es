<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmOrder implements CommandInterface
{
    /**
     * @param list<array{productId: string, label: string, unitPriceInCents: int, quantity: int}>                                 $lines
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        public string $id,
        public string $cartId,
        public string $shopperId,
        public string $paymentId,
        public array $lines,
        public array $shippingAddress,
        public array $billingAddress,
    ) {
    }
}
