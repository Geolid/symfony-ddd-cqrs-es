<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\ConfirmOrderOnPaymentAuthorized;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmOrderOnPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);
        $paymentId = Uuid::uuid7()->toString();

        // When
        $this->trigger(ConfirmOrderOnPaymentAuthorized::class, new PaymentAuthorizedIntegrationEvent($paymentId, $checkoutSession->id->toString(), Clock::get()->now()));

        // Then
        $orderId = OrderId::forCart($checkoutSessionBuilder['cartId'])->toString();
        $result = $this->service(OrderFinderInterface::class)->ofId($orderId);
        self::assertSame($checkoutSessionBuilder['shopperId'], $result->shopperId);
        self::assertSame($paymentId, $result->paymentId);
        self::assertSame(
            PostalAddressMapper::toArray($checkoutSessionBuilder['shippingAddress']),
            ['recipientName' => $result->shippingAddress->recipientName, 'address' => (array) $result->shippingAddress->address],
        );
        self::assertSame(
            PostalAddressMapper::toArray($checkoutSessionBuilder['billingAddress']),
            ['recipientName' => $result->billingAddress->recipientName, 'address' => (array) $result->billingAddress->address],
        );
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
