<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_password_credentials')]
final readonly class DbalPasswordCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_password_credential';

    #[Subscribe(PasswordCredentialDefined::class)]
    public function onPasswordCredentialDefined(PasswordCredentialDefined $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id->toString(),
            'identity_id' => $event->identityId,
            'password_hash' => $event->passwordHash,
            'defined_at' => $event->definedAt,
            'password_changed_at' => $event->definedAt,
        ], [
            'defined_at' => Types::DATETIME_IMMUTABLE,
            'password_changed_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }

    #[Subscribe(PasswordCredentialChanged::class)]
    public function onPasswordCredentialChanged(PasswordCredentialChanged $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['password_hash' => $event->passwordHash, 'password_changed_at' => $event->changedAt],
            ['id' => $event->id->toString()],
            ['password_changed_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(PasswordCredentialRehashed::class)]
    public function onPasswordCredentialRehashed(PasswordCredentialRehashed $event): void
    {
        $this->connection->update(self::TABLE, ['password_hash' => $event->passwordHash], ['id' => $event->id->toString()]);
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
        $table->addColumn('password_hash', Types::STRING, ['length' => 255]);
        $table->addColumn('defined_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('password_changed_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['identity_id'], 'iam_authentication_password_credential_identity_id_idx');
    }
}
