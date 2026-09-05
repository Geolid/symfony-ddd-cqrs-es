<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\DisputeOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\DisputeOrder\DisputeOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DisputeOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itDisputesWhenReturnRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        $this->dispatch(new DisputeOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::DISPUTED, $result->status);
        self::assertNotNull($result->disputedAt);
    }

    #[Test]
    public function itIgnoresWhenNotReturnRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->dispatch(new DisputeOrder($order->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new DisputeOrder($id));
    }
}
