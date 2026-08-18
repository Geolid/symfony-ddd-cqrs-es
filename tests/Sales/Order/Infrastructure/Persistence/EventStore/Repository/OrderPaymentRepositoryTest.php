<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\Repository;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class OrderPaymentRepositoryTest extends AbstractIntegrationTestCase
{
    private OrderPaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderPaymentRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsSaved(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->create();

        // When
        $this->repository->save($orderPayment);

        // Then
        $id = $orderPayment->id;
        self::assertTrue($this->repository->has($id));
        $this->repository->load($id);
    }

    #[Test]
    public function itThrowsOnUnsaved(): void
    {
        // Given
        $id = OrderPaymentId::forOrder(Uuid::uuid7()->toString());

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
