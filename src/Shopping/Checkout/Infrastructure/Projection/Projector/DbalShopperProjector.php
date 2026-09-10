<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\ErasureStatus;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;
use Shopping\Checkout\Domain\Event\ShopperErased;
use Shopping\Checkout\Domain\Event\ShopperErasureCancelled;
use Shopping\Checkout\Domain\Event\ShopperErasureRequested;
use Shopping\Checkout\Domain\Event\ShopperRegistered;

#[Projector('shopping.checkout.project_shoppers')]
final readonly class DbalShopperProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shopping_checkout_shopper';

    #[Subscribe(ShopperRegistered::class)]
    public function onShopperRegistered(ShopperRegistered $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'email' => $event->email->value,
                'registered_at' => $event->registeredAt,
                'erasure_status' => ErasureStatus::RETAINED->value,
            ],
            ['registered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShopperErasureRequested::class)]
    public function onShopperErasureRequested(ShopperErasureRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::REQUESTED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(ShopperErasureCancelled::class)]
    public function onShopperErasureCancelled(ShopperErasureCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['erasure_status' => ErasureStatus::RETAINED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(ShopperErased::class)]
    public function onShopperErased(ShopperErased $event): void
    {
        $this->connection->delete(self::TABLE, ['id' => $event->id]);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('email', Types::STRING, ['length' => 254]);
        $table->addColumn('registered_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('erasure_status', Types::STRING, ['length' => 20]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addUniqueIndex(['email'], 'shopping_checkout_shopper_email_unique');
    }
}
