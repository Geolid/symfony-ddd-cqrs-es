<?php

declare(strict_types=1);

namespace Webhook\Webhook;

/**
 * @extends AbstractSignedRequestParser<CarrierReturnReceivedPayload>
 */
final class CarrierReturnReceivedParser extends AbstractSignedRequestParser
{
    public const string EVENT_TYPE = 'carrier-return-received';

    public const string SIGNATURE_HEADER = 'X-Carrier-Signature';

    protected function signatureHeader(): string
    {
        return self::SIGNATURE_HEADER;
    }

    protected function payloadClass(): string
    {
        return CarrierReturnReceivedPayload::class;
    }

    protected function eventType(): string
    {
        return self::EVENT_TYPE;
    }

    protected function eventId(object $payload): string
    {
        return $payload->returnTrackingReference;
    }
}
