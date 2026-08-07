<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenCredentialProjector;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, identity_id: string, identifier: string, hash: string, revoked: int, expires_at: string}
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
        $other = ApiTokenCredentialTestFactory::new()->create();
        $this->store($other);

        // When
        $credential = ApiTokenCredentialTestFactory::new()->revoked()->create();
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(1, (int) $row['revoked']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(0, (int) $otherRow['revoked']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT id, identity_id, identifier, hash, revoked, expires_at FROM %s WHERE id = :id',
                DbalApiTokenCredentialProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
