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
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionConsumed;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionStaled;

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
                'shopper_id' => $event->shopperId,
                'status' => CheckoutSessionStatus::OPEN->value,
                'opened_at' => $event->openedAt,
            ],
            ['opened_at' => Types::DATETIME_IMMUTABLE],
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

    #[Subscribe(CheckoutSessionConsumed::class)]
    public function onCheckoutSessionConsumed(CheckoutSessionConsumed $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => CheckoutSessionStatus::CONSUMED->value],
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
        $table->addColumn('shopper_id', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 8]);
        $table->addColumn('opened_at', Types::DATETIME_IMMUTABLE);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['cart_id'], 'shopping_checkout_checkout_session_cart_id_idx');
        $table->addIndex(['shopper_id'], 'shopping_checkout_checkout_session_shopper_id_idx');
    }
}
