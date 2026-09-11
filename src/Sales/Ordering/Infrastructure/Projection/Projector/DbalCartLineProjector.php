<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Domain\ValueObject\Label;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Checkout\Application\IntegrationEvent\CartCheckedOut\CartCheckedOutIntegrationEvent;

#[Projector('sales.ordering.project_cart_lines')]
final readonly class DbalCartLineProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_cart_line';

    #[Subscribe(CartCheckedOutIntegrationEvent::class)]
    public function onCartCheckedOutIntegrationEvent(CartCheckedOutIntegrationEvent $event): void
    {
        foreach ($event->lines as $line) {
            $this->connection->insert(
                self::TABLE,
                [
                    'line_id' => $line['lineId'],
                    'cart_id' => $event->cartId,
                    'product_id' => $line['productId'],
                    'label' => $line['label'],
                    'unit_price_in_cents' => $line['unitPriceInCents'],
                    'quantity' => $line['quantity'],
                    'checked_out_at' => $event->checkedOutAt,
                ],
                ['checked_out_at' => Types::DATETIME_IMMUTABLE],
            );
        }
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
        $table->addColumn('checked_out_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('line_id'))
                ->create(),
        );
    }
}
