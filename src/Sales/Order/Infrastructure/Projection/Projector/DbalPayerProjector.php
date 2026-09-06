<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payer\Application\IntegrationEvent\PayerErased\PayerErasedIntegrationEvent;
use Finance\Payer\Application\IntegrationEvent\PayerPostalAddressDefined\PayerPostalAddressDefinedIntegrationEvent;
use Finance\Payer\Application\IntegrationEvent\PayerRegistered\PayerRegisteredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

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

    #[Subscribe(PayerPostalAddressDefinedIntegrationEvent::class)]
    public function onPayerPostalAddressDefinedIntegrationEvent(PayerPostalAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['address' => SnakeCaseKeys::from($event->postalAddress)],
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
}
