<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Domain\Event\CheckoutSessionCompleted;
use Shopping\Checkout\Domain\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\Event\CheckoutSessionStaled;

#[Projector('shopping.checkout.project_checkout_sessions')]
final readonly class DbalCheckoutSessionProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_checkout_session';

    #[Subscribe(CheckoutSessionOpened::class)]
    public function onCheckoutSessionOpened(CheckoutSessionOpened $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'cart_id' => $event->cartId,
                'customer_id' => $event->customerId,
                'shipping_address' => SnakeCaseKeys::from(PostalAddressMapper::toArray($event->shippingAddress)),
                'billing_address' => SnakeCaseKeys::from(PostalAddressMapper::toArray($event->billingAddress)),
                'total_amount_in_cents' => $event->totalAmount->cents,
                'status' => CheckoutSessionStatus::OPEN->value,
                'opened_at' => $event->openedAt,
            ],
            ['opened_at' => Types::DATETIME_IMMUTABLE, 'shipping_address' => Types::JSON, 'billing_address' => Types::JSON],
        );
    }

    #[Subscribe(CheckoutSessionExpired::class)]
    public function onCheckoutSessionExpired(CheckoutSessionExpired $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CheckoutSessionStatus::EXPIRED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(CheckoutSessionStaled::class)]
    public function onCheckoutSessionStaled(CheckoutSessionStaled $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CheckoutSessionStatus::STALE->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(CheckoutSessionCompleted::class)]
    public function onCheckoutSessionCompleted(CheckoutSessionCompleted $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CheckoutSessionStatus::COMPLETED->value],
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
        $table->addColumn('cart_id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('shipping_address', Types::JSON);
        $table->addColumn('billing_address', Types::JSON);
        $table->addColumn('total_amount_in_cents', Types::INTEGER);
        $table->addColumn('status', Types::STRING, ['length' => 9]);
        $table->addColumn('opened_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['cart_id'], 'shopping_checkout_checkout_session_cart_id_idx');
        $table->addIndex(['customer_id'], 'shopping_checkout_checkout_session_customer_id_idx');
    }
}
