<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

#[Projector('finance.payment.project_placed_orders')]
final readonly class DbalPlacedOrderProjector extends AbstractDbalProjector
{
    public const string TABLE = 'finance_payment_placed_order';

    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'order_id' => $event->orderId,
                'amount_in_cents' => $event->totalAmountInCents,
                'billing_address' => SnakeCaseKeys::from($event->billingAddress),
                'cancelled' => false,
            ],
            ['billing_address' => Types::JSON, 'cancelled' => Types::BOOLEAN],
        );
    }

    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function onOrderCancelled(OrderCancelledIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['cancelled' => true],
            ['order_id' => $event->orderId],
            ['cancelled' => Types::BOOLEAN],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('amount_in_cents', Types::INTEGER);
        $table->addColumn('billing_address', Types::JSON);
        $table->addColumn('cancelled', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
    }
}
