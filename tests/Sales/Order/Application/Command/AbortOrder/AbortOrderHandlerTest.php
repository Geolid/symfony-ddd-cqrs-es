<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\AbortOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\AbortOrder\AbortOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class AbortOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itAbortsWhenPlaced(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new AbortOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenDispatched(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->create();
        $this->store($order);

        // When
        $this->dispatch(new AbortOrder($order->id->toString()));

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
        $this->dispatch(new AbortOrder($id));
    }
}
