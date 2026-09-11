<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAbandoned\PaymentAbandonedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\Command\AbandonCartCheckout\AbandonCartCheckout;
use Shopping\Checkout\Application\Policy\AbandonCartOnPaymentAbandoned;
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
