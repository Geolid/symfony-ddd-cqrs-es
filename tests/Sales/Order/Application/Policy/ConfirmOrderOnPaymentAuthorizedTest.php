<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\ConfirmOrderOnPaymentAuthorized;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmOrderOnPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        $this->trigger(ConfirmOrderOnPaymentAuthorized::class, new PaymentAuthorizedIntegrationEvent($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
