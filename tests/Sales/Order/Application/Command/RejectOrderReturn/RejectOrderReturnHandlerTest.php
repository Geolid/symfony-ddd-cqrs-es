<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RejectOrderReturn;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\RejectOrderReturn\RejectOrderReturn;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class RejectOrderReturnHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itRejectsReturnWhenRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        $this->dispatch(new RejectOrderReturn($order->id->toString(), 'item damaged beyond resale'));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURN_REJECTED, $result->status);
        self::assertSame('item damaged beyond resale', $result->returnRejectionReason);
    }

    #[Test]
    public function itIgnoresWhenNotRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->dispatch(new RejectOrderReturn($order->id->toString(), 'item damaged beyond resale'));

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
        $this->dispatch(new RejectOrderReturn($id, 'item damaged beyond resale'));
    }
}
