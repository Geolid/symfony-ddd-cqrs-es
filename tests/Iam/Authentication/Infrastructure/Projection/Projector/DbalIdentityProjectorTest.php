<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Application\IdentityStatus;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{full_name: string, email: string, status: string}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnIdentityRegisteredIntegrationEvent(): void
    {
        // Given
        $builder = IdentityBuilder::new();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['fullName']->value, $row['full_name']);
        self::assertSame($builder['email']->value, $row['email']);
        self::assertSame(IdentityStatus::PENDING->value, $row['status']);
    }

    #[Test]
    public function itProjectsOnIdentityActivatedIntegrationEvent(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $identity = IdentityBuilder::new()->activated()->create();

        // When
        $this->store($other, $identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::PENDING->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $identity = IdentityBuilder::new()->activated()->suspended()->create();

        // When
        $this->store($other, $identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::PENDING->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivatedIntegrationEvent(): void
    {
        // Given
        $other = IdentityBuilder::new()->activated()->suspended()->create();
        $identity = IdentityBuilder::new()->activated()->suspended()->reactivated()->create();

        // When
        $this->store($other, $identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['status']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();

        // When
        $this->store($other, $identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $identityId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT full_name, email, status FROM %s WHERE identity_id = :identityId', DbalIdentityProjector::TABLE),
            ['identityId' => $identityId],
        );
    }
}
