<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\Repository\TrustedDeviceRepositoryInterface;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelTrustedDeviceRepositoryTest extends AbstractIntegrationTestCase
{
    private TrustedDeviceRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(TrustedDeviceRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $trustedDevice = TrustedDeviceBuilder::new()->create();

        // When
        $this->repository->save($trustedDevice);
        $loaded = $this->repository->load($trustedDevice->id);

        // Then
        self::assertSame($trustedDevice->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(TrustedDeviceNotFoundException::class);

        // When
        $this->repository->load(TrustedDeviceId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $trustedDevice = TrustedDeviceBuilder::new()->create();
        $this->repository->save($trustedDevice);

        // When
        $exists = $this->repository->has($trustedDevice->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(TrustedDeviceId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
