<?php

declare(strict_types=1);

namespace Shared\Infrastructure\VerificationCode;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Schema\DoctrineSchemaConfigurator;
use Patchlevel\Hydrator\Hydrator;
use Shared\Application\VerificationCode\VerificationCodeRecord;
use Shared\Application\VerificationCode\VerificationCodeStoreInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DbalVerificationCodeStore implements VerificationCodeStoreInterface, DoctrineSchemaConfigurator
{
    private const string TABLE = 'verification_code';

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.event_store_connection')]
        private Connection $connection,
        #[Autowire(service: 'shared.hydration.result_hydrator')]
        private Hydrator $hydrator,
    ) {
    }

    public function save(\BackedEnum $purpose, string $subjectId, string $codeHash, \DateTimeImmutable $expiresAt): void
    {
        $row = ['purpose' => $purpose, 'subject_id' => $subjectId, 'code_hash' => $codeHash, 'expires_at' => $expiresAt, 'attempts' => 0];
        $types = ['expires_at' => Types::DATETIME_IMMUTABLE, 'attempts' => Types::INTEGER];

        try {
            $this->connection->insert(self::TABLE, $row, $types);
        } catch (UniqueConstraintViolationException) {
            $this->connection->update(
                self::TABLE,
                ['code_hash' => $codeHash, 'expires_at' => $expiresAt, 'attempts' => 0],
                ['purpose' => $purpose, 'subject_id' => $subjectId],
                $types,
            );
        }
    }

    public function find(\BackedEnum $purpose, string $subjectId): ?VerificationCodeRecord
    {
        $row = $this->connection->createQueryBuilder()
            ->select('code_hash', 'expires_at', 'attempts')
            ->from(self::TABLE)
            ->where('purpose = :purpose')
            ->andWhere('subject_id = :subjectId')
            ->setParameter('purpose', $purpose)
            ->setParameter('subjectId', $subjectId)
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->hydrator->hydrate(VerificationCodeRecord::class, $row);
    }

    public function incrementAttempts(\BackedEnum $purpose, string $subjectId): void
    {
        $this->connection->executeStatement(
            \sprintf('UPDATE %s SET attempts = attempts + 1 WHERE purpose = :purpose AND subject_id = :subjectId', self::TABLE),
            ['purpose' => $purpose, 'subjectId' => $subjectId],
        );
    }

    public function delete(\BackedEnum $purpose, string $subjectId): void
    {
        $this->connection->delete(self::TABLE, ['purpose' => $purpose, 'subject_id' => $subjectId]);
    }

    /**
     * @codeCoverageIgnore
     */
    public function configureSchema(Schema $schema, Connection $connection): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('purpose', Types::STRING, ['length' => 255]);
        $table->addColumn('subject_id', Types::STRING, ['length' => 36]);
        $table->addColumn('code_hash', Types::STRING, ['length' => 64]);
        $table->addColumn('expires_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('attempts', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(
                    UnqualifiedName::unquoted('purpose'),
                    UnqualifiedName::unquoted('subject_id'),
                )
                ->create(),
        );
    }
}
