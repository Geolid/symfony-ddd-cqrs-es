<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\ConfirmOrderOnOrderPaymentAuthorized;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmOrderOnOrderPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        $this->trigger(ConfirmOrderOnOrderPaymentAuthorized::class, new OrderPaymentAuthorized(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
