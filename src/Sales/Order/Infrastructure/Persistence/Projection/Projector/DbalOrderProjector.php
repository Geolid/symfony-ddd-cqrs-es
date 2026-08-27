<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Event\OrderAnonymized;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderCompleted;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Order\Domain\Event\OrderReturnRejected;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

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
                'customer_id' => $event->customerId,
                'total_amount_in_cents' => $event->totalAmountInCents,
                'status' => OrderStatus::PLACED->value,
                'placed_at' => new \DateTimeImmutable($event->placedAt),
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
                'cancelled_at' => new \DateTimeImmutable($event->cancelledAt),
                'closed_at' => new \DateTimeImmutable($event->cancelledAt),
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE, 'closed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderConfirmed::class)]
    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::CONFIRMED->value,
                'confirmed_at' => new \DateTimeImmutable($event->confirmedAt),
            ],
            ['id' => $event->id],
            ['confirmed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderDispatched::class)]
    public function onOrderDispatched(OrderDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::DISPATCHED->value,
                'dispatched_at' => new \DateTimeImmutable($event->dispatchedAt),
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
                'delivered_at' => new \DateTimeImmutable($event->deliveredAt),
            ],
            ['id' => $event->id],
            ['delivered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderCompleted::class)]
    public function onOrderCompleted(OrderCompleted $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::COMPLETED->value,
                'completed_at' => new \DateTimeImmutable($event->completedAt),
                'closed_at' => new \DateTimeImmutable($event->completedAt),
            ],
            ['id' => $event->id],
            ['completed_at' => Types::DATETIME_IMMUTABLE, 'closed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderReturnRequested::class)]
    public function onOrderReturnRequested(OrderReturnRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURN_REQUESTED->value,
                'return_requested_at' => new \DateTimeImmutable($event->requestedAt),
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
                'returned_at' => new \DateTimeImmutable($event->returnedAt),
                'closed_at' => new \DateTimeImmutable($event->returnedAt),
            ],
            ['id' => $event->id],
            ['returned_at' => Types::DATETIME_IMMUTABLE, 'closed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderReturnRejected::class)]
    public function onOrderReturnRejected(OrderReturnRejected $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURN_REJECTED->value,
                'return_rejected_at' => new \DateTimeImmutable($event->rejectedAt),
                'return_rejection_reason' => $event->reason,
                'closed_at' => new \DateTimeImmutable($event->rejectedAt),
            ],
            ['id' => $event->id],
            ['return_rejected_at' => Types::DATETIME_IMMUTABLE, 'closed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(OrderAnonymized::class)]
    public function onOrderAnonymized(OrderAnonymized $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['anonymized_at' => new \DateTimeImmutable($event->anonymizedAt)],
            ['id' => $event->id],
            ['anonymized_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 17]);
        $table->addColumn('placed_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('confirmed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('completed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_requested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('returned_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_rejected_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_rejection_reason', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('closed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('anonymized_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['customer_id'], 'sales_order_customer_id_idx');
    }
}
