<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Patchlevel\EventSourcing\Attribute\Cleanup;
use Patchlevel\EventSourcing\Attribute\Setup;
use Patchlevel\EventSourcing\Subscription\Cleanup\Dbal\DropTableTask;

abstract readonly class AbstractDbalProjector
{
    public function __construct(protected Connection $connection)
    {
    }

    /**
     * @codeCoverageIgnore
     */
    #[Setup]
    public function create(): void
    {
        $schema = new Schema();
        $this->configureSchema($schema);
        $this->connection->createSchemaManager()->createSchemaObjects($schema);
    }

    /**
     * @return list<DropTableTask>
     *
     * @codeCoverageIgnore
     */
    #[Cleanup]
    public function cleanup(): array
    {
        $schema = new Schema();
        $this->configureSchema($schema);

        return array_map(
            static function (Table $table): DropTableTask {
                $name = $table->getObjectName()->toString();
                \assert('' !== $name);

                return new DropTableTask($name);
            },
            $schema->getTables(),
        );
    }

    abstract protected function configureSchema(Schema $schema): void;
}
