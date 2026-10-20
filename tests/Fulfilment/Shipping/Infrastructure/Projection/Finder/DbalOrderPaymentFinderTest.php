<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Finder;

use Fulfilment\Shipping\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalOrderPaymentFinderTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itFindsByOrder(): void
    {
        // Given
        $other = OrderFactory::new()->prepared()->create();
        $order = OrderFactory::new()->prepared()->paid()->create();
        $orderId = $order->id->toString();
        $this->store($other, $order);

        // When
        $found = $this->finder->ofOrderOrNull($orderId);

        // Then
        self::assertNotNull($found);
        self::assertSame($orderId, $found->orderId);
        self::assertTrue($found->paid);
    }

    #[Test]
    public function itFindsNothingByOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $result = $this->finder->ofOrderOrNull($orderId);

        // Then
        self::assertNull($result);
    }
}
