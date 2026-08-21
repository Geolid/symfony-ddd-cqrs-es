<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\EventStore\Repository;

use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
    public function itSavesAndLoads(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->create();

        // When
        $this->repository->save($credential);

        // Then
        $id = $credential->id;
        self::assertTrue($this->repository->has($id));
        $this->repository->load($id);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $id = PasswordCredentialId::forIdentity(Uuid::uuid7()->toString());

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
