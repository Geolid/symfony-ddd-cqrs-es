<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Event\OrderCancelled;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Ordering\Domain\Order\Event\OrderDelivered;
use Sales\Ordering\Domain\Order\Event\OrderDispatched;
use Sales\Ordering\Domain\Order\Event\OrderErased;
use Sales\Ordering\Domain\Order\Event\OrderErasureApproved;
use Sales\Ordering\Domain\Order\Event\OrderFailed;
use Sales\Ordering\Domain\Order\Event\OrderPrepared;
use Shared\Application\ErasureStatus;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.ordering.project_orders')]
final readonly class DbalOrderProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering';

    #[Subscribe(OrderConfirmed::class)]
    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'shopper_id' => $event->shopperId,
                'payment_id' => $event->paymentId,
                'total_amount_in_cents' => $event->totalAmount->cents,
                'status' => OrderStatus::CONFIRMED->value,
                'confirmed_at' => $event->confirmedAt,
                'erasure_status' => ErasureStatus::RETAINED->value,
            ],
            ['confirmed_at' => Types::DATETIME_IMMUTABLE],
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

    #[Subscribe(OrderFailed::class)]
    public function onOrderFailed(OrderFailed $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => OrderStatus::FAILED->value,
                'failed_at' => $event->failedAt,
            ],
            ['id' => $event->id],
            ['failed_at' => Types::DATETIME_IMMUTABLE],
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

    #[Subscribe(OrderErasureApproved::class)]
    public function onOrderErasureApproved(OrderErasureApproved $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::APPROVED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderErased::class)]
    public function onOrderErased(OrderErased $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::ERASED->value],
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
        $table->addColumn('shopper_id', Types::STRING, ['length' => 64]);
        $table->addColumn('payment_id', Types::STRING, ['length' => 36]);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('confirmed_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('prepared_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('failed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('erasure_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['shopper_id'], 'sales_ordering_shopper_id_idx');
    }
}
