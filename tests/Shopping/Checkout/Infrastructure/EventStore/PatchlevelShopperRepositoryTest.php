<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelShopperRepositoryTest extends AbstractIntegrationTestCase
{
    private ShopperRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShopperRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();

        // When
        $this->repository->save($shopper);
        $loaded = $this->repository->load($shopper->id);

        // Then
        self::assertSame($shopper->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShopperNotFoundException::class);

        // When
        $this->repository->load(ShopperId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $this->repository->save($shopper);

        // When
        $exists = $this->repository->has($shopper->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ShopperId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
