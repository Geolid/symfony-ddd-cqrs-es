<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAbandoned\PaymentAbandonedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\AbandonCartCheckout\AbandonCartCheckout;
use Sales\Ordering\Application\Policy\AbandonCartOnPaymentAbandoned;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class AbandonCartOnPaymentAbandonedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAbandons(): void
    {
        // Given
        $cartId = Uuid::uuid7()->toString();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new AbandonCartCheckout($cartId));

        // When
        $this->trigger(AbandonCartOnPaymentAbandoned::class, new PaymentAbandonedIntegrationEvent(Uuid::uuid7()->toString(), $cartId, Clock::get()->now()));
    }
}
