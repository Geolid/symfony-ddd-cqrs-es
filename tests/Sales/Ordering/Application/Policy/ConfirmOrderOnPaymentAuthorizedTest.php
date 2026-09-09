<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\ConfirmOrderOnPaymentAuthorized;
use Sales\Ordering\Domain\Cart\Event\CartConverted;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmOrderOnPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $cartBuilder = CartBuilder::new()->withBuyerId($buyer->id->toString())->lineAdded()->checkedOut();
        $cart = $cartBuilder->create();
        $this->store($buyer, $cart);
        $paymentId = Uuid::uuid7()->toString();

        // When
        $this->trigger(ConfirmOrderOnPaymentAuthorized::class, new PaymentAuthorizedIntegrationEvent($paymentId, $cart->id->toString(), Clock::get()->now()));

        // Then
        $orderId = OrderId::forCart($cart->id->toString())->toString();
        $result = $this->service(OrderFinderInterface::class)->ofId($orderId);
        self::assertSame($cartBuilder['buyerId'], $result->buyerId);
        self::assertSame($paymentId, $result->paymentId);
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
        $event = $this->publishedEventOf(CartConverted::class);
        self::assertSame($cart->id->toString(), $event->id);
    }
}
