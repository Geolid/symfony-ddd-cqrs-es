<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
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

        // Then
        $id = $orderPayment->id;
        self::assertTrue($this->repository->has($id));
        $reloaded = $this->repository->load($id);
        self::assertSame(
            [
                'id' => $id->toString(),
                'checkoutUrl' => $orderPayment->checkoutUrl,
            ],
            [
                'id' => $reloaded->id->toString(),
                'checkoutUrl' => $reloaded->checkoutUrl,
            ],
        );
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $id = OrderPaymentBuilder::new()->create()->id;

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
