<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, full_name: string, email: string, verification_status: string, moderation_status: string, reason: string|null, registered_at: string, confirmation_requested_at: string, suspended_at: string|null, reactivated_at: string|null, erasure_status: string}
 */
final class DbalIdentityProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itProjectsOnIdentityRegistered(): void
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
        self::assertSame(IdentityVerificationStatus::PENDING->value, $row['verification_status']);
        self::assertSame(IdentityModerationStatus::ACTIVE->value, $row['moderation_status']);
        self::assertNull($row['reason']);
        self::assertSame($builder['registeredAt']->format(self::DATE_FORMAT), $row['registered_at']);
        self::assertSame($builder['registeredAt']->format(self::DATE_FORMAT), $row['confirmation_requested_at']);
        self::assertNull($row['suspended_at']);
        self::assertNull($row['reactivated_at']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
    }

    #[Test]
    public function itProjectsOnIdentityConfirmationRequested(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $this->store($other);

        $builder = IdentityBuilder::new()->confirmationRequested();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(
            $builder['confirmationRequestedAt']->format(self::DATE_FORMAT),
            $row['confirmation_requested_at'],
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherRow['registered_at'], $otherRow['confirmation_requested_at']);
    }

    #[Test]
    public function itProjectsOnIdentityConfirmed(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->confirmed()->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityVerificationStatus::CONFIRMED->value, $row['verification_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityVerificationStatus::PENDING->value, $otherRow['verification_status']);
    }

    #[Test]
    public function itProjectsOnIdentityFullNameChanged(): void
    {
        // Given
        $otherBuilder = IdentityBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);

        $newFullName = IdentityBuilder::sample('fullName')->value;
        $identity = IdentityBuilder::new()->fullNameChanged($newFullName)->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame($newFullName, $row['full_name']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['fullName']->value, $otherRow['full_name']);
    }

    #[Test]
    public function itProjectsOnIdentityEmailChanged(): void
    {
        // Given
        $otherBuilder = IdentityBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);

        $newEmail = IdentityBuilder::sample('email')->value;
        $identity = IdentityBuilder::new()->emailChanged($newEmail)->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame($newEmail, $row['email']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['email']->value, $otherRow['email']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $this->store($other);

        $builder = IdentityBuilder::new()->confirmed()->suspended()->reactivated()->suspended();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityModerationStatus::SUSPENDED->value, $row['moderation_status']);
        self::assertSame($builder['reason']->value, $row['reason']);
        self::assertSame($builder['suspendedAt']->format(self::DATE_FORMAT), $row['suspended_at']);
        self::assertNull($row['reactivated_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityModerationStatus::ACTIVE->value, $otherRow['moderation_status']);
        self::assertNull($otherRow['reason']);
        self::assertNull($otherRow['suspended_at']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivated(): void
    {
        // Given
        $otherBuilder = IdentityBuilder::new()->confirmed()->suspended();
        $other = $otherBuilder->create();
        $this->store($other);

        $builder = IdentityBuilder::new()->confirmed()->suspended()->reactivated();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityModerationStatus::ACTIVE->value, $row['moderation_status']);
        self::assertSame($builder['reason']->value, $row['reason']);
        self::assertSame($builder['reactivatedAt']->format(self::DATE_FORMAT), $row['reactivated_at']);
        self::assertNull($row['suspended_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityModerationStatus::SUSPENDED->value, $otherRow['moderation_status']);
        self::assertSame($otherBuilder['reason']->value, $otherRow['reason']);
        self::assertSame($otherBuilder['suspendedAt']->format(self::DATE_FORMAT), $otherRow['suspended_at']);
        self::assertNull($otherRow['reactivated_at']);
    }

    #[Test]
    public function itProjectsOnIdentityErasureRequested(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $this->store($other);
        $identity = IdentityBuilder::new()->erasureRequested()->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::REQUESTED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itProjectsOnIdentityErasureCancelled(): void
    {
        // Given
        $other = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($other);
        $identity = IdentityBuilder::new()->erasureRequested()->erasureCancelled()->create();

        // When
        $this->store($identity);

        // Then
        $row = $this->fetchRow($identity->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::REQUESTED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itRemovesOnIdentityErased(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();

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
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT id, full_name, email, verification_status, moderation_status, reason, registered_at, confirmation_requested_at, suspended_at, reactivated_at, erasure_status FROM %s WHERE id = :id', DbalIdentityProjector::TABLE),
            ['id' => $id],
        );
    }
}
