<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.api_token_credentials')]
final readonly class DbalApiTokenCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity_api_token_credential';

    #[Subscribe(ApiTokenCredentialIssued::class)]
    public function onApiTokenCredentialIssued(ApiTokenCredentialIssued $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'identifier' => $event->identifier,
            'hash' => $event->secretHash,
            'revoked' => 0,
            'expires_at' => new \DateTimeImmutable($event->expiresAt)->format('Y-m-d H:i:s'),
        ]);
    }

    #[Subscribe(ApiTokenCredentialRevoked::class)]
    public function onApiTokenCredentialRevoked(ApiTokenCredentialRevoked $event): void
    {
        $this->connection->update(self::TABLE, ['revoked' => 1], ['id' => $event->id]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('identity_id', Types::STRING, ['length' => 36]);
        $table->addColumn('identifier', Types::STRING, ['length' => 255]);
        $table->addColumn('hash', Types::STRING, ['length' => 255]);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addColumn('expires_at', Types::DATETIME_MUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
