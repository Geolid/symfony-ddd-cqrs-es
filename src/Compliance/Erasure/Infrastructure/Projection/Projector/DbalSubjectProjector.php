<?php

declare(strict_types=1);

namespace Compliance\Erasure\Infrastructure\Projection\Projector;

use Compliance\Erasure\Application\SubjectStatus;
use Compliance\Erasure\Domain\Event\HoldLifted;
use Compliance\Erasure\Domain\Event\HoldPlaced;
use Compliance\Erasure\Domain\Event\SubjectErased;
use Compliance\Erasure\Domain\Event\SubjectErasureCancelled;
use Compliance\Erasure\Domain\Event\SubjectErasureRequested;
use Compliance\Erasure\Domain\Event\SubjectRegistered;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('compliance.erasure.project_subjects')]
final readonly class DbalSubjectProjector extends AbstractDbalProjector
{
    public const string TABLE = 'compliance_erasure_subject';

    #[Subscribe(SubjectRegistered::class)]
    public function onSubjectRegistered(SubjectRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'status' => SubjectStatus::RETAINED->value,
                'requested_at' => null,
                'active_hold_count' => 0,
            ],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(HoldPlaced::class)]
    public function onHoldPlaced(HoldPlaced $event): void
    {
        $this->connection->executeStatement(
            \sprintf('UPDATE %s SET active_hold_count = active_hold_count + 1 WHERE id = :id', self::TABLE),
            ['id' => $event->id],
        );
    }

    #[Subscribe(HoldLifted::class)]
    public function onHoldLifted(HoldLifted $event): void
    {
        $this->connection->executeStatement(
            \sprintf('UPDATE %s SET active_hold_count = active_hold_count - 1 WHERE id = :id', self::TABLE),
            ['id' => $event->id],
        );
    }

    #[Subscribe(SubjectErasureRequested::class)]
    public function onSubjectErasureRequested(SubjectErasureRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => SubjectStatus::ERASING->value,
                'requested_at' => $event->requestedAt,
            ],
            ['id' => $event->id],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(SubjectErasureCancelled::class)]
    public function onSubjectErasureCancelled(SubjectErasureCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => SubjectStatus::RETAINED->value,
                'requested_at' => null,
            ],
            ['id' => $event->id],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(SubjectErased::class)]
    public function onSubjectErased(SubjectErased $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => SubjectStatus::ERASED->value],
            ['id' => $event->id],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('requested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('active_hold_count', Types::INTEGER, ['default' => 0]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
