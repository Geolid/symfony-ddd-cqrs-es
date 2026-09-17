<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Event\IdentityActivated;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\ErasureStatus;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.project_identities')]
final readonly class DbalIdentityProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity_identity';

    #[Subscribe(IdentityRegistered::class)]
    public function onIdentityRegistered(IdentityRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id->toString(),
                'full_name' => $event->fullName->value,
                'email' => $event->email->value,
                'status' => IdentityStatus::PENDING->value,
                'registered_at' => $event->registeredAt,
                'erasure_status' => ErasureStatus::RETAINED->value,
            ],
            ['registered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(IdentityActivated::class)]
    public function onIdentityActivated(IdentityActivated $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => IdentityStatus::ACTIVE->value],
            ['id' => $event->id->toString()],
        );
    }

    #[Subscribe(IdentitySuspended::class)]
    public function onIdentitySuspended(IdentitySuspended $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => IdentityStatus::SUSPENDED->value,
                'reason' => $event->reason->value,
                'suspended_at' => $event->suspendedAt,
                'reactivated_at' => null,
            ],
            ['id' => $event->id->toString()],
            ['suspended_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(IdentityReactivated::class)]
    public function onIdentityReactivated(IdentityReactivated $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => IdentityStatus::ACTIVE->value,
                'reason' => $event->reason->value,
                'reactivated_at' => $event->reactivatedAt,
                'suspended_at' => null,
            ],
            ['id' => $event->id->toString()],
            ['reactivated_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(IdentityErasureRequested::class)]
    public function onIdentityErasureRequested(IdentityErasureRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::REQUESTED->value],
            ['id' => $event->id->toString()],
        );
    }

    #[Subscribe(IdentityErasureCancelled::class)]
    public function onIdentityErasureCancelled(IdentityErasureCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::RETAINED->value],
            ['id' => $event->id->toString()],
        );
    }

    #[Subscribe(IdentityErased::class)]
    public function onIdentityErased(IdentityErased $event): void
    {
        $this->connection->delete(self::TABLE, ['id' => $event->id->toString()]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('full_name', Types::STRING, ['length' => FullName::MAX_LENGTH]);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addColumn('reason', Types::STRING, ['length' => Reason::MAX_LENGTH, 'notnull' => false]);
        $table->addColumn('registered_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('suspended_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('reactivated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('erasure_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['email'], 'iam_identity_identity_email_idx');
        $table->addIndex(['status', 'registered_at'], 'iam_identity_identity_status_registered_at_idx');
    }
}
