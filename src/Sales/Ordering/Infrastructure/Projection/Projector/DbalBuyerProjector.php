<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerBillingAddressDefined\BuyerBillingAddressDefinedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerErasureCancelled\BuyerErasureCancelledIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerErasureRequested\BuyerErasureRequestedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerRegistered\BuyerRegisteredIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressDefined\BuyerShippingAddressDefinedIntegrationEvent;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

#[Projector('sales.ordering.project_buyers')]
final readonly class DbalBuyerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_buyer';

    #[Subscribe(BuyerRegisteredIntegrationEvent::class)]
    public function onBuyerRegisteredIntegrationEvent(BuyerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'buyer_id' => $event->buyerId,
                'erasure_requested' => false,
            ],
            ['erasure_requested' => Types::BOOLEAN],
        );
    }

    #[Subscribe(BuyerShippingAddressDefinedIntegrationEvent::class)]
    public function onBuyerShippingAddressDefinedIntegrationEvent(BuyerShippingAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['shipping_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['buyer_id' => $event->buyerId],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(BuyerBillingAddressDefinedIntegrationEvent::class)]
    public function onBuyerBillingAddressDefinedIntegrationEvent(BuyerBillingAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['billing_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['buyer_id' => $event->buyerId],
            ['billing_address' => Types::JSON],
        );
    }

    #[Subscribe(BuyerErasedIntegrationEvent::class)]
    public function onBuyerErasedIntegrationEvent(BuyerErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['buyer_id' => $event->buyerId]);
    }

    #[Subscribe(BuyerErasureRequestedIntegrationEvent::class)]
    public function onBuyerErasureRequestedIntegrationEvent(BuyerErasureRequestedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_requested' => true],
            ['buyer_id' => $event->buyerId],
            ['erasure_requested' => Types::BOOLEAN],
        );
    }

    #[Subscribe(BuyerErasureCancelledIntegrationEvent::class)]
    public function onBuyerErasureCancelledIntegrationEvent(BuyerErasureCancelledIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_requested' => false],
            ['buyer_id' => $event->buyerId],
            ['erasure_requested' => Types::BOOLEAN],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('buyer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('billing_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('erasure_requested', Types::BOOLEAN, ['default' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('buyer_id'))
                ->create(),
        );
    }
}
