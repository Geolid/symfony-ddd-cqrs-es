<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenCredentialProjector;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, identity_id: string, identifier: string, hash: string, revoked: int, expires_at: string, identity_status: string}
 */
final class DbalApiTokenCredentialProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheCredentialOnApiTokenCredentialIssued(): void
    {
        // When
        $identityId = IdentityId::generate()->toString();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withIdentifier('key_abc123')
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame($identityId, $row['identity_id']);
        self::assertSame('key_abc123', $row['identifier']);
        self::assertSame(0, (int) $row['revoked']);
    }

    #[Test]
    public function itMarksTheCredentialAsRevokedOnApiTokenCredentialRevoked(): void
    {
        // Given
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->create();
        $this->store($other);

        // When
        $credential = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->revoked()->create();
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(1, (int) $row['revoked']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(0, (int) $otherRow['revoked']);
    }

    #[Test]
    public function itProjectsTheIdentityStatusOnApiTokenCredentialIssued(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // When
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('suspended', $row['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentitySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // When
        $identity->suspend(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('suspended', $row['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentityReactivated(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // When
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('active', $row['identity_status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT id, identity_id, identifier, hash, revoked, expires_at, identity_status FROM %s WHERE id = :id',
                DbalApiTokenCredentialProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
