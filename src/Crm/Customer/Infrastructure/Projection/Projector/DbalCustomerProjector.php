<?php

declare(strict_types=1);

namespace Crm\Customer\Infrastructure\Projection\Projector;

use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerErased;
use Crm\Customer\Domain\Customer\Event\CustomerErasureCancelled;
use Crm\Customer\Domain\Customer\Event\CustomerErasureRequested;
use Crm\Customer\Domain\Customer\Event\CustomerRegistered;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

#[Projector('crm.customer.project_customers')]
final readonly class DbalCustomerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'crm_customer';

    #[Subscribe(CustomerRegistered::class)]
    public function onCustomerRegistered(CustomerRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id->toString(),
                'first_name' => $event->firstName->value,
                'last_name' => $event->lastName->value,
                'email' => $event->email->value,
                'registered_at' => $event->registeredAt,
                'erasure_status' => ErasureStatus::RETAINED->value,
            ],
            ['registered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(CustomerShippingAddressDefined::class)]
    public function onCustomerShippingAddressDefined(CustomerShippingAddressDefined $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['shipping_address' => SnakeCaseKeys::from(PostalAddressMapper::toArray($event->postalAddress))],
            ['id' => $event->id->toString()],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(CustomerBillingAddressDefined::class)]
    public function onCustomerBillingAddressDefined(CustomerBillingAddressDefined $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['billing_address' => SnakeCaseKeys::from(PostalAddressMapper::toArray($event->postalAddress))],
            ['id' => $event->id->toString()],
            ['billing_address' => Types::JSON],
        );
    }

    #[Subscribe(CustomerErasureRequested::class)]
    public function onCustomerErasureRequested(CustomerErasureRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::REQUESTED->value],
            ['id' => $event->id->toString()],
        );
    }

    #[Subscribe(CustomerErasureCancelled::class)]
    public function onCustomerErasureCancelled(CustomerErasureCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::RETAINED->value],
            ['id' => $event->id->toString()],
        );
    }

    #[Subscribe(CustomerErased::class)]
    public function onCustomerErased(CustomerErased $event): void
    {
        $this->connection->delete(self::TABLE, ['id' => $event->id->toString()]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('first_name', Types::STRING, ['length' => 100]);
        $table->addColumn('last_name', Types::STRING, ['length' => 100]);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->addColumn('registered_at', Types::DATETIME_IMMUTABLE);
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
