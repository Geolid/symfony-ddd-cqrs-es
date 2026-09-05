<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Event\OrderAborted;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderDisputed;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderPrepared;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.order.project_orders')]
final readonly class DbalOrderProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order';

    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'buyer_id' => $event->buyerId,
                'total_amount_in_cents' => $event->totalAmountInCents,
                'status' => OrderStatus::PLACED->value,
                'placed_at' => $event->placedAt,
            ],
            ['placed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderCancelled::class)]
    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::CANCELLED->value,
                'cancelled_at' => $event->cancelledAt,
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderConfirmed::class)]
    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::CONFIRMED->value,
                'confirmed_at' => $event->confirmedAt,
            ],
            ['id' => $event->id],
            ['confirmed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderAborted::class)]
    public function onOrderAborted(OrderAborted $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::CANCELLED->value,
                'cancelled_at' => $event->abortedAt,
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderPrepared::class)]
    public function onOrderPrepared(OrderPrepared $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::PREPARED->value,
                'prepared_at' => $event->preparedAt,
            ],
            ['id' => $event->id],
            ['prepared_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderDispatched::class)]
    public function onOrderDispatched(OrderDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::DISPATCHED->value,
                'dispatched_at' => $event->dispatchedAt,
            ],
            ['id' => $event->id],
            ['dispatched_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderDelivered::class)]
    public function onOrderDelivered(OrderDelivered $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::DELIVERED->value,
                'delivered_at' => $event->deliveredAt,
            ],
            ['id' => $event->id],
            ['delivered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderReturnRequested::class)]
    public function onOrderReturnRequested(OrderReturnRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURN_REQUESTED->value,
                'return_requested_at' => $event->requestedAt,
            ],
            ['id' => $event->id],
            ['return_requested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderReturned::class)]
    public function onOrderReturned(OrderReturned $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURNED->value,
                'returned_at' => $event->returnedAt,
            ],
            ['id' => $event->id],
            ['returned_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderDisputed::class)]
    public function onOrderDisputed(OrderDisputed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::DISPUTED->value,
                'disputed_at' => $event->disputedAt,
            ],
            ['id' => $event->id],
            ['disputed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('buyer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 17]);
        $table->addColumn('placed_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('confirmed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('prepared_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_requested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('returned_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('disputed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['buyer_id'], 'sales_order_buyer_id_idx');
    }
}
