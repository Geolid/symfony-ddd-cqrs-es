<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Checkout\Application\IntegrationEvent\CartCheckedOut\CartCheckedOutIntegrationEvent;

#[Projector('sales.ordering.project_carts')]
final readonly class DbalCartProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_cart';

    #[Subscribe(CartCheckedOutIntegrationEvent::class)]
    public function onCartCheckedOutIntegrationEvent(CartCheckedOutIntegrationEvent $event): void
    {
        $this->connection->insert(self::TABLE, [
            'cart_id' => $event->cartId,
            'shopper_id' => $event->shopperId,
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('cart_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shopper_id', Types::STRING, ['length' => 36]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('cart_id'))
                ->create(),
        );
    }
}
