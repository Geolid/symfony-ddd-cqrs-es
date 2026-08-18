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
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

/**
 * @template TPayload of object
 */
abstract class AbstractSignedRequestParser extends AbstractRequestParser
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    abstract protected function signatureHeader(): string;

    /**
     * @return class-string<TPayload>
     */
    abstract protected function payloadClass(): string;

    abstract protected function eventType(): string;

    /**
     * @param TPayload $payload
     */
    abstract protected function eventId(object $payload): string;

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
                $this->payloadClass(),
                'json',
                [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true],
            );
        } catch (NotEncodableValueException) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid JSON payload.');
        } catch (PartialDenormalizationException $e) {
            throw new RejectWebhookException(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage(), $e);
        }

        $violations = $this->validator->validate($payload);

        if (\count($violations) > 0) {
            throw new RejectWebhookException(Response::HTTP_UNPROCESSABLE_ENTITY, (string) $violations->get(0)->getMessage());
        }

        return new RemoteEvent($this->eventType(), $this->eventId($payload), get_object_vars($payload));
    }

    private function verifySignature(Request $request, #[\SensitiveParameter] string $secret): void
    {
        $header = $this->signatureHeader();
        $signature = $request->headers->get($header);

        if (null === $signature) {
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, \sprintf('Missing "%s" header.', $header));
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid signature.');
        }
    }
}
