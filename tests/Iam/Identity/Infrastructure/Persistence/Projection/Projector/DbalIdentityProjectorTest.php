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
 * @phpstan-type Row array{id: string, status: string, registered_at: string}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheIdentityOnIdentityRegistered(): void
    {
        // When
        $identity = IdentityTestFactory::new()->store();

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);
    }

    #[Test]
    public function itUpdatesTheStatusOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();

        $identity = IdentityTestFactory::new()->store();

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::ACTIVE->value, $otherRow['status']);
    }

    #[Test]
    public function itUpdatesTheStatusOnIdentityReactivated(): void
    {
        // Given
        $other = IdentityTestFactory::new()->suspended()->store();

        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['status']);
    }

    #[Test]
    public function itDeletesOnIdentityErased(): void
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
            \sprintf('SELECT id, status, registered_at FROM %s WHERE id = :id', DbalIdentityProjector::TABLE),
            ['id' => $id],
        );
    }
}
