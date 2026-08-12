<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Access\Infrastructure\Persistence\Projection\Projector\DbalGrantProjector;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{identity_id: string, permission: string, revoked: int}
 */
final class DbalGrantProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheGrantOnPermissionGranted(): void
    {
        // When
        $identityId = Uuid::uuid7()->toString();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:read')->store();

        // Then
        $row = $this->fetchRow($grant->id()->toString());
        self::assertNotFalse($row);
        self::assertSame($identityId, $row['identity_id']);
        self::assertSame('fixture.widget:read', $row['permission']);
        self::assertSame(0, (int) $row['revoked']);
    }

    #[Test]
    public function itMarksTheGrantAsRevokedOnPermissionRevoked(): void
    {
        // Given
        $other = GrantTestFactory::new()->store();

        // When
        $grant = GrantTestFactory::new()->revoked()->store();

        // Then
        $row = $this->fetchRow($grant->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(1, (int) $row['revoked']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(0, (int) $otherRow['revoked']);
    }

    #[Test]
    public function itProjectsTheReactivationOnPermissionReactivated(): void
    {
        // Given
        $other = GrantTestFactory::new()->revoked()->store();
        $grant = GrantTestFactory::new()->revoked()->store();

        // When
        $grant->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($grant);

        // Then
        $row = $this->fetchRow($grant->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(0, (int) $row['revoked']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(1, (int) $otherRow['revoked']);
    }

    #[Test]
    public function itRemovesTheGrantOnIdentityErased(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $other = GrantTestFactory::new()->store();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->store();

        // When
        IdentityTestFactory::new()->withId($identityId)->erased()->store();

        // Then
        self::assertFalse($this->fetchRow($grant->id()->toString()));
        self::assertNotFalse($this->fetchRow($other->id()->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT identity_id, permission, revoked FROM %s WHERE id = :id', DbalGrantProjector::TABLE),
            ['id' => $id],
        );
    }
}
