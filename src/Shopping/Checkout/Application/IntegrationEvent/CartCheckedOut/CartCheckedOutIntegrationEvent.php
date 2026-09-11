<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CartCheckedOut;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.checkout.cart.checked_out')]
final readonly class CartCheckedOutIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines
     */
    public function __construct(
        public string $cartId,
        public string $shopperId,
        public array $lines,
        public int $totalAmountInCents,
        public \DateTimeImmutable $checkedOutAt,
    ) {
    }
}
