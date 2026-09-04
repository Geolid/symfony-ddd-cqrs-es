<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Infrastructure\EventStore;

use Finance\Payer\Domain\Exception\PayerNotFoundException;
use Finance\Payer\Domain\Repository\PayerRepositoryInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelPayerRepositoryTest extends AbstractIntegrationTestCase
{
    private PayerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(PayerRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $payer = PayerBuilder::new()->create();

        // When
        $this->repository->save($payer);
        $loaded = $this->repository->load($payer->id);

        // Then
        self::assertSame($payer->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(PayerNotFoundException::class);

        // When
        $this->repository->load(PayerId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $payer = PayerBuilder::new()->create();
        $this->repository->save($payer);

        // When
        $exists = $this->repository->has($payer->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(PayerId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
