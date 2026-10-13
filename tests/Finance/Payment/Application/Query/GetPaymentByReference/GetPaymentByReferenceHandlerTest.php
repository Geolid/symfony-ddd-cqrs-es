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
        $orderPayment = PaymentFactory::new()->create();
        $this->store($orderPayment);

        // When
        $result = $this->ask(new GetPaymentByReference($orderPayment->reference->value));

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($orderPayment->checkoutSessionId, $result->checkoutSessionId);
        self::assertNull($result->orderId);
        self::assertSame($orderPayment->amount->cents, $result->amountInCents);
        self::assertSame($orderPayment->reference->value, $result->reference);
        self::assertSame($orderPayment->hostedPageUrl, $result->hostedPageUrl);
        self::assertSame(PaymentStatus::REQUESTED, $result->status);
        self::assertNotNull($result->requestedAt);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->ask(new GetPaymentByReference(PaymentReferenceFactory::new()->create()->value));
    }
}
