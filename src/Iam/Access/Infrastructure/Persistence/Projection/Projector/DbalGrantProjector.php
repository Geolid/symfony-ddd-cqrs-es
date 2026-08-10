<?php

declare(strict_types=1);

namespace Iam\Access\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Access\Domain\Event\PermissionGranted;
use Iam\Access\Domain\Event\PermissionReactivated;
use Iam\Access\Domain\Event\PermissionRevoked;
use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.access.grants')]
final readonly class DbalGrantProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_access_grant';

    #[Subscribe(PermissionGranted::class)]
    public function onPermissionGranted(PermissionGranted $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'permission' => $event->permission,
            'revoked' => 0,
        ]);
    }

    #[Subscribe(PermissionRevoked::class)]
    public function onPermissionRevoked(PermissionRevoked $event): void
    {
        $this->connection->update(self::TABLE, ['revoked' => 1], ['id' => $event->id]);
    }

    #[Subscribe(PermissionReactivated::class)]
    public function onPermissionReactivated(PermissionReactivated $event): void
    {
        $this->connection->update(self::TABLE, ['revoked' => 0], ['id' => $event->id]);
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function onIdentityErased(IdentityErasedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, ['identity_id' => $event->identityId]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('identity_id', Types::STRING, ['length' => 36]);
        $table->addColumn('permission', Types::STRING, ['length' => 255]);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
