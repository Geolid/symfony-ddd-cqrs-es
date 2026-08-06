<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
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
        $credential = PasswordCredentialTestFactory::new()->withLogin('buyer@example.com')->create();

        // When
        $this->repository->save($credential);

        // Then
        $id = $credential->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('buyer@example.com', $this->repository->load($id)->login()->toString());
    }

    #[Test]
    public function itThrowsOnAnUnsavedPasswordCredential(): void
    {
        // Given
        $id = PasswordCredentialId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
