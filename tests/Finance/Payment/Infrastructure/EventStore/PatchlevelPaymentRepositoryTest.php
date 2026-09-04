<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\EventStore;

use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelPaymentRepositoryTest extends AbstractIntegrationTestCase
{
    private PaymentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(PaymentRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->create();

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
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->repository->load(PaymentId::forOrder(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->create();
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
        $notExists = $this->repository->has(PaymentId::forOrder(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
