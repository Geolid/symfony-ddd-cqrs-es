<?php

declare(strict_types=1);

namespace Finance\Payer\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payer\Domain\Event\PayerErased;
use Finance\Payer\Domain\Event\PayerRegistered;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('finance.payer.project_payers')]
final readonly class DbalPayerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'finance_payer';

    #[Subscribe(PayerRegistered::class)]
    public function onPayerRegistered(PayerRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'registered_at' => $event->registeredAt,
            ],
            ['registered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PayerErased::class)]
    public function onPayerErased(PayerErased $event): void
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
        $table->addColumn('registered_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
