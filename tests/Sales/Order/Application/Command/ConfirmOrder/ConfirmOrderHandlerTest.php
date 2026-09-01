<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\ConfirmOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;

final class ConfirmOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itConfirmsWhenPlaced(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new ConfirmOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
        self::assertNotNull($result->confirmedAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyConfirmed(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->create();
        $this->store($order);

        // When
        $this->dispatch(new ConfirmOrder($order->id->toString()));

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
        $this->dispatch(new ConfirmOrder($id));
    }
}
