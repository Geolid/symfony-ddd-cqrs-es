<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\ApiTokenCredentialIssued;
use Iam\Identity\Domain\Event\ApiTokenCredentialRehashed;
use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\ValueObject\IdentityState;
use Iam\Identity\Infrastructure\Persistence\Projection\Reducer\IdentityStatusReducer;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.api_token_credentials')]
final readonly class DbalApiTokenCredentialProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity_api_token_credential';

    public function __construct(
        Connection $connection,
        private IdentityStatusReducer $identityStatusReducer,
    ) {
        parent::__construct($connection);
    }

    #[Subscribe(ApiTokenCredentialIssued::class)]
    public function onApiTokenCredentialIssued(ApiTokenCredentialIssued $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'identity_id' => $event->identityId,
            'identifier' => $event->identifier,
            'label' => $event->label,
            'hash' => $event->secretHash,
            'revoked' => 0,
            'expires_at' => new \DateTimeImmutable($event->expiresAt)->format('Y-m-d H:i:s'),
            'identity_status' => $this->identityStatusReducer->statusFor($event->identityId)->value,
        ]);
    }

    #[Subscribe(ApiTokenCredentialRevoked::class)]
    public function onApiTokenCredentialRevoked(ApiTokenCredentialRevoked $event): void
    {
        $this->connection->update(self::TABLE, ['revoked' => 1], ['id' => $event->id]);
    }

    #[Subscribe(ApiTokenCredentialRehashed::class)]
    public function onApiTokenCredentialRehashed(ApiTokenCredentialRehashed $event): void
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

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('identity_id', Types::STRING, ['length' => 36]);
        $table->addColumn('identifier', Types::STRING, ['length' => 255]);
        $table->addColumn('label', Types::STRING, ['length' => 255]);
        $table->addColumn('hash', Types::STRING, ['length' => 255]);
        $table->addColumn('revoked', Types::BOOLEAN);
        $table->addColumn('expires_at', Types::DATETIME_MUTABLE);
        $table->addColumn('identity_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
