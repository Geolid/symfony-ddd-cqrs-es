<?php

declare(strict_types=1);

namespace Sales\Buyer\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerErasureCancelled;
use Sales\Buyer\Domain\Event\BuyerErasureRequested;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Shared\Application\ErasureStatus;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.buyer.project_buyers')]
final readonly class DbalBuyerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_buyer';

    #[Subscribe(BuyerRegistered::class)]
    public function onBuyerRegistered(BuyerRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'email' => $event->email->value,
                'registered_at' => $event->registeredAt,
                'erasure_status' => ErasureStatus::RETAINED->value,
            ],
            ['registered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(BuyerErasureRequested::class)]
    public function onBuyerErasureRequested(BuyerErasureRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::PENDING->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(BuyerErasureCancelled::class)]
    public function onBuyerErasureCancelled(BuyerErasureCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::RETAINED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(BuyerErased::class)]
    public function onBuyerErased(BuyerErased $event): void
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
        $table->addColumn('email', Types::STRING, ['length' => 254]);
        $table->addColumn('registered_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('erasure_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['email'], 'sales_buyer_email_unique');
    }
}
