<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Query\GetPaymentByReference;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Query\GetPaymentByReference\GetPaymentByReference;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentReferenceFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetPaymentByReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);

        // When
        $result = $this->ask(new GetPaymentByReference($payment->reference->value));

        // Then
        self::assertSame($payment->id->toString(), $result->id);
        self::assertSame($payment->checkoutSessionId, $result->checkoutSessionId);
        self::assertNull($result->orderId);
        self::assertSame($payment->amount->cents, $result->amountInCents);
        self::assertSame($payment->reference->value, $result->reference);
        self::assertSame($payment->hostedPageUrl, $result->hostedPageUrl);
        self::assertSame(PaymentStatus::REQUESTED, $result->status);
        self::assertNotNull($result->requestedAt);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownReference = PaymentReferenceFactory::new()->create()->value;

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->ask(new GetPaymentByReference($unknownReference));
    }
}
