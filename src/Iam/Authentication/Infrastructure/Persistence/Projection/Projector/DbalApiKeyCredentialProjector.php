<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('iam.authentication.api_key_credentials')]
final readonly class DbalApiKeyCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_api_key_credential';

    #[Subscribe(ApiKeyCredentialIssued::class)]
    public function onApiKeyCredentialIssued(ApiKeyCredentialIssued $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'label' => $event->label,
            'key_id' => $event->keyId,
            'secret_hash' => $event->secretHash,
            'issued_at' => new \DateTimeImmutable($event->issuedAt)->format('Y-m-d H:i:s'),
            'revoked' => false,
            'identity_authenticatable' => true,
        ], ['revoked' => Types::BOOLEAN, 'identity_authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(ApiKeyCredentialRevoked::class)]
    public function onApiKeyCredentialRevoked(ApiKeyCredentialRevoked $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['revoked' => true, 'revoked_at' => new \DateTimeImmutable($event->revokedAt)->format('Y-m-d H:i:s')],
            ['id' => $event->id],
            ['revoked' => Types::BOOLEAN],
        );
    }

    #[Subscribe(IdentitySuspendedIntegrationEvent::class)]
    public function onIdentitySuspendedIntegrationEvent(IdentitySuspendedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['identity_authenticatable' => false], ['identity_id' => $event->identityId], ['identity_authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(IdentityReactivatedIntegrationEvent::class)]
    public function onIdentityReactivatedIntegrationEvent(IdentityReactivatedIntegrationEvent $event): void
    {
        $this->connection->update(self::TABLE, ['identity_authenticatable' => true], ['identity_id' => $event->identityId], ['identity_authenticatable' => Types::BOOLEAN]);
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
        $table->addColumn('label', Types::STRING, ['length' => 255]);
        $table->addColumn('key_id', Types::STRING, ['length' => 20]);
        $table->addColumn('secret_hash', Types::STRING, ['length' => 64]);
        $table->addColumn('issued_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('identity_authenticatable', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['key_id'], 'iam_authentication_api_key_credential_key_id_unique');
    }
}
