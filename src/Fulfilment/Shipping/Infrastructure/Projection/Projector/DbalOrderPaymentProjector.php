<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Finance\Payment\Application\IntegrationEvent\PaymentCaptured\PaymentCapturedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('fulfilment.shipping.project_order_payments')]
final readonly class DbalOrderPaymentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'fulfilment_shipping_order_payment';

    #[Subscribe(PaymentCapturedIntegrationEvent::class)]
    public function onPaymentCapturedIntegrationEvent(PaymentCapturedIntegrationEvent $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'order_id' => $event->orderId,
                'paid' => true,
            ],
            ['paid' => Types::BOOLEAN],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('paid', Types::BOOLEAN);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('order_id'))
                ->create(),
        );
    }
}
