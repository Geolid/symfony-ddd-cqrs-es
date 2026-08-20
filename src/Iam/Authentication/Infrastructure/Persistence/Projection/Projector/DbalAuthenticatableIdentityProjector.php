<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityRegisteredIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('iam.authentication.authenticatable_identities')]
final readonly class DbalAuthenticatableIdentityProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_authenticatable_identity';

    #[Subscribe(IdentityRegisteredIntegrationEvent::class)]
    public function onIdentityRegisteredIntegrationEvent(IdentityRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'identity_id' => $event->identityId,
            'authenticatable' => true,
        ], ['authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(IdentitySuspendedIntegrationEvent::class)]
    public function onIdentitySuspendedIntegrationEvent(IdentitySuspendedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['authenticatable' => false], ['identity_id' => $event->identityId], ['authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(IdentityReactivatedIntegrationEvent::class)]
    public function onIdentityReactivatedIntegrationEvent(IdentityReactivatedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['authenticatable' => true], ['identity_id' => $event->identityId], ['authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function onIdentityErasedIntegrationEvent(IdentityErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['identity_id' => $event->identityId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('identity_id', Types::STRING, ['length' => 36]);
        $table->addColumn('authenticatable', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('identity_id'))
                ->create(),
        );
    }
}
