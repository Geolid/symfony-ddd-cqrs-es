<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceAlreadyExistsException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\Repository\TrustedDeviceRepositoryInterface;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Iam\Tests\Authentication\Support\Factory\TrustedDeviceFactory;
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
        $trustedDevice = TrustedDeviceFactory::new()
            ->revoked()
            ->create();

        // When
        $this->repository->save($trustedDevice);
        $loaded = $this->repository->load($trustedDevice->id);

        // Then
        self::assertSame($this->propertiesOf($trustedDevice), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $trustedDevice = TrustedDeviceFactory::new()->create();
        $this->store($trustedDevice);
        $duplicate = TrustedDeviceFactory::new()->withId($trustedDevice->id->toString())->create();

        // Then
        $this->expectException(TrustedDeviceAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
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
        $trustedDevice = TrustedDeviceFactory::new()->create();
        $this->store($trustedDevice);

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

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(TrustedDevice $trustedDevice): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $trustedDevice->id->toString(),
            'identityId' => $trustedDevice->identityId,
            'userAgent' => $trustedDevice->userAgent,
            'ip' => $trustedDevice->ip,
            'trustedAt' => $atom($trustedDevice->trustedAt),
            'revoked' => $trustedDevice->revoked,
            'revokedAt' => $atom($trustedDevice->revokedAt),
        ];
    }
}
