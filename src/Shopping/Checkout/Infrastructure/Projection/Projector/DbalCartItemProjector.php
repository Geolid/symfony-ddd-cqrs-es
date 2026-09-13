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
use Shopping\Cart\Application\IntegrationEvent\CartProductAdded\CartProductAddedIntegrationEvent;
use Shopping\Cart\Application\IntegrationEvent\CartProductQuantityChanged\CartProductQuantityChangedIntegrationEvent;
use Shopping\Cart\Application\IntegrationEvent\CartProductRemoved\CartProductRemovedIntegrationEvent;

#[Projector('shopping.checkout.project_cart_items')]
final readonly class DbalCartItemProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_cart_item';

    #[Subscribe(CartProductAddedIntegrationEvent::class)]
    public function onCartProductAdded(CartProductAddedIntegrationEvent $event): void
    {
        try {
            $this->connection->insert(self::TABLE, [
                'cart_id' => $event->cartId,
                'product_id' => $event->productId,
                'quantity' => $event->quantity,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->connection->executeStatement(
                \sprintf('UPDATE %s SET quantity = quantity + :delta WHERE cart_id = :cartId AND product_id = :productId', self::TABLE),
                ['delta' => $event->quantity, 'cartId' => $event->cartId, 'productId' => $event->productId],
            );
        }
    }

    #[Subscribe(CartProductRemovedIntegrationEvent::class)]
    public function onCartProductRemoved(CartProductRemovedIntegrationEvent $event): void
    {
        $this->connection->delete(self::TABLE, [
            'cart_id' => $event->cartId,
            'product_id' => $event->productId,
        ]);
    }

    #[Subscribe(CartProductQuantityChangedIntegrationEvent::class)]
    public function onCartProductQuantityChanged(CartProductQuantityChangedIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['quantity' => $event->quantity],
            ['cart_id' => $event->cartId, 'product_id' => $event->productId],
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
