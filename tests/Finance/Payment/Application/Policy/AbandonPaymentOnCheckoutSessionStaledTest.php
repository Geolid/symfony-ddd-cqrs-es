<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Command\AbandonPayment\AbandonPayment;
use Finance\Payment\Application\Policy\AbandonPaymentOnCheckoutSessionStaled;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionStaled\CheckoutSessionStaledIntegrationEvent;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class AbandonPaymentOnCheckoutSessionStaledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAbandons(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $checkoutSessionId = Uuid::uuid7()->toString();
        $paymentId = PaymentId::forCheckoutSession($checkoutSessionId);
        $payment = PaymentBuilder::new()->withId($paymentId->toString())->withCheckoutSessionId($checkoutSessionId)->create();
        $this->store($payment);
        $commandBus->expects(self::once())->method('dispatch')->with(new AbandonPayment($paymentId->toString()));

        // When
        $this->trigger(AbandonPaymentOnCheckoutSessionStaled::class, new CheckoutSessionStaledIntegrationEvent($checkoutSessionId, Clock::get()->now()));
    }

    #[Test]
    public function itIgnoresWhenNoPaymentRequested(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::never())->method('dispatch');

        // When
        $this->trigger(AbandonPaymentOnCheckoutSessionStaled::class, new CheckoutSessionStaledIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));
    }
}
