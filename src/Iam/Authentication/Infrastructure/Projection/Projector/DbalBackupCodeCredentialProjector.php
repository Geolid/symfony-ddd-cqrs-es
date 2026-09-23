<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialConsumed;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialIssued;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialRegenerated;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.authentication.project_backup_code_credentials')]
final readonly class DbalBackupCodeCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_authentication_backup_code_credential';

    #[Subscribe(BackupCodeCredentialIssued::class)]
    public function onBackupCodeCredentialIssued(BackupCodeCredentialIssued $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id->toString(),
                'identity_id' => $event->identityId,
                'issued_at' => $event->issuedAt,
                'remaining_count' => \count($event->backupCodes),
            ],
            ['issued_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(BackupCodeCredentialRegenerated::class)]
    public function onBackupCodeCredentialRegenerated(BackupCodeCredentialRegenerated $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'regenerated_at' => $event->regeneratedAt,
                'remaining_count' => \count($event->backupCodes),
            ],
            ['id' => $event->id->toString()],
            ['regenerated_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(BackupCodeCredentialConsumed::class)]
    public function onBackupCodeCredentialConsumed(BackupCodeCredentialConsumed $event): void
    {
        $this->connection->executeStatement(
            \sprintf('UPDATE %s SET remaining_count = remaining_count - 1 WHERE id = :id', self::TABLE),
            ['id' => $event->id->toString()],
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
        $table->addColumn('issued_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('regenerated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('remaining_count', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('identity_id'))
                ->create(),
        );
        $table->addUniqueIndex(['id']);
    }
}
