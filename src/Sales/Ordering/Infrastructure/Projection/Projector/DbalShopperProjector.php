<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Checkout\Application\IntegrationEvent\ShopperBillingAddressDefined\ShopperBillingAddressDefinedIntegrationEvent;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErased\ShopperErasedIntegrationEvent;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErasureCancelled\ShopperErasureCancelledIntegrationEvent;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErasureRequested\ShopperErasureRequestedIntegrationEvent;
use Shopping\Checkout\Application\IntegrationEvent\ShopperRegistered\ShopperRegisteredIntegrationEvent;
use Shopping\Checkout\Application\IntegrationEvent\ShopperShippingAddressDefined\ShopperShippingAddressDefinedIntegrationEvent;

#[Projector('sales.ordering.project_shoppers')]
final readonly class DbalShopperProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_shopper';

    #[Subscribe(ShopperRegisteredIntegrationEvent::class)]
    public function onShopperRegisteredIntegrationEvent(ShopperRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'shopper_id' => $event->shopperId,
                'erasure_requested' => false,
            ],
            ['erasure_requested' => Types::BOOLEAN],
        );
    }

    #[Subscribe(ShopperShippingAddressDefinedIntegrationEvent::class)]
    public function onShopperShippingAddressDefinedIntegrationEvent(ShopperShippingAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['shipping_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['shopper_id' => $event->shopperId],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(ShopperBillingAddressDefinedIntegrationEvent::class)]
    public function onShopperBillingAddressDefinedIntegrationEvent(ShopperBillingAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['billing_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['shopper_id' => $event->shopperId],
            ['billing_address' => Types::JSON],
        );
    }

    #[Subscribe(ShopperErasedIntegrationEvent::class)]
    public function onShopperErasedIntegrationEvent(ShopperErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['shopper_id' => $event->shopperId]);
    }

    #[Subscribe(ShopperErasureRequestedIntegrationEvent::class)]
    public function onShopperErasureRequestedIntegrationEvent(ShopperErasureRequestedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_requested' => true],
            ['shopper_id' => $event->shopperId],
            ['erasure_requested' => Types::BOOLEAN],
        );
    }

    #[Subscribe(ShopperErasureCancelledIntegrationEvent::class)]
    public function onShopperErasureCancelledIntegrationEvent(ShopperErasureCancelledIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_requested' => false],
            ['shopper_id' => $event->shopperId],
            ['erasure_requested' => Types::BOOLEAN],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('shopper_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('billing_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addColumn('erasure_requested', Types::BOOLEAN, ['default' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('shopper_id'))
                ->create(),
        );
    }
}
