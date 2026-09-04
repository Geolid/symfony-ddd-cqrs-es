<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Infrastructure\EventStore;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelWithdrawalRepositoryTest extends AbstractIntegrationTestCase
{
    private WithdrawalRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(WithdrawalRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->create();

        // When
        $this->repository->save($withdrawal);
        $loaded = $this->repository->load($withdrawal->id);

        // Then
        self::assertSame($withdrawal->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(WithdrawalNotFoundException::class);

        // When
        $this->repository->load(WithdrawalId::forOrder(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->create();
        $this->repository->save($withdrawal);

        // When
        $exists = $this->repository->has($withdrawal->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(WithdrawalId::forOrder(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
