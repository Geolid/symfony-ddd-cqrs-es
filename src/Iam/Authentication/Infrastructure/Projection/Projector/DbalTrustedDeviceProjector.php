<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceRevoked;
use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceTrusted;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_trusted_devices')]
final readonly class DbalTrustedDeviceProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_trusted_device';

    #[Subscribe(TrustedDeviceTrusted::class)]
    public function onTrustedDeviceTrusted(TrustedDeviceTrusted $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id->toString(),
                'identity_id' => $event->identityId,
                'version' => $event->version,
                'user_agent' => $event->userAgent,
                'ip' => $event->ip,
                'trusted_at' => $event->trustedAt,
            ],
            ['version' => Types::BIGINT, 'trusted_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(TrustedDeviceRevoked::class)]
    public function onTrustedDeviceRevoked(TrustedDeviceRevoked $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['revoked_at' => $event->revokedAt],
            ['id' => $event->id->toString()],
            ['revoked_at' => Types::DATETIME_IMMUTABLE],
        );
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
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('identity_id', Types::STRING, ['length' => 36]);
        $table->addColumn('version', Types::BIGINT);
        $table->addColumn('user_agent', Types::STRING, ['length' => 255]);
        $table->addColumn('ip', Types::STRING, ['length' => 45]);
        $table->addColumn('trusted_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['identity_id'], 'iam_authentication_trusted_device_identity_id_idx');
    }
}
