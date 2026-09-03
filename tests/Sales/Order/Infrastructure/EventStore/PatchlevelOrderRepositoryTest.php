<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelOrderRepositoryTest extends AbstractIntegrationTestCase
{
    private OrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $order = OrderBuilder::new()->create();

        // When
        $this->repository->save($order);
        $loaded = $this->repository->load($order->id);

        // Then
        self::assertSame($order->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->repository->load(OrderId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->repository->save($order);

        // When
        $exists = $this->repository->has($order->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(OrderId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
