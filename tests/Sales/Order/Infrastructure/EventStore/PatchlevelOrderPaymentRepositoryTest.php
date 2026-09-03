<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelOrderPaymentRepositoryTest extends AbstractIntegrationTestCase
{
    private OrderPaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderPaymentRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $orderPayment = OrderPaymentBuilder::new()->create();

        // When
        $this->repository->save($orderPayment);
        $loaded = $this->repository->load($orderPayment->id);

        // Then
        self::assertSame($orderPayment->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->repository->load(OrderPaymentId::forOrder(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $orderPayment = OrderPaymentBuilder::new()->create();
        $this->repository->save($orderPayment);

        // When
        $exists = $this->repository->has($orderPayment->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(OrderPaymentId::forOrder(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
