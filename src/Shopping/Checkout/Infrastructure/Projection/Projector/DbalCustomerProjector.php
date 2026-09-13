<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Projector;

use Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined\CustomerBillingAddressDefinedIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerErasureCancelled\CustomerErasureCancelledIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerErasureRequested\CustomerErasureRequestedIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerRegistered\CustomerRegisteredIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerShippingAddressDefined\CustomerShippingAddressDefinedIntegrationEvent;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\ErasureStatus;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

#[Projector('shopping.checkout.project_customers')]
final readonly class DbalCustomerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_customer';

    #[Subscribe(CustomerRegisteredIntegrationEvent::class)]
    public function onCustomerRegistered(CustomerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->customerId,
                'erasure_status' => ErasureStatus::RETAINED->value,
            ],
        );
    }

    #[Subscribe(CustomerShippingAddressDefinedIntegrationEvent::class)]
    public function onCustomerShippingAddressDefined(CustomerShippingAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['shipping_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['id' => $event->customerId],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(CustomerBillingAddressDefinedIntegrationEvent::class)]
    public function onCustomerBillingAddressDefined(CustomerBillingAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['billing_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['id' => $event->customerId],
            ['billing_address' => Types::JSON],
        );
    }

    #[Subscribe(CustomerErasureRequestedIntegrationEvent::class)]
    public function onCustomerErasureRequested(CustomerErasureRequestedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::REQUESTED->value],
            ['id' => $event->customerId],
        );
    }

    #[Subscribe(CustomerErasureCancelledIntegrationEvent::class)]
    public function onCustomerErasureCancelled(CustomerErasureCancelledIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::RETAINED->value],
            ['id' => $event->customerId],
        );
    }

    #[Subscribe(CustomerErasedIntegrationEvent::class)]
    public function onCustomerErased(CustomerErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['id' => $event->customerId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('billing_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('erasure_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
