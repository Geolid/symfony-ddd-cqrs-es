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

final class ConfirmOrderOnOrderPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    private ConfirmOrderOnOrderPaymentAuthorized $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(ConfirmOrderOnOrderPaymentAuthorized::class);
    }

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        ($this->policy)(new OrderPaymentAuthorized(Uuid::uuid7()->toString(), $order->id->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
