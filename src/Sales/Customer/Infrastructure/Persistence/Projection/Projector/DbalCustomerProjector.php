<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

#[Projector('sales.customer.customers')]
final readonly class DbalCustomerProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_customer';

    #[Subscribe(CustomerRegistered::class)]
    public function onCustomerRegistered(CustomerRegistered $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'email' => $event->email,
            'registered_at' => new \DateTimeImmutable($event->registeredAt)->format('Y-m-d H:i:s'),
        ]);
    }

    #[Subscribe(CustomerErased::class)]
    public function onCustomerErased(CustomerErased $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'email' => null,
                'erased_at' => new \DateTimeImmutable($event->erasedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('email', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addColumn('registered_at', Types::DATETIME_MUTABLE);
        $table->addColumn('erased_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
