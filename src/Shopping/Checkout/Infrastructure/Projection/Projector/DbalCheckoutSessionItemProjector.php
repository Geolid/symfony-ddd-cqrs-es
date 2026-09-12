<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;

#[Projector('shopping.checkout.project_checkout_session_items')]
final readonly class DbalCheckoutSessionItemProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_checkout_session_item';

    #[Subscribe(CheckoutSessionOpened::class)]
    public function onCheckoutSessionOpened(CheckoutSessionOpened $event): void
    {
        foreach ($event->items as $item) {
            $this->connection->insert(self::TABLE, [
                'checkout_session_id' => $event->id,
                'product_id' => $item->productId,
                'label' => $item->label->value,
                'unit_price_in_cents' => $item->unitPrice->cents,
                'quantity' => $item->quantity->value,
            ]);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('checkout_session_id', Types::STRING, ['length' => 36]);
        $table->addColumn('product_id', Types::STRING, ['length' => 36]);
        $table->addColumn('label', Types::STRING, ['length' => 255]);
        $table->addColumn('unit_price_in_cents', Types::INTEGER);
        $table->addColumn('quantity', Types::INTEGER);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('checkout_session_id'), UnqualifiedName::unquoted('product_id'))
                ->create(),
        );
    }
}
