<?php

declare(strict_types=1);

namespace Webhook\OpenApi;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use Symfony\Component\TypeInfo\Type;
use Webhook\Webhook\CarrierDeliveryParser;
use Webhook\Webhook\CarrierDeliveryPayload;
use Webhook\Webhook\CarrierPickupConfirmedParser;
use Webhook\Webhook\CarrierPickupConfirmedPayload;
use Webhook\Webhook\PaymentAuthorizedParser;
use Webhook\Webhook\PaymentAuthorizedPayload;
use Webhook\Webhook\PaymentFailedParser;
use Webhook\Webhook\PaymentFailedPayload;

final class WebhookDescriber implements DescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    private const string SIGNATURE_DESCRIPTION = 'Signature of the request, format `sha256=<hmac>` where <hmac> '
        .'is the HMAC-SHA256 of the exact raw JSON body computed with the shared webhook secret. Recompute it for '
        .'every payload (the value changes with the body) and send it in this header on Try it out.';

    /** @var array<string, array{summary: string, payload: class-string, signatureHeader: string, responses: array<int, string>}> */
    private const array ENDPOINTS = [
        '/webhooks/'.CarrierDeliveryParser::EVENT_TYPE => [
            'summary' => 'Report a shipment as delivered.',
            'payload' => CarrierDeliveryPayload::class,
            'signatureHeader' => CarrierDeliveryParser::SIGNATURE_HEADER,
            'responses' => [404 => 'No shipment matches the given shipment ID.'],
        ],
        '/webhooks/'.CarrierPickupConfirmedParser::EVENT_TYPE => [
            'summary' => 'Report a shipment as picked up by the carrier.',
            'payload' => CarrierPickupConfirmedPayload::class,
            'signatureHeader' => CarrierPickupConfirmedParser::SIGNATURE_HEADER,
            'responses' => [404 => 'No shipment matches the given tracking reference.'],
        ],
        '/webhooks/'.PaymentAuthorizedParser::EVENT_TYPE => [
            'summary' => 'Report an order payment as authorized.',
            'payload' => PaymentAuthorizedPayload::class,
            'signatureHeader' => PaymentAuthorizedParser::SIGNATURE_HEADER,
            'responses' => [404 => 'No payment matches the given reference.'],
        ],
        '/webhooks/'.PaymentFailedParser::EVENT_TYPE => [
            'summary' => 'Report an order payment as failed.',
            'payload' => PaymentFailedPayload::class,
            'signatureHeader' => PaymentFailedParser::SIGNATURE_HEADER,
            'responses' => [404 => 'No payment matches the given reference.'],
        ],
    ];

    /** @var array<int, string> */
    private const array RESPONSES = [
        202 => 'Webhook accepted and queued for processing.',
        400 => 'Malformed JSON payload.',
        401 => 'Missing or invalid signature header.',
        422 => 'Payload failed validation.',
    ];

    public function describe(OA\OpenApi $api): void
    {
        foreach (self::ENDPOINTS as $path => $endpoint) {
            $ref = $this->modelRegistry->register(new Model(Type::object($endpoint['payload'])));

            $operation = Util::getOperation(Util::getPath($api, $path), 'post');
            $operation->tags = ['Webhook'];
            $operation->summary = $endpoint['summary'];

            $signature = Util::getOperationParameter($operation, $endpoint['signatureHeader'], 'header');
            $signature->required = true;
            $signature->description = self::SIGNATURE_DESCRIPTION;
            Util::getChild($signature, OA\Schema::class, ['type' => 'string']);

            $requestBody = Util::getChild($operation, OA\RequestBody::class, ['required' => true]);
            $mediaType = Util::getIndexedCollectionItem($requestBody, OA\MediaType::class, 'application/json');
            Util::getChild($mediaType, OA\Schema::class)->ref = $ref;

            $responses = self::RESPONSES + $endpoint['responses'];
            ksort($responses);

            foreach ($responses as $status => $description) {
                Util::getIndexedCollectionItem($operation, OA\Response::class, (string) $status)
                    ->description = $description;
            }
        }
    }
}
