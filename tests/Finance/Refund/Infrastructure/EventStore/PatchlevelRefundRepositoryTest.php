<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Infrastructure\EventStore;

use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\Repository\RefundRepositoryInterface;
use Finance\Refund\Domain\ValueObject\RefundId;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelRefundRepositoryTest extends AbstractIntegrationTestCase
{
    private RefundRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(RefundRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $refund = RefundBuilder::new()->create();

        // When
        $this->repository->save($refund);
        $loaded = $this->repository->load($refund->id);

        // Then
        self::assertSame($refund->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(RefundNotFoundException::class);

        // When
        $this->repository->load(RefundId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $refund = RefundBuilder::new()->create();
        $this->repository->save($refund);

        // When
        $exists = $this->repository->has($refund->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(RefundId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
