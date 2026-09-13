<?php

declare(strict_types=1);

namespace Shopping\Cart\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Cart\Application\CartStatus;
use Shopping\Cart\Domain\Event\CartPurchased;
use Shopping\Cart\Domain\Event\CartStarted;

#[Projector('shopping.cart.project_carts')]
final readonly class DbalCartProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_cart_cart';

    #[Subscribe(CartStarted::class)]
    public function onCartStarted(CartStarted $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id->toString(),
            'customer_id' => $event->customerId,
            'status' => CartStatus::ACTIVE->value,
        ]);
    }

    #[Subscribe(CartPurchased::class)]
    public function onCartPurchased(CartPurchased $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CartStatus::PURCHASED->value],
            ['id' => $event->id->toString()],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
    }
}
