<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Application\TotpCredentialStatus;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialConfirmed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_totp_credentials')]
final readonly class DbalTotpCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_totp_credential';

    #[Subscribe(TotpCredentialEnrolled::class)]
    public function onTotpCredentialEnrolled(TotpCredentialEnrolled $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id->toString(),
            'identity_id' => $event->identityId,
            'encrypted_secret' => $event->encryptedSecret,
            'enrolled_at' => $event->enrolledAt,
            'status' => TotpCredentialStatus::PENDING->value,
        ], ['enrolled_at' => Types::DATETIME_IMMUTABLE]);
    }

    #[Subscribe(TotpCredentialConfirmed::class)]
    public function onTotpCredentialConfirmed(TotpCredentialConfirmed $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => TotpCredentialStatus::CONFIRMED->value, 'confirmed_at' => $event->confirmedAt],
            ['id' => $event->id->toString()],
            ['confirmed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(TotpCredentialRevoked::class)]
    public function onTotpCredentialRevoked(TotpCredentialRevoked $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => TotpCredentialStatus::REVOKED->value, 'revoked_at' => $event->revokedAt],
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
        $table->addColumn('encrypted_secret', Types::TEXT);
        $table->addColumn('enrolled_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addColumn('confirmed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['identity_id'], 'iam_authentication_totp_credential_identity_id_idx');
    }
}
