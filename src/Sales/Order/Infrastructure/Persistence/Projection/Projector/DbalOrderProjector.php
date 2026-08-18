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
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderRefundStarted;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Order\Domain\Event\OrderReturnRejected;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.order.orders')]
final readonly class DbalOrderProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order';

    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'customer_id' => $event->customerId,
            'total_amount_in_cents' => $event->totalAmountInCents,
            'status' => OrderStatus::PLACED->value,
            'placed_at' => new \DateTimeImmutable($event->placedAt)->format('Y-m-d H:i:s'),
        ]);
    }

    #[Subscribe(OrderCancelled::class)]
    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::CANCELLED->value,
                'cancelled_at' => new \DateTimeImmutable($event->cancelledAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderConfirmed::class)]
    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::CONFIRMED->value,
                'confirmed_at' => new \DateTimeImmutable($event->confirmedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderDispatched::class)]
    public function onOrderDispatched(OrderDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::DISPATCHED->value,
                'dispatched_at' => new \DateTimeImmutable($event->dispatchedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderCompleted::class)]
    public function onOrderCompleted(OrderCompleted $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::COMPLETED->value,
                'completed_at' => new \DateTimeImmutable($event->completedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderReturnRequested::class)]
    public function onOrderReturnRequested(OrderReturnRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURN_REQUESTED->value,
                'return_requested_at' => new \DateTimeImmutable($event->requestedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderRefundStarted::class)]
    public function onOrderRefundStarted(OrderRefundStarted $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::REFUNDING->value,
                'refund_started_at' => new \DateTimeImmutable($event->startedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderReturned::class)]
    public function onOrderReturned(OrderReturned $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURNED->value,
                'returned_at' => new \DateTimeImmutable($event->returnedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderReturnRejected::class)]
    public function onOrderReturnRejected(OrderReturnRejected $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::RETURN_REJECTED->value,
                'return_rejected_at' => new \DateTimeImmutable($event->rejectedAt)->format('Y-m-d H:i:s'),
                'return_rejection_reason' => $event->reason,
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderAnonymized::class)]
    public function onOrderAnonymized(OrderAnonymized $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['anonymized_at' => new \DateTimeImmutable($event->anonymizedAt)->format('Y-m-d H:i:s')],
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
        $table->addColumn('customer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 17]);
        $table->addColumn('placed_at', Types::DATETIME_MUTABLE);
        $table->addColumn('confirmed_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('completed_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_requested_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('refund_started_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('returned_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_rejected_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_rejection_reason', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('anonymized_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['customer_id'], 'sales_order_customer_id_idx');
    }
}
