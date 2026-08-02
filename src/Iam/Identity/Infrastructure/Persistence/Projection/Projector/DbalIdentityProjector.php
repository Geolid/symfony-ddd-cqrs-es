<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('iam.identity.identities')]
final readonly class DbalIdentityProjector extends AbstractDbalProjector
{
    public const string TABLE = 'iam_identity';

    #[Subscribe(IdentityRegistered::class)]
    public function onIdentityRegistered(IdentityRegistered $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'login' => $event->login,
            'registered_at' => new \DateTimeImmutable($event->registeredAt)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('login', Types::STRING, ['length' => 255]);
        $table->addColumn('registered_at', Types::DATETIME_MUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
