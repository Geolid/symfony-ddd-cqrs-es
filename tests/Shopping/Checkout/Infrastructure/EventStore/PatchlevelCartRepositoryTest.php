<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelCartRepositoryTest extends AbstractIntegrationTestCase
{
    private CartRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CartRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $cart = CartBuilder::new()->create();

        // When
        $this->repository->save($cart);
        $loaded = $this->repository->load($cart->id);

        // Then
        self::assertSame($cart->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->repository->load(CartId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $cart = CartBuilder::new()->create();
        $this->repository->save($cart);

        // When
        $exists = $this->repository->has($cart->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(CartId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
