<?php

declare(strict_types=1);

namespace Compliance\Erasure\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Application\IntegrationEvent\BuyerRegistered\BuyerRegisteredIntegrationEvent;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('compliance.erasure.project_buyers')]
final readonly class DbalBuyerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'compliance_erasure_buyer';

    #[Subscribe(BuyerRegisteredIntegrationEvent::class)]
    public function onBuyerRegisteredIntegrationEvent(BuyerRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'buyer_id' => $event->buyerId,
            'identity_id' => $event->identityId,
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('buyer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('identity_id', Types::STRING, ['length' => 36]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('buyer_id'))
                ->create(),
        );
    }
}
