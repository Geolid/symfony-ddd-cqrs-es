<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Identity\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, status: string, reason: string|null, registered_at: string, suspended_at: string|null, reactivated_at: string|null}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itProjectsOnIdentityRegistered(): void
    {
        // Given
        $factory = IdentityTestFactory::new();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
        self::assertNull($row['reason']);
        self::assertSame($factory['registeredAt']->format(self::DATE_FORMAT), $row['registered_at']);
        self::assertNull($row['suspended_at']);
        self::assertNull($row['reactivated_at']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityTestFactory::new()->create();
        $this->store($other);

        $factory = IdentityTestFactory::new()->suspended();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['status']);
        self::assertSame($factory['reason']->value, $row['reason']);
        self::assertSame($factory['suspendedAt']->format(self::DATE_FORMAT), $row['suspended_at']);

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
        $otherFactory = IdentityTestFactory::new()->suspended();
        $other = $otherFactory->create();
        $this->store($other);

        $factory = IdentityTestFactory::new()->suspended()->reactivated();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
        self::assertSame($factory['reason']->value, $row['reason']);
        self::assertSame($factory['reactivatedAt']->format(self::DATE_FORMAT), $row['reactivated_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['status']);
        self::assertSame($otherFactory['reason']->value, $otherRow['reason']);
        self::assertNull($otherRow['reactivated_at']);
    }

    #[Test]
    public function itRemovesOnIdentityErased(): void
    {
        // Given
        $other = IdentityTestFactory::new()->create();
        $this->store($other);

        $identity = IdentityTestFactory::new()->erased()->create();

        // When
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
