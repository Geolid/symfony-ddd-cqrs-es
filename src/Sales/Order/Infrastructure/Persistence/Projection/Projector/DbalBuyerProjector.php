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

#[Projector('sales.order.project_buyers')]
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
            ['shipping_address' => $this->normalizePostalAddress($event->address)],
            ['customer_id' => $event->customerId],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(CustomerBillingAddressRegisteredIntegrationEvent::class)]
    public function onCustomerBillingAddressRegistered(CustomerBillingAddressRegisteredIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['billing_address' => $this->normalizePostalAddress($event->address)],
            ['customer_id' => $event->customerId],
            ['billing_address' => Types::JSON],
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
        $table->addColumn('shipping_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('billing_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('customer_id'))
                ->create(),
        );
    }

    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     *
     * @return array{first_name: string, last_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function normalizePostalAddress(array $address): array
    {
        return [
            'first_name' => $address['firstName'],
            'last_name' => $address['lastName'],
            'street' => $address['street'],
            'postal_code' => $address['postalCode'],
            'city' => $address['city'],
            'country_code' => $address['countryCode'],
        ];
    }
}
