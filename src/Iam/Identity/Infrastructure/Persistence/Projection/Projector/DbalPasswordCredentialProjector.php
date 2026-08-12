<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialRehashed;
use Iam\Identity\Domain\Event\PasswordCredentialSet;
use Iam\Identity\Domain\ValueObject\IdentityState;
use Iam\Identity\Infrastructure\Persistence\Projection\Reducer\IdentityStatusReducer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('iam.identity.password_credentials')]
final readonly class DbalPasswordCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity_password_credential';

    public function __construct(
        Connection $connection,
        private IdentityStatusReducer $identityStatusReducer,
    ) {
        parent::__construct($connection);
    }

    #[Subscribe(PasswordCredentialSet::class)]
    public function onPasswordCredentialSet(PasswordCredentialSet $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'login' => $event->login,
            'hash' => $event->hash,
            'identity_status' => $this->identityStatusReducer->statusFor($event->identityId)->value,
        ]);
    }

    #[Subscribe(PasswordCredentialChanged::class)]
    public function onPasswordCredentialChanged(PasswordCredentialChanged $event): void
    {
        $this->connection->update(self::TABLE, ['hash' => $event->hash], ['id' => $event->id]);
    }

    #[Subscribe(PasswordCredentialRehashed::class)]
    public function onPasswordCredentialRehashed(PasswordCredentialRehashed $event): void
    {
        $this->connection->update(self::TABLE, ['hash' => $event->hash], ['id' => $event->id]);
    }

    #[Subscribe(IdentitySuspended::class)]
    public function onIdentitySuspended(IdentitySuspended $event): void
    {
        $this->connection->update(self::TABLE, ['identity_status' => IdentityState::SUSPENDED->value], ['identity_id' => $event->id]);
    }

    #[Subscribe(IdentityReactivated::class)]
    public function onIdentityReactivated(IdentityReactivated $event): void
    {
        $this->connection->update(self::TABLE, ['identity_status' => IdentityState::ACTIVE->value], ['identity_id' => $event->id]);
    }

    #[Subscribe(IdentityErased::class)]
    public function onIdentityErased(IdentityErased $event): void
    {
        $this->connection->delete(self::TABLE, ['identity_id' => $event->id]);
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
        $table->addColumn('hash', Types::STRING, ['length' => 255]);
        $table->addColumn('identity_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['login'], 'iam_identity_password_credential_login_unique');
    }
}
