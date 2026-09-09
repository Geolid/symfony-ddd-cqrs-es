<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConfirmOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Exception\OrderNotFoundException;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new ConfirmOrder($id));
    }
}
