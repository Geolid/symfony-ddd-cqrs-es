<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Checkout\Domain\Cart\Event\CartProductAdded;
use Shopping\Checkout\Domain\Cart\Event\CartProductQuantityChanged;
use Shopping\Checkout\Domain\Cart\Event\CartProductRemoved;

#[Projector('shopping.checkout.project_cart_items')]
final readonly class DbalCartItemProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_cart_item';

    #[Subscribe(CartProductAdded::class)]
    public function onCartProductAdded(CartProductAdded $event): void
    {
        try {
            $this->connection->insert(self::TABLE, [
                'cart_id' => $event->id->toString(),
                'product_id' => $event->productId,
                'quantity' => $event->quantity->value,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->connection->executeStatement(
                \sprintf('UPDATE %s SET quantity = quantity + :delta WHERE cart_id = :cartId AND product_id = :productId', self::TABLE),
                ['delta' => $event->quantity->value, 'cartId' => $event->id->toString(), 'productId' => $event->productId],
            );
        }
    }

    #[Subscribe(CartProductRemoved::class)]
    public function onCartProductRemoved(CartProductRemoved $event): void
    {
        $this->connection->delete(self::TABLE, [
            'cart_id' => $event->id->toString(),
            'product_id' => $event->productId,
        ]);
    }

    #[Subscribe(CartProductQuantityChanged::class)]
    public function onCartProductQuantityChanged(CartProductQuantityChanged $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['quantity' => $event->quantity->value],
            ['cart_id' => $event->id->toString(), 'product_id' => $event->productId],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('cart_id', Types::STRING, ['length' => 36]);
        $table->addColumn('product_id', Types::STRING, ['length' => 36]);
        $table->addColumn('quantity', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('cart_id'), UnqualifiedName::unquoted('product_id'))
                ->create(),
        );
    }
}
