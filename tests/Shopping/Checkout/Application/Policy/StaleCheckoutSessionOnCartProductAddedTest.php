<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Tests\Support\Factory\QuantityFactory;
use Shopping\Cart\Application\IntegrationEvent\CartProductAdded\CartProductAddedIntegrationEvent;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Policy\StaleCheckoutSessionOnCartProductAdded;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class StaleCheckoutSessionOnCartProductAddedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itStales(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);
        $commandBus->expects(self::once())->method('dispatch')->with(new StaleCheckoutSession($checkoutSession->id->toString()));

        // When
        $this->trigger(StaleCheckoutSessionOnCartProductAdded::class, new CartProductAddedIntegrationEvent(
            cartId: $checkoutSession->cartId,
            productId: Uuid::uuid7()->toString(),
            quantity: QuantityFactory::new()->create()->value,
            addedAt: Clock::get()->now(),
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
        $this->trigger(StaleCheckoutSessionOnCartProductAdded::class, new CartProductAddedIntegrationEvent(
            cartId: Uuid::uuid7()->toString(),
            productId: Uuid::uuid7()->toString(),
            quantity: QuantityFactory::new()->create()->value,
            addedAt: Clock::get()->now(),
        ));
    }
}
