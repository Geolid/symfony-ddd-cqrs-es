<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\CartStatus;
use Sales\Ordering\Domain\Cart\Event\CartCheckedOut;
use Sales\Ordering\Domain\Cart\Event\CartCheckoutAbandoned;
use Sales\Ordering\Domain\Cart\Event\CartConverted;
use Sales\Ordering\Domain\Cart\Event\CartStarted;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.ordering.project_carts')]
final readonly class DbalCartProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_cart';

    #[Subscribe(CartStarted::class)]
    public function onCartStarted(CartStarted $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'buyer_id' => $event->buyerId,
                'status' => CartStatus::ACTIVE->value,
            ],
        );
    }

    #[Subscribe(CartCheckedOut::class)]
    public function onCartCheckedOut(CartCheckedOut $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::CHECKOUT->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(CartCheckoutAbandoned::class)]
    public function onCartCheckoutAbandoned(CartCheckoutAbandoned $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::ACTIVE->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(CartConverted::class)]
    public function onCartConverted(CartConverted $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::CONVERTED->value],
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
        $table->addColumn('buyer_id', Types::STRING, ['length' => 64]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
