<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialIssued;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_totp_credentials')]
final readonly class DbalTotpCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_totp_credential';

    #[Subscribe(TotpCredentialIssued::class)]
    public function onTotpCredentialIssued(TotpCredentialIssued $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id->toString(),
            'identity_id' => $event->identityId,
            'encrypted_secret' => $event->encryptedSecret,
            'issued_at' => $event->issuedAt,
            'revoked' => false,
        ], ['issued_at' => Types::DATETIME_IMMUTABLE, 'revoked' => Types::BOOLEAN]);
    }

    #[Subscribe(TotpCredentialRevoked::class)]
    public function onTotpCredentialRevoked(TotpCredentialRevoked $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['revoked' => true, 'revoked_at' => $event->revokedAt],
            ['id' => $event->id->toString()],
            ['revoked' => Types::BOOLEAN, 'revoked_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addColumn('encrypted_secret', Types::TEXT);
        $table->addColumn('issued_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['identity_id'], 'iam_authentication_totp_credential_identity_id_idx');
    }
}
