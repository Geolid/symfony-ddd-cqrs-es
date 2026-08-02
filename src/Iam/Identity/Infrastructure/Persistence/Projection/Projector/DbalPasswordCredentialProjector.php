<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialSet;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.password_credentials')]
final readonly class DbalPasswordCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity_password_credential';

    #[Subscribe(PasswordCredentialSet::class)]
    public function onPasswordCredentialSet(PasswordCredentialSet $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'login' => $event->login,
            'hash' => $event->hash,
        ]);
    }

    #[Subscribe(PasswordCredentialChanged::class)]
    public function onPasswordCredentialChanged(PasswordCredentialChanged $event): void
    {
        $this->connection->update(self::TABLE, ['hash' => $event->hash], ['id' => $event->id]);
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
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
