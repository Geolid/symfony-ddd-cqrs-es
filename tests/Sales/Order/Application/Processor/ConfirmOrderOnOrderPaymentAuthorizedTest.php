<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\ConfirmOrderOnOrderPaymentAuthorized;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ConfirmOrderOnOrderPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    private const string AUTHORIZED_AT = '2026-01-02T00:00:00+00:00';

    private ConfirmOrderOnOrderPaymentAuthorized $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ConfirmOrderOnOrderPaymentAuthorized::class);
    }

    #[Test]
    public function itConfirmsTheOrderOnOrderPaymentAuthorized(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // When
        ($this->processor)(new OrderPaymentAuthorized(Uuid::uuid7()->toString(), $order->id()->toString(), self::AUTHORIZED_AT));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }
}
