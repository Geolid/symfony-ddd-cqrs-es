<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\ApiTokenIssued;
use Iam\Identity\Domain\Event\ApiTokenRevoked;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.api_tokens')]
final readonly class DbalApiTokenProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity_api_token';

    #[Subscribe(ApiTokenIssued::class)]
    public function onApiTokenIssued(ApiTokenIssued $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'identifier' => $event->identifier,
            'secret_hash' => $event->secretHash,
            'revoked' => 0,
        ]);
    }

    #[Subscribe(ApiTokenRevoked::class)]
    public function onApiTokenRevoked(ApiTokenRevoked $event): void
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
        $table->addColumn('secret_hash', Types::STRING, ['length' => 255]);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
