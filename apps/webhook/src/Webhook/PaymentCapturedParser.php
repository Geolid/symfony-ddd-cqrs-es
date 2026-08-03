<?php

declare(strict_types=1);

namespace Webhook\Webhook;

/**
 * @extends AbstractSignedRequestParser<PaymentCapturedPayload>
 */
final class PaymentCapturedParser extends AbstractSignedRequestParser
{
    public const string EVENT_TYPE = 'payment-captured';

    public const string SIGNATURE_HEADER = 'X-Payment-Signature';

    protected function signatureHeader(): string
    {
        return self::SIGNATURE_HEADER;
    }

    protected function payloadClass(): string
    {
        return PaymentCapturedPayload::class;
    }

    protected function eventType(): string
    {
        return self::EVENT_TYPE;
    }

    protected function eventId(object $payload): string
    {
        return $payload->paymentReference;
    }
}
