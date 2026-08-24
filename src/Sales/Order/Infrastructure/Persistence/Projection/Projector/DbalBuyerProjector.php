<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\IntegrationEvent\CustomerBillingAddressRegistered\CustomerBillingAddressRegisteredIntegrationEvent;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\IntegrationEvent\CustomerRegistered\CustomerRegisteredIntegrationEvent;
use Sales\Customer\Application\IntegrationEvent\CustomerShippingAddressRegistered\CustomerShippingAddressRegisteredIntegrationEvent;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.order.buyers')]
final readonly class DbalBuyerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_buyer';

    #[Subscribe(CustomerRegisteredIntegrationEvent::class)]
    public function onCustomerRegistered(CustomerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'customer_id' => $event->customerId,
        ]);
    }

    #[Subscribe(CustomerShippingAddressRegisteredIntegrationEvent::class)]
    public function onCustomerShippingAddressRegistered(CustomerShippingAddressRegisteredIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'shipping_first_name' => $event->address['firstName'],
                'shipping_last_name' => $event->address['lastName'],
                'shipping_street' => $event->address['street'],
                'shipping_postal_code' => $event->address['postalCode'],
                'shipping_city' => $event->address['city'],
            ],
            ['customer_id' => $event->customerId],
        );
    }

    #[Subscribe(CustomerBillingAddressRegisteredIntegrationEvent::class)]
    public function onCustomerBillingAddressRegistered(CustomerBillingAddressRegisteredIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'billing_first_name' => $event->address['firstName'],
                'billing_last_name' => $event->address['lastName'],
                'billing_street' => $event->address['street'],
                'billing_postal_code' => $event->address['postalCode'],
                'billing_city' => $event->address['city'],
            ],
            ['customer_id' => $event->customerId],
        );
    }

    #[Subscribe(CustomerErasedIntegrationEvent::class)]
    public function onCustomerErased(CustomerErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['customer_id' => $event->customerId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('customer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_first_name', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('shipping_last_name', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('shipping_street', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('shipping_postal_code', Types::STRING, ['length' => 20, 'notnull' => false, 'default' => null]);
        $table->addColumn('shipping_city', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('billing_first_name', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('billing_last_name', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('billing_street', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('billing_postal_code', Types::STRING, ['length' => 20, 'notnull' => false, 'default' => null]);
        $table->addColumn('billing_city', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('customer_id'))
                ->create(),
        );
    }
}
