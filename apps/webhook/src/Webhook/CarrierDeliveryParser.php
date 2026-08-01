<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class CarrierDeliveryParser extends AbstractRequestParser
{
    public const string EVENT_TYPE = 'carrier-delivery';

    private const string SIGNATURE_HEADER = 'X-Carrier-Signature';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher('POST'),
            new IsJsonRequestMatcher(),
        ]);
    }

    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent
    {
        $this->verifySignature($request, $secret);

        try {
            $payload = $this->serializer->deserialize(
                $request->getContent(),
                CarrierDeliveryPayload::class,
                'json',
                [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true],
            );
        } catch (NotEncodableValueException) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid JSON payload.');
        } catch (PartialDenormalizationException|MissingConstructorArgumentsException $e) {
            throw new RejectWebhookException(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
        }

        \assert($payload instanceof CarrierDeliveryPayload);

        $violations = $this->validator->validate($payload);

        if (\count($violations) > 0) {
            throw new RejectWebhookException(Response::HTTP_UNPROCESSABLE_ENTITY, (string) $violations->get(0)->getMessage());
        }

        return new RemoteEvent(self::EVENT_TYPE, $payload->trackingReference, get_object_vars($payload));
    }

    private function verifySignature(Request $request, #[\SensitiveParameter] string $secret): void
    {
        $signature = $request->headers->get(self::SIGNATURE_HEADER);

        if (null === $signature) {
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, \sprintf('Missing "%s" header.', self::SIGNATURE_HEADER));
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid signature.');
        }
    }
}
