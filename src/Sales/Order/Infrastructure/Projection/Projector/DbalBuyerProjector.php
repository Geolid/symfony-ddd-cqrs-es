<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerRegistered\BuyerRegisteredIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressRegistered\BuyerShippingAddressRegisteredIntegrationEvent;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.order.project_buyers')]
final readonly class DbalBuyerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_buyer';

    #[Subscribe(BuyerRegisteredIntegrationEvent::class)]
    public function onBuyerRegisteredIntegrationEvent(BuyerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'buyer_id' => $event->buyerId,
        ]);
    }

    #[Subscribe(BuyerShippingAddressRegisteredIntegrationEvent::class)]
    public function onBuyerShippingAddressRegisteredIntegrationEvent(BuyerShippingAddressRegisteredIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['shipping_address' => $this->toAddressData($event->address)],
            ['buyer_id' => $event->buyerId],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(BuyerErasedIntegrationEvent::class)]
    public function onBuyerErasedIntegrationEvent(BuyerErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['buyer_id' => $event->buyerId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('buyer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('buyer_id'))
                ->create(),
        );
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     *
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function toAddressData(array $address): array
    {
        return [
            'recipient_name' => $address['recipientName'],
            'street' => $address['street'],
            'postal_code' => $address['postalCode'],
            'city' => $address['city'],
            'country_code' => $address['countryCode'],
        ];
    }
}
