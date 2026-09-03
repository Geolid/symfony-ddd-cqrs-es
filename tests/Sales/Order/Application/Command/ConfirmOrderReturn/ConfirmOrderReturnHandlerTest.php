<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\ConfirmOrderReturn;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\ConfirmOrderReturn\ConfirmOrderReturn;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConfirmOrderReturnHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itConfirmsReturnWhenRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        $this->dispatch(new ConfirmOrderReturn($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURNED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->dispatch(new ConfirmOrderReturn($order->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderBuilder::new()->attribute('id')->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new ConfirmOrderReturn($id));
    }
}
