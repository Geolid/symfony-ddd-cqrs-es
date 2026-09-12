<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Policy\StaleCheckoutSessionOnCartProductAdded;
use Shopping\Checkout\Domain\Cart\Event\CartProductAdded;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\SeededFaker;
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
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);
        $commandBus->expects(self::once())->method('dispatch')->with(new StaleCheckoutSession($checkoutSession->id->toString()));

        // When
        $this->trigger(StaleCheckoutSessionOnCartProductAdded::class, new CartProductAdded(
            CartId::fromString($checkoutSessionBuilder['cartId']),
            Uuid::uuid7()->toString(),
            Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
            Clock::get()->now(),
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
        $this->trigger(StaleCheckoutSessionOnCartProductAdded::class, new CartProductAdded(
            CartId::fromString(Uuid::uuid7()->toString()),
            Uuid::uuid7()->toString(),
            Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
            Clock::get()->now(),
        ));
    }
}
