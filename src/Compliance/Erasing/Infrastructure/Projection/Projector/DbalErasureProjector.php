<?php

declare(strict_types=1);

namespace Compliance\Erasing\Infrastructure\Projection\Projector;

use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Domain\Event\ErasureApproved;
use Compliance\Erasing\Domain\Event\ErasureCancelled;
use Compliance\Erasing\Domain\Event\ErasureRequested;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('compliance.erasing.project_erasures')]
final readonly class DbalErasureProjector extends AbstractDbalProjector
{
    public const string TABLE = 'compliance_erasing_erasure';

    #[Subscribe(ErasureRequested::class)]
    public function onErasureRequested(ErasureRequested $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id->toString(),
                'status' => ErasureRequestStatus::REQUESTED->value,
                'requested_at' => $event->requestedAt,
            ],
            ['requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ErasureCancelled::class)]
    public function onErasureCancelled(ErasureCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ErasureRequestStatus::CANCELLED->value,
                'cancelled_at' => $event->cancelledAt,
            ],
            ['id' => $event->id->toString()],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ErasureApproved::class)]
    public function onErasureApproved(ErasureApproved $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ErasureRequestStatus::APPROVED->value,
                'approved_at' => $event->approvedAt,
            ],
            ['id' => $event->id->toString()],
            ['approved_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('approved_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
