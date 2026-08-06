<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Access\Infrastructure\Persistence\Projection\Projector\DbalGrantProjector;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
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
        $grant = GrantTestFactory::new()->forIdentity('identity-1')->withPermission('sales:read')->create();
        $this->store($grant);

        // Then
        $row = $this->fetchRow($grant->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('identity-1', $row['identity_id']);
        self::assertSame('sales:read', $row['permission']);
        self::assertSame(0, (int) $row['revoked']);
    }

    #[Test]
    public function itMarksTheGrantAsRevokedOnPermissionRevoked(): void
    {
        // Given
        $other = GrantTestFactory::new()->create();
        $this->store($other);

        // When
        $grant = GrantTestFactory::new()->revoked()->create();
        $this->store($grant);

        // Then
        $row = $this->fetchRow($grant->id()->toString());
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
            \sprintf('SELECT identity_id, permission, revoked FROM %s WHERE id = :id', DbalGrantProjector::TABLE),
            ['id' => $id],
        );
    }
}
