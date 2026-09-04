<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelBuyerRepositoryTest extends AbstractIntegrationTestCase
{
    private BuyerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(BuyerRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();

        // When
        $this->repository->save($buyer);
        $loaded = $this->repository->load($buyer->id);

        // Then
        self::assertSame($buyer->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(BuyerNotFoundException::class);

        // When
        $this->repository->load(BuyerId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->repository->save($buyer);

        // When
        $exists = $this->repository->has($buyer->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(BuyerId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
