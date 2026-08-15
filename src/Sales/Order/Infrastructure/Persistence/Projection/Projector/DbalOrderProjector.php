<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Domain\Event\OrderBillingAddressErased;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderCompleted;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderPlaced;
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
            ['status' => OrderStatus::CONFIRMED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderDispatched::class)]
    public function onOrderDispatched(OrderDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderStatus::DISPATCHED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderCompleted::class)]
    public function onOrderCompleted(OrderCompleted $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => OrderStatus::COMPLETED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderBillingAddressErased::class)]
    public function onOrderBillingAddressErased(OrderBillingAddressErased $event): void
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
        $table->addColumn('customer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('placed_at', Types::DATETIME_MUTABLE);
        $table->addColumn('cancelled_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['customer_id'], 'sales_order_customer_id_idx');
    }
}
