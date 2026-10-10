<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\ConfirmOrderOnCheckoutSessionCompleted;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Tests\Ordering\Support\PostalAddressResultMapper;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

final class ConfirmOrderOnCheckoutSessionCompletedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $cartId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $checkoutSessionId = Uuid::uuid7()->toString();
        $shippingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());
        $billingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());
        $items = [[
            'productId' => Uuid::uuid7()->toString(),
            'label' => faker()->sentence(3),
            'unitPriceInCents' => faker()->numberBetween(500, 5_000),
            'taxAmountInCents' => faker()->numberBetween(50, 500),
            'quantity' => faker()->numberBetween(1, 5),
        ]];

        // When
        $this->trigger(ConfirmOrderOnCheckoutSessionCompleted::class, new CheckoutSessionCompletedIntegrationEvent(
            checkoutSessionId: $checkoutSessionId,
            cartId: $cartId,
            customerId: $customerId,
            items: $items,
            currency: 'EUR',
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            paymentId: Uuid::uuid7()->toString(),
            completedAt: Clock::get()->now(),
        ));

        // Then
        $orderId = OrderId::forCheckoutSession($checkoutSessionId)->toString();
        $result = $this->service(OrderFinderInterface::class)->ofId($orderId);
        self::assertSame($customerId, $result->customerId);
        self::assertSame($checkoutSessionId, $result->checkoutSessionId);
        self::assertSame(
            $shippingAddress,
            PostalAddressResultMapper::toArray($result->shippingAddress),
        );
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
