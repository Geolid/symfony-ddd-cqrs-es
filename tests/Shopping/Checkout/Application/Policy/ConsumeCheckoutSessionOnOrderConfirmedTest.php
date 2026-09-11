<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\Command\ConsumeCheckoutSession\ConsumeCheckoutSession;
use Shopping\Checkout\Application\Policy\ConsumeCheckoutSessionOnOrderConfirmed;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConsumeCheckoutSessionOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConsumes(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);
        $commandBus->expects(self::once())->method('dispatch')->with(new ConsumeCheckoutSession($checkoutSession->id->toString()));

        // When
        $this->trigger(ConsumeCheckoutSessionOnOrderConfirmed::class, new OrderConfirmedIntegrationEvent(
            orderId: Uuid::uuid7()->toString(),
            cartId: $checkoutSessionBuilder['cartId'],
            shopperId: $checkoutSessionBuilder['shopperId'],
            paymentId: Uuid::uuid7()->toString(),
            shippingAddress: ['recipientName' => 'John Doe', 'address' => ['street' => '1 rue de Paris', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']],
            confirmedAt: Clock::get()->now(),
        ));
    }

    #[Test]
    public function itIgnoresWhenNoCheckoutSession(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::never())->method('dispatch');

        // When
        $this->trigger(ConsumeCheckoutSessionOnOrderConfirmed::class, new OrderConfirmedIntegrationEvent(
            orderId: Uuid::uuid7()->toString(),
            cartId: Uuid::uuid7()->toString(),
            shopperId: Uuid::uuid7()->toString(),
            paymentId: Uuid::uuid7()->toString(),
            shippingAddress: ['recipientName' => 'John Doe', 'address' => ['street' => '1 rue de Paris', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR']],
            confirmedAt: Clock::get()->now(),
        ));
    }
}
