<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('sales.order.integration.payment_captured')]
final readonly class OrderPaymentCapturedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: 'erased-address')]
        public string $buyerAddress,
        public string $capturedAt,
    ) {
    }
}
