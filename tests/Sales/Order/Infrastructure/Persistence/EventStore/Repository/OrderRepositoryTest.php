<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\Repository;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class OrderRepositoryTest extends AbstractIntegrationTestCase
{
    private OrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsASavedOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->withBuyerAddress('buyer@example.com')->withTotalAmountInCents(2_500)->create();

        // When
        $this->repository->save($order);

        // Then
        $id = $order->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('buyer@example.com', $this->repository->load($id)->buyerAddress());
        self::assertSame(2_500, $this->repository->load($id)->totalAmount()->toCents());
    }

    #[Test]
    public function itThrowsOnAnUnsavedOrder(): void
    {
        // Given
        $id = OrderId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
