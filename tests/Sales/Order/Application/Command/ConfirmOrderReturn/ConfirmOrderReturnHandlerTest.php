<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\ConfirmOrderReturn;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\ConfirmOrderReturn\ConfirmOrderReturn;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ConfirmOrderReturnHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itConfirmsTheReturnOnceRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested()->store();

        // When
        $this->dispatch(new ConfirmOrderReturn($order->id()->toString()));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::RETURNED, $result->status);
    }

    #[Test]
    public function itIgnoresAnOrderWithNoReturnRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->store();

        // When
        $this->dispatch(new ConfirmOrderReturn($order->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new ConfirmOrderReturn($id));
    }
}
