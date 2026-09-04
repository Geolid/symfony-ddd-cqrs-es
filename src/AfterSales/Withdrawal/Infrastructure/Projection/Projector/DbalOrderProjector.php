<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderDelivered\OrderDeliveredIntegrationEvent;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

/**
 * A local, read-only projection of `Sales.Order`, kept current solely to
 * decide withdrawal eligibility (a delivered order, its shipping address at
 * that moment) — never a general-purpose read of Order's own state.
 */
#[Projector('after_sales.withdrawal.project_orders')]
final readonly class DbalOrderProjector extends AbstractDbalProjector
{
    public const string TABLE = 'after_sales_withdrawal_order';

    #[Subscribe(OrderDeliveredIntegrationEvent::class)]
    public function onOrderDelivered(OrderDeliveredIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'shipping_address' => [
                    'recipient_name' => $event->shippingAddress['recipientName'],
                    'street' => $event->shippingAddress['street'],
                    'postal_code' => $event->shippingAddress['postalCode'],
                    'city' => $event->shippingAddress['city'],
                    'country_code' => $event->shippingAddress['countryCode'],
                ],
                'delivered_at' => $event->deliveredAt,
            ],
            ['shipping_address' => Types::JSON, 'delivered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('shipping_address', Types::JSON);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
    }
}
