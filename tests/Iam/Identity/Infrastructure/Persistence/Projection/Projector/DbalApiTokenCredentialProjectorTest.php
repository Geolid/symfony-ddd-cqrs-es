<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenCredentialProjector;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, identity_id: string, identifier: string, label: string, hash: string, revoked: int, expires_at: string, identity_status: string}
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
            ->withLabel('CI pipeline')
            ->withHasher(new DummySecretHasher())
            ->store();

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame($identityId, $row['identity_id']);
        self::assertSame('key_abc123', $row['identifier']);
        self::assertSame('CI pipeline', $row['label']);
        self::assertSame(0, (int) $row['revoked']);
    }

    #[Test]
    public function itMarksTheCredentialAsRevokedOnApiTokenCredentialRevoked(): void
    {
        // Given
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->store();

        // When
        $credential = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->revoked()->store();

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
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->store();

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();
        $otherCredential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($other->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->store();

        $identity = IdentityTestFactory::new()->store();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->store();

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['identity_status']);

        $otherRow = $this->fetchRow($otherCredential->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::ACTIVE->value, $otherRow['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentityReactivated(): void
    {
        // Given
        $other = IdentityTestFactory::new()->suspended()->store();
        $otherCredential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($other->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->store();

        $identity = IdentityTestFactory::new()->suspended()->store();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->store();

        // When
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['identity_status']);

        $otherRow = $this->fetchRow($otherCredential->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['identity_status']);
    }

    #[Test]
    public function itProjectsTheNewHashOnApiTokenCredentialRehashed(): void
    {
        // Given
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->store();
        $otherHashBeforeRehash = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherHashBeforeRehash);

        $credential = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->store();
        $hashBeforeRehash = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($hashBeforeRehash);

        // When
        $credential->rehash('a new correct horse battery staple', new DummySecretHasher(), new \DateTimeImmutable('now +00:00'));
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertNotSame($hashBeforeRehash['hash'], $row['hash']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherHashBeforeRehash['hash'], $otherRow['hash']);
    }

    #[Test]
    public function itRemovesTheCredentialOnIdentityErased(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->store();
        $credential = ApiTokenCredentialTestFactory::new()->withIdentityId($identityId)->withHasher(new DummySecretHasher())->store();

        // When
        $this->service(DbalApiTokenCredentialProjector::class)->onIdentityErased(
            new IdentityErased($identityId, '2026-01-02T00:00:00+00:00'),
        );

        // Then
        self::assertFalse($this->fetchRow($credential->id()->toString()));
        self::assertNotFalse($this->fetchRow($other->id()->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT id, identity_id, identifier, label, hash, revoked, expires_at, identity_status FROM %s WHERE id = :id',
                DbalApiTokenCredentialProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
