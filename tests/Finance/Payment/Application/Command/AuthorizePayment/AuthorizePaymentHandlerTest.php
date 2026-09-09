<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\AuthorizePayment;

use Finance\Payment\Application\Command\AuthorizePayment\AuthorizePayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class AuthorizePaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAuthorizesWhenRequested(): void
    {
        // Given
        $paymentFactory = PaymentBuilder::new();
        $orderPayment = $paymentFactory->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new AuthorizePayment($orderPayment->id->toString()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyAuthorized(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->authorized()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new AuthorizePayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new AuthorizePayment($id));
    }
}
