<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, status: string, reason: string|null, registered_at: string, suspended_at: string|null, reactivated_at: string|null}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnIdentityRegistered(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->withRegisteredAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
        self::assertNull($row['reason']);
        self::assertSame('2026-01-01 00:00:00', $row['registered_at']);
        self::assertNull($row['suspended_at']);
        self::assertNull($row['reactivated_at']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();
        $identity = IdentityTestFactory::new()->store();

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['status']);
        self::assertSame('Suspected fraudulent activity', $row['reason']);
        self::assertSame('2026-01-02 00:00:00', $row['suspended_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::ACTIVE->value, $otherRow['status']);
        self::assertNull($otherRow['reason']);
        self::assertNull($otherRow['suspended_at']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivated(): void
    {
        // Given
        $other = IdentityTestFactory::new()->suspended()->store();
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $identity->reactivate(Reason::fromString('Appeal upheld'), new \DateTimeImmutable('2026-01-03T00:00:00+00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
        self::assertSame('Appeal upheld', $row['reason']);
        self::assertSame('2026-01-03 00:00:00', $row['reactivated_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['status']);
        self::assertSame('Suspected fraudulent activity', $otherRow['reason']);
        self::assertNull($otherRow['reactivated_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErased(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();
        $identity = IdentityTestFactory::new()->store();

        // When
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT id, status, reason, registered_at, suspended_at, reactivated_at FROM %s WHERE id = :id', DbalIdentityProjector::TABLE),
            ['id' => $id],
        );
    }
}
