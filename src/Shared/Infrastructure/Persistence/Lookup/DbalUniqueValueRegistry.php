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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DbalUniqueValueRegistry implements UniqueValueRegistryInterface, DoctrineSchemaConfigurator
{
    private const string TABLE = 'unique_constraints';

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.event_store_connection')]
        private Connection $connection,
    ) {
    }

    public function reserve(\BackedEnum $type, string $value): void
    {
        try {
            $this->connection->insert(self::TABLE, [
                'key_type' => $type->value,
                'key_value' => $value,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new UniqueValueAlreadyTakenException($type, $value);
        }
    }

    public function release(\BackedEnum $type, string $value): void
    {
        $this->connection->delete(self::TABLE, [
            'key_type' => $type->value,
            'key_value' => $value,
        ]);
    }

    public function exists(\BackedEnum $type, string $value): bool
    {
        $result = $this->connection->fetchOne(
            \sprintf('SELECT 1 FROM %s WHERE key_type = :type AND key_value = :value', self::TABLE),
            [
                'type' => $type->value,
                'value' => $value,
            ],
        );

        return false !== $result;
    }

    /**
     * @codeCoverageIgnore
     */
    public function configureSchema(Schema $schema, Connection $connection): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('key_type', Types::STRING, ['length' => 50]);
        $table->addColumn('key_value', Types::STRING, ['length' => 255]);
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
