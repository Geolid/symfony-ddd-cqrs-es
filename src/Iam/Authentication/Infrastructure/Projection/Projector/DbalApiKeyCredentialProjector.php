<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentityReactivated\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentitySuspended\IdentitySuspendedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_api_key_credentials')]
final readonly class DbalApiKeyCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_api_key_credential';

    #[Subscribe(ApiKeyCredentialIssued::class)]
    public function onApiKeyCredentialIssued(ApiKeyCredentialIssued $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'label' => $event->label->value,
            'key_id' => $event->keyId->value,
            'secret_hash' => $event->secretHash,
            'issued_at' => $event->issuedAt,
            'revoked' => false,
            'identity_authenticatable' => true,
        ], ['issued_at' => Types::DATETIME_IMMUTABLE, 'revoked' => Types::BOOLEAN, 'identity_authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(ApiKeyCredentialRevoked::class)]
    public function onApiKeyCredentialRevoked(ApiKeyCredentialRevoked $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['revoked' => true, 'revoked_at' => $event->revokedAt],
            ['id' => $event->id],
            ['revoked' => Types::BOOLEAN, 'revoked_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addColumn('key_id', Types::STRING, ['length' => KeyId::LENGTH]);
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
