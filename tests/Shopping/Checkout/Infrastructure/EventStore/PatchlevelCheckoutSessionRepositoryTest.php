<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelCheckoutSessionRepositoryTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CheckoutSessionRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->create();

        // When
        $this->repository->save($checkoutSession);
        $loaded = $this->repository->load($checkoutSession->id);

        // Then
        self::assertSame($checkoutSession->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->repository->load(CheckoutSessionId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->create();
        $this->repository->save($checkoutSession);

        // When
        $exists = $this->repository->has($checkoutSession->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(CheckoutSessionId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
