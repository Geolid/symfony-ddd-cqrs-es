<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustNotFoundException;
use Iam\Authentication\Domain\DeviceTrust\Repository\DeviceTrustRepositoryInterface;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Iam\Tests\Authentication\Support\Builder\DeviceTrustBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelDeviceTrustRepositoryTest extends AbstractIntegrationTestCase
{
    private DeviceTrustRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(DeviceTrustRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $deviceTrust = DeviceTrustBuilder::new()->create();

        // When
        $this->repository->save($deviceTrust);
        $loaded = $this->repository->load($deviceTrust->id);

        // Then
        self::assertSame($deviceTrust->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(DeviceTrustNotFoundException::class);

        // When
        $this->repository->load(DeviceTrustId::forIdentity(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $deviceTrust = DeviceTrustBuilder::new()->create();
        $this->repository->save($deviceTrust);

        // When
        $exists = $this->repository->has($deviceTrust->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(DeviceTrustId::forIdentity(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
