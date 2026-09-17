<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Application\IdentityStatus;
use Iam\Identity\Application\IntegrationEvent\IdentityActivated\IdentityActivatedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentityReactivated\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentityRegistered\IdentityRegisteredIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentitySuspended\IdentitySuspendedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_identities')]
final readonly class DbalIdentityProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_identity';

    #[Subscribe(IdentityRegisteredIntegrationEvent::class)]
    public function onIdentityRegisteredIntegrationEvent(IdentityRegisteredIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'identity_id' => $event->identityId,
            'full_name' => $event->fullName,
            'email' => $event->email,
            'status' => IdentityStatus::PENDING->value,
        ]);
    }

    #[Subscribe(IdentityActivatedIntegrationEvent::class)]
    public function onIdentityActivatedIntegrationEvent(IdentityActivatedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['status' => IdentityStatus::ACTIVE->value], ['identity_id' => $event->identityId]);
    }

    #[Subscribe(IdentitySuspendedIntegrationEvent::class)]
    public function onIdentitySuspendedIntegrationEvent(IdentitySuspendedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['status' => IdentityStatus::SUSPENDED->value], ['identity_id' => $event->identityId]);
    }

    #[Subscribe(IdentityReactivatedIntegrationEvent::class)]
    public function onIdentityReactivatedIntegrationEvent(IdentityReactivatedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['status' => IdentityStatus::ACTIVE->value], ['identity_id' => $event->identityId]);
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
        $table->addColumn('full_name', Types::STRING, ['length' => 200]);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('identity_id'))
                ->create(),
        );
    }
}
