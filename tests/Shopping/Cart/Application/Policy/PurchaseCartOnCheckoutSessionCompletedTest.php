<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Shopping\Cart\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Cart\Application\Policy\PurchaseCartOnCheckoutSessionCompleted;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Shopping\Tests\Cart\Support\Factory\CartIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PurchaseCartOnCheckoutSessionCompletedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPurchases(): void
    {
        // Given
        $cartId = CartIdFactory::new()->create()->toString();
        $checkoutSessionId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $paymentId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new PurchaseCart($cartId));
        $address = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());

        // When
        $this->trigger(PurchaseCartOnCheckoutSessionCompleted::class, new CheckoutSessionCompletedIntegrationEvent(
            checkoutSessionId: $checkoutSessionId,
            cartId: $cartId,
            customerId: $customerId,
            items: [],
            currency: 'EUR',
            shippingAddress: $address,
            billingAddress: $address,
            paymentId: $paymentId,
            completedAt: Clock::get()->now(),
        ));
    }
}
