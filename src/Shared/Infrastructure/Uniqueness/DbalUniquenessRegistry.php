<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Uniqueness;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Schema\DoctrineSchemaConfigurator;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DbalUniquenessRegistry implements UniquenessRegistryInterface, DoctrineSchemaConfigurator
{
    private const string TABLE = 'uniqueness_registry';

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.event_store_connection')]
        private Connection $connection,
    ) {
    }

    public function claim(UniqueKey $key, string $value, string $ownerId): void
    {
        $keyType = $key->toString();

        try {
            $this->connection->insert(self::TABLE, [
                'key_type' => $keyType,
                'key_value' => $value,
                'owner_id' => $ownerId,
            ]);
        } catch (UniqueConstraintViolationException) {
            if ($this->isClaimed($key, $value, $ownerId)) {
                throw UniquenessViolatedException::forValue($key, $value);
            }
        }
    }

    public function isClaimed(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('1')
            ->from(self::TABLE)
            ->where('key_type = :type')
            ->andWhere('key_value = :value')
            ->setParameter('type', $key->toString())
            ->setParameter('value', $value);

        if (null !== $excludeOwnerId) {
            $qb->andWhere('owner_id != :excludeOwnerId')
                ->setParameter('excludeOwnerId', $excludeOwnerId);
        }

        return false !== $qb->fetchOne();
    }

    public function release(UniqueKey $key, string $ownerId): void
    {
        $this->connection->delete(self::TABLE, [
            'key_type' => $key->toString(),
            'owner_id' => $ownerId,
        ]);
    }

    public function releaseAll(UniqueKey $key): void
    {
        $this->connection->delete(self::TABLE, ['key_type' => $key->toString()]);
    }

    /**
     * @codeCoverageIgnore
     */
    public function configureSchema(Schema $schema, Connection $connection): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('key_type', Types::STRING, ['length' => 255]);
        $table->addColumn('key_value', Types::STRING, ['length' => 255]);
        $table->addColumn('owner_id', Types::STRING, ['length' => 36]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(
                    UnqualifiedName::unquoted('key_type'),
                    UnqualifiedName::unquoted('key_value'),
                )
                ->create(),
        );
    }
}
