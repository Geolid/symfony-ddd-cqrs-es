<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Cart\Application\IntegrationEvent\CartProductQuantityChanged\CartProductQuantityChangedIntegrationEvent;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Policy\StaleCheckoutSessionOnCartProductQuantityChanged;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\SeededFaker;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class StaleCheckoutSessionOnCartProductQuantityChangedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itStales(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);
        $commandBus->expects(self::once())->method('dispatch')->with(new StaleCheckoutSession($checkoutSession->id->toString()));

        // When
        $this->trigger(StaleCheckoutSessionOnCartProductQuantityChanged::class, new CartProductQuantityChangedIntegrationEvent(
            cartId: $checkoutSessionBuilder['cartId'],
            productId: Uuid::uuid7()->toString(),
            quantity: SeededFaker::get()->numberBetween(1, 5),
            changedAt: Clock::get()->now(),
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
        $this->trigger(StaleCheckoutSessionOnCartProductQuantityChanged::class, new CartProductQuantityChangedIntegrationEvent(
            cartId: Uuid::uuid7()->toString(),
            productId: Uuid::uuid7()->toString(),
            quantity: SeededFaker::get()->numberBetween(1, 5),
            changedAt: Clock::get()->now(),
        ));
    }
}
