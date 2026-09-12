<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\ConfirmOrderOnCheckoutSessionCompleted;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Support\SeededFaker;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmOrderOnCheckoutSessionCompletedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $cartId = Uuid::uuid7()->toString();
        $shopperId = Uuid::uuid7()->toString();
        $paymentId = Uuid::uuid7()->toString();
        $shippingAddress = PostalAddressMapper::toArray(PostalAddress::of('John Doe', Address::of('1 rue de Paris', '75001', 'Paris', 'FR')));
        $billingAddress = PostalAddressMapper::toArray(PostalAddress::of('John Doe', Address::of('2 rue de Paris', '75001', 'Paris', 'FR')));
        $items = [[
            'productId' => Uuid::uuid7()->toString(),
            'label' => SeededFaker::get()->sentence(3),
            'unitPriceInCents' => SeededFaker::get()->numberBetween(500, 5_000),
            'quantity' => SeededFaker::get()->numberBetween(1, 5),
        ]];

        // When
        $this->trigger(ConfirmOrderOnCheckoutSessionCompleted::class, new CheckoutSessionCompletedIntegrationEvent(
            checkoutSessionId: Uuid::uuid7()->toString(),
            cartId: $cartId,
            shopperId: $shopperId,
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            totalAmountInCents: SeededFaker::get()->numberBetween(500, 5_000),
            paymentId: $paymentId,
            completedAt: Clock::get()->now(),
        ));

        // Then
        $orderId = OrderId::forCart($cartId)->toString();
        $result = $this->service(OrderFinderInterface::class)->ofId($orderId);
        self::assertSame($shopperId, $result->shopperId);
        self::assertSame($paymentId, $result->paymentId);
        self::assertSame(
            $shippingAddress,
            ['recipientName' => $result->shippingAddress->recipientName, 'address' => (array) $result->shippingAddress->address],
        );
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
