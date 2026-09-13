<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Cart\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Cart\Application\Policy\PurchaseCartOnCheckoutSessionCompleted;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
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
        $address = PostalAddressMapper::toArray(PostalAddress::of('John Doe', Address::of('1 rue de Paris', '75001', 'Paris', 'FR')));

        // When
        $this->trigger(PurchaseCartOnCheckoutSessionCompleted::class, new CheckoutSessionCompletedIntegrationEvent(
            checkoutSessionId: Uuid::uuid7()->toString(),
            cartId: $cartId,
            customerId: Uuid::uuid7()->toString(),
            items: [],
            shippingAddress: $address,
            billingAddress: $address,
            totalAmountInCents: 1_000,
            paymentId: Uuid::uuid7()->toString(),
            completedAt: Clock::get()->now(),
        ));
    }
}
