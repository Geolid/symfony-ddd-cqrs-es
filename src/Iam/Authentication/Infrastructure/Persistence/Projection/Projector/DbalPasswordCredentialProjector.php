<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('iam.authentication.password_credentials')]
final readonly class DbalPasswordCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_password_credential';

    #[Subscribe(PasswordCredentialDefined::class)]
    public function onPasswordCredentialDefined(PasswordCredentialDefined $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'login' => $event->login,
            'password_hash' => $event->passwordHash,
            'identity_authenticatable' => true,
        ], ['identity_authenticatable' => Types::BOOLEAN]);
    }

    #[Subscribe(PasswordCredentialChanged::class)]
    public function onPasswordCredentialChanged(PasswordCredentialChanged $event): void
    {
        $this->connection->update(self::TABLE, ['password_hash' => $event->passwordHash], ['id' => $event->id]);
    }

    #[Subscribe(PasswordCredentialRehashed::class)]
    public function onPasswordCredentialRehashed(PasswordCredentialRehashed $event): void
    {
        $this->connection->update(self::TABLE, ['password_hash' => $event->passwordHash], ['id' => $event->id]);
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
        $table->addColumn('login', Types::STRING, ['length' => 255]);
        $table->addColumn('password_hash', Types::STRING, ['length' => 255]);
        $table->addColumn('identity_authenticatable', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['login'], 'iam_authentication_password_credential_login_unique');
    }
}
