<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\Command\ConvertCart\ConvertCart;
use Shopping\Checkout\Application\Policy\ConvertCartOnOrderConfirmed;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConvertCartOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConverts(): void
    {
        // Given
        $cartId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new ConvertCart($cartId));

        // When
        $this->trigger(ConvertCartOnOrderConfirmed::class, new OrderConfirmedIntegrationEvent(
            orderId: Uuid::uuid7()->toString(),
            cartId: $cartId,
            shopperId: Uuid::uuid7()->toString(),
            paymentId: Uuid::uuid7()->toString(),
            shippingAddress: ['recipientName' => 'John Doe', 'address' => ['street' => '1 rue de Paris', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']],
            confirmedAt: Clock::get()->now(),
        ));
    }
}
