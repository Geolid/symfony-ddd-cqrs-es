<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrollmentConfirmed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentityReactivated\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\IntegrationEvent\IdentitySuspended\IdentitySuspendedIntegrationEvent;
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
            'confirmed' => false,
            'revoked' => false,
            'identity_authenticatable' => true,
        ], ['enrolled_at' => Types::DATETIME_IMMUTABLE, 'confirmed' => Types::BOOLEAN, 'revoked' => Types::BOOLEAN, 'identity_authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(TotpCredentialEnrollmentConfirmed::class)]
    public function onTotpCredentialEnrollmentConfirmed(TotpCredentialEnrollmentConfirmed $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['confirmed' => true, 'confirmed_at' => $event->confirmedAt],
            ['id' => $event->id->toString()],
            ['confirmed' => Types::BOOLEAN, 'confirmed_at' => Types::DATETIME_IMMUTABLE],
        );
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
        $table->addColumn('encrypted_secret', Types::TEXT);
        $table->addColumn('enrolled_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('confirmed', Types::BOOLEAN);
        $table->addColumn('confirmed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addColumn('revoked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('identity_authenticatable', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
