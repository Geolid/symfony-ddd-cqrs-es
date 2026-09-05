<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payer\Application\IntegrationEvent\PayerAddressRegistered\PayerAddressRegisteredIntegrationEvent;
use Finance\Payer\Application\IntegrationEvent\PayerErased\PayerErasedIntegrationEvent;
use Finance\Payer\Application\IntegrationEvent\PayerRegistered\PayerRegisteredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.order.project_payers')]
final readonly class DbalPayerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_payer';

    #[Subscribe(PayerRegisteredIntegrationEvent::class)]
    public function onPayerRegisteredIntegrationEvent(PayerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'payer_id' => $event->payerId,
        ]);
    }

    #[Subscribe(PayerAddressRegisteredIntegrationEvent::class)]
    public function onPayerAddressRegisteredIntegrationEvent(PayerAddressRegisteredIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['address' => $this->addressRow($event->address)],
            ['payer_id' => $event->payerId],
            ['address' => Types::JSON],
        );
    }

    #[Subscribe(PayerErasedIntegrationEvent::class)]
    public function onPayerErasedIntegrationEvent(PayerErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['payer_id' => $event->payerId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('payer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('address', Types::JSON, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('payer_id'))
                ->create(),
        );
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     *
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function addressRow(array $address): array
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
