<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Patchlevel\EventSourcing\Attribute\Setup;
use Patchlevel\EventSourcing\Attribute\Teardown;

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
     * @codeCoverageIgnore
     */
    #[Teardown]
    public function drop(): void
    {
        $schema = new Schema();
        $this->configureSchema($schema);
        $this->connection->createSchemaManager()->dropSchemaObjects($schema);
    }

    abstract protected function configureSchema(Schema $schema): void;
}
