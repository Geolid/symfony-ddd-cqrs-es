<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(PasswordCredentialRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsASavedPasswordCredential(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()->withLogin('operator')->withHasher(new DummySecretHasher())->create();

        // When
        $this->repository->save($credential);

        // Then
        $id = $credential->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('operator', $this->repository->load($id)->login()->toString());
    }

    #[Test]
    public function itThrowsOnAnUnsavedPasswordCredential(): void
    {
        // Given
        $id = PasswordCredentialId::forIdentity(Uuid::uuid7()->toString());

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
