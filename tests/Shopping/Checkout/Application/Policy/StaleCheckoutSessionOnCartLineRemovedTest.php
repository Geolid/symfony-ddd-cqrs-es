<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\Command\StaleCheckoutSession\StaleCheckoutSession;
use Shopping\Checkout\Application\Policy\StaleCheckoutSessionOnCartLineRemoved;
use Shopping\Checkout\Domain\Cart\Event\CartLineRemoved;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class StaleCheckoutSessionOnCartLineRemovedTest extends AbstractIntegrationTestCase
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
        $this->trigger(StaleCheckoutSessionOnCartLineRemoved::class, new CartLineRemoved(
            $checkoutSessionBuilder['cartId'],
            Uuid::uuid7()->toString(),
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
        $this->trigger(StaleCheckoutSessionOnCartLineRemoved::class, new CartLineRemoved(
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Clock::get()->now(),
        ));
    }
}
