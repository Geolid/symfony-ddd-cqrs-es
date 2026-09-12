<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Checkout\Application\Policy\PurchaseCartOnCheckoutSessionCompleted;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionCompleted;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PurchaseCartOnCheckoutSessionCompletedTest extends AbstractIntegrationTestCase
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
        $this->trigger(PurchaseCartOnCheckoutSessionCompleted::class, new CheckoutSessionCompleted(
            id: Uuid::uuid7()->toString(),
            cartId: $cartId,
            shopperId: CheckoutSessionBuilder::sample('shopperId'),
            items: CheckoutSessionBuilder::sample('items'),
            shippingAddress: CheckoutSessionBuilder::sample('shippingAddress'),
            billingAddress: CheckoutSessionBuilder::sample('billingAddress'),
            totalAmount: Money::fromCents(1_000),
            paymentId: CheckoutSessionBuilder::sample('paymentId'),
            completedAt: Clock::get()->now(),
        ));
    }
}
