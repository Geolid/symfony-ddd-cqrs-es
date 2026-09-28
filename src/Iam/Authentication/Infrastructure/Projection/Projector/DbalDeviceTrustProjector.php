<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\DeviceTrust\Event\DeviceTrustRevoked;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_device_trusts')]
final readonly class DbalDeviceTrustProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_device_trust';

    #[Subscribe(DeviceTrustRevoked::class)]
    public function onDeviceTrustRevoked(DeviceTrustRevoked $event): void
    {
        $updated = $this->connection->update(
            self::TABLE,
            ['revoked_at' => $event->revokedAt],
            ['identity_id' => $event->identityId],
            ['revoked_at' => Types::DATETIME_IMMUTABLE],
        );

        if (0 === $updated) {
            $this->connection->insert(self::TABLE, [
                'identity_id' => $event->identityId,
                'revoked_at' => $event->revokedAt,
            ], ['revoked_at' => Types::DATETIME_IMMUTABLE]);
        }
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
        $table->addColumn('revoked_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('identity_id'))
                ->create(),
        );
    }
}
