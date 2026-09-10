<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Cart\Event\CartLineAdded;
use Sales\Ordering\Domain\Cart\Event\CartLineQuantityChanged;
use Sales\Ordering\Domain\Cart\Event\CartLineRemoved;
use Shared\Domain\ValueObject\Label;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('sales.ordering.project_cart_lines')]
final readonly class DbalCartLineProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_cart_line';

    #[Subscribe(CartLineAdded::class)]
    public function onCartLineAdded(CartLineAdded $event): void
    {
        $affected = $this->connection->executeStatement(
            \sprintf('UPDATE %s SET quantity = quantity + :quantity WHERE line_id = :lineId', self::TABLE),
            ['quantity' => $event->quantity->value, 'lineId' => $event->lineId],
        );

        if (0 !== $affected) {
            return;
        }

        $this->connection->insert(
            self::TABLE,
            [
                'line_id' => $event->lineId,
                'cart_id' => $event->id,
                'product_id' => $event->product->id,
                'label' => $event->product->label->value,
                'unit_price_in_cents' => $event->product->price->cents,
                'quantity' => $event->quantity->value,
            ],
        );
    }

    #[Subscribe(CartLineRemoved::class)]
    public function onCartLineRemoved(CartLineRemoved $event): void
    {
        $this->connection->delete(self::TABLE, ['line_id' => $event->lineId]);
    }

    #[Subscribe(CartLineQuantityChanged::class)]
    public function onCartLineQuantityChanged(CartLineQuantityChanged $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['quantity' => $event->quantity->value],
            ['line_id' => $event->lineId],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('line_id', Types::STRING, ['length' => 36]);
        $table->addColumn('cart_id', Types::STRING, ['length' => 36]);
        $table->addColumn('product_id', Types::STRING, ['length' => 36]);
        $table->addColumn('label', Types::STRING, ['length' => Label::MAX_LENGTH]);
        $table->addColumn('unit_price_in_cents', Types::INTEGER);
        $table->addColumn('quantity', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('line_id'))
                ->create(),
        );
        $table->addIndex(['cart_id'], 'sales_ordering_cart_line_cart_id_idx');
    }
}
