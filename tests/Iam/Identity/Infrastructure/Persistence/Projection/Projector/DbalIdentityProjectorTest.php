<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, status: string, registered_at: string, erased_at: ?string}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheIdentityOnIdentityRegistered(): void
    {
        // When
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('active', $row['status']);
    }

    #[Test]
    public function itUpdatesTheStatusOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityTestFactory::new()->create();
        $this->store($other);

        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $identity->suspend(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('suspended', $row['status']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame('active', $otherRow['status']);
    }

    #[Test]
    public function itUpdatesTheStatusOnIdentityReactivated(): void
    {
        // Given
        $other = IdentityTestFactory::new()->suspended()->create();
        $this->store($other);

        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // When
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('active', $row['status']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame('suspended', $otherRow['status']);
    }

    #[Test]
    public function itUpdatesTheErasedAtOnIdentityErased(): void
    {
        // Given
        $other = IdentityTestFactory::new()->create();
        $this->store($other);

        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id()->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['erased_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['erased_at']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT id, status, registered_at, erased_at FROM %s WHERE id = :id', DbalIdentityProjector::TABLE),
            ['id' => $id],
        );
    }
}
