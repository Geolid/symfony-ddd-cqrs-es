<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Lookup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Schema\DoctrineSchemaConfigurator;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DbalUniqueValueRegistry implements UniqueValueRegistryInterface, DoctrineSchemaConfigurator
{
    private const string TABLE = 'unique_constraints';

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.event_store_connection')]
        private Connection $connection,
    ) {
    }

    public function reserve(UniqueKey $key, string $value, string $ownerId, ?string $subjectId = null): void
    {
        $keyType = $key->value;

        try {
            $this->connection->insert(self::TABLE, [
                'key_type' => $keyType,
                'key_value' => $value,
                'owner_id' => $ownerId,
                'subject_id' => $subjectId,
            ]);
        } catch (UniqueConstraintViolationException) {
            if ($this->exists($key, $value, $ownerId)) {
                throw UniqueValueAlreadyTakenException::forValue($key, $value);
            }
        }
    }

    public function release(UniqueKey $key, string $value, string $ownerId): void
    {
        $this->connection->delete(self::TABLE, [
            'key_type' => $key->value,
            'key_value' => $value,
            'owner_id' => $ownerId,
        ]);
    }

    public function exists(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('1')
            ->from(self::TABLE)
            ->where('key_type = :type')
            ->andWhere('key_value = :value')
            ->setParameter('type', $key->value)
            ->setParameter('value', $value);

        if (null !== $excludeOwnerId) {
            $qb->andWhere('owner_id != :excludeOwnerId')
                ->setParameter('excludeOwnerId', $excludeOwnerId);
        }

        return false !== $qb->fetchOne();
    }

    public function releaseAllForSubject(string $subjectId): void
    {
        $this->connection->delete(self::TABLE, ['subject_id' => $subjectId]);
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
        $table->addColumn('subject_id', Types::STRING, ['length' => 36, 'notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(
                    UnqualifiedName::unquoted('key_type'),
                    UnqualifiedName::unquoted('key_value'),
                )
                ->create(),
        );
        $table->addIndex(['subject_id']);
    }
}
