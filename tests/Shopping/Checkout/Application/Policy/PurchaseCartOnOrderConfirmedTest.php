<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Checkout\Application\Policy\PurchaseCartOnOrderConfirmed;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PurchaseCartOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPurchases(): void
    {
        // Given
        $cartId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new PurchaseCart($cartId));

        // When
        $this->trigger(PurchaseCartOnOrderConfirmed::class, new OrderConfirmedIntegrationEvent(
            orderId: Uuid::uuid7()->toString(),
            cartId: $cartId,
            shopperId: Uuid::uuid7()->toString(),
            paymentId: Uuid::uuid7()->toString(),
            shippingAddress: ['recipientName' => 'John Doe', 'address' => ['street' => '1 rue de Paris', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']],
            confirmedAt: Clock::get()->now(),
        ));
    }
}
