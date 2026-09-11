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
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionOpened\CheckoutSessionOpenedIntegrationEvent;

#[Projector('sales.ordering.project_checkout_sessions')]
final readonly class DbalCheckoutSessionProjector extends AbstractDbalProjector
{
    public const string TABLE = 'sales_ordering_checkout_session';

    #[Subscribe(CheckoutSessionOpenedIntegrationEvent::class)]
    public function onCheckoutSessionOpenedIntegrationEvent(CheckoutSessionOpenedIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'checkout_session_id' => $event->checkoutSessionId,
                'cart_id' => $event->cartId,
                'shopper_id' => $event->shopperId,
                'shipping_address' => SnakeCaseKeys::from($event->shippingAddress),
                'billing_address' => SnakeCaseKeys::from($event->billingAddress),
            ],
            ['shipping_address' => Types::JSON, 'billing_address' => Types::JSON],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('checkout_session_id', Types::STRING, ['length' => 36]);
        $table->addColumn('cart_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shopper_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_address', Types::JSON);
        $table->addColumn('billing_address', Types::JSON);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('checkout_session_id'))
                ->create(),
        );
    }
}
