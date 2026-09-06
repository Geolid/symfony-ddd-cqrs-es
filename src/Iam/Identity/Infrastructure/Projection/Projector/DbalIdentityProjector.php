<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\ValueObject\IdentityState;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.project_identities')]
final readonly class DbalIdentityProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity';

    #[Subscribe(IdentityRegistered::class)]
    public function onIdentityRegistered(IdentityRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'status' => IdentityState::ACTIVE->value,
                'registered_at' => $event->registeredAt,
            ],
            ['registered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(IdentitySuspended::class)]
    public function onIdentitySuspended(IdentitySuspended $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => IdentityState::SUSPENDED->value,
                'reason' => $event->reason->value,
                'suspended_at' => $event->suspendedAt,
                'reactivated_at' => null,
            ],
            ['id' => $event->id],
            ['suspended_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(IdentityReactivated::class)]
    public function onIdentityReactivated(IdentityReactivated $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => IdentityState::ACTIVE->value,
                'reason' => $event->reason->value,
                'reactivated_at' => $event->reactivatedAt,
                'suspended_at' => null,
            ],
            ['id' => $event->id],
            ['reactivated_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(IdentityErased::class)]
    public function onIdentityErased(IdentityErased $event): void
    {
        $this->connection->delete(self::TABLE, ['id' => $event->id]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addColumn('reason', Types::STRING, ['length' => Reason::MAX_LENGTH, 'notnull' => false]);
        $table->addColumn('registered_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('suspended_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('reactivated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
