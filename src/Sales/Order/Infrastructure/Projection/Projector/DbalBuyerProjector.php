<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Projector;

use Compliance\Erasure\Application\IntegrationEvent\SubjectErased\SubjectErasedIntegrationEvent;
use Compliance\Erasure\Application\IntegrationEvent\SubjectErasureCancelled\SubjectErasureCancelledIntegrationEvent;
use Compliance\Erasure\Application\IntegrationEvent\SubjectErasureRequested\SubjectErasureRequestedIntegrationEvent;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerPostalAddressDefined\BuyerPostalAddressDefinedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerRegistered\BuyerRegisteredIntegrationEvent;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

#[Projector('sales.order.project_buyers')]
final readonly class DbalBuyerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_order_buyer';

    #[Subscribe(BuyerRegisteredIntegrationEvent::class)]
    public function onBuyerRegisteredIntegrationEvent(BuyerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'buyer_id' => $event->buyerId,
                'erasure_pending' => false,
            ],
            ['erasure_pending' => Types::BOOLEAN],
        );
    }

    #[Subscribe(BuyerPostalAddressDefinedIntegrationEvent::class)]
    public function onBuyerPostalAddressDefinedIntegrationEvent(BuyerPostalAddressDefinedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['shipping_address' => SnakeCaseKeys::from($event->postalAddress)],
            ['buyer_id' => $event->buyerId],
            ['shipping_address' => Types::JSON],
        );
    }

    #[Subscribe(BuyerErasedIntegrationEvent::class)]
    public function onBuyerErasedIntegrationEvent(BuyerErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['buyer_id' => $event->buyerId]);
    }

    #[Subscribe(SubjectErasureRequestedIntegrationEvent::class)]
    public function onSubjectErasureRequestedIntegrationEvent(SubjectErasureRequestedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_pending' => true],
            ['buyer_id' => $event->subjectId],
            ['erasure_pending' => Types::BOOLEAN],
        );
    }

    #[Subscribe(SubjectErasureCancelledIntegrationEvent::class)]
    public function onSubjectErasureCancelledIntegrationEvent(SubjectErasureCancelledIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_pending' => false],
            ['buyer_id' => $event->subjectId],
            ['erasure_pending' => Types::BOOLEAN],
        );
    }

    #[Subscribe(SubjectErasedIntegrationEvent::class)]
    public function onSubjectErasedIntegrationEvent(SubjectErasedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_pending' => false],
            ['buyer_id' => $event->subjectId],
            ['erasure_pending' => Types::BOOLEAN],
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
        $table->addColumn('erasure_pending', Types::BOOLEAN, ['default' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('buyer_id'))
                ->create(),
        );
    }
}
