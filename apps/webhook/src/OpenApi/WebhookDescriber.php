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
use Webhook\Webhook\CarrierDeliveryPayload;

final class WebhookDescriber implements DescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    private const string SIGNATURE_HEADER = 'X-Carrier-Signature';

    private const string SIGNATURE_DESCRIPTION = 'Signature of the request, format `sha256=<hmac>` where <hmac> '
        .'is the HMAC-SHA256 of the exact raw JSON body computed with the shared webhook secret. Recompute it for '
        .'every payload (the value changes with the body) and send it in this header on Try it out.';

    /** @var array<string, array{summary: string, payload: class-string, responses: array<int, string>}> */
    private const array ENDPOINTS = [
        '/webhooks/carrier-delivery' => [
            'summary' => 'Report a shipment as delivered.',
            'payload' => CarrierDeliveryPayload::class,
            'responses' => [404 => 'No shipment matches the given shipmentId.'],
        ],
    ];

    /** @var array<int, string> */
    private const array RESPONSES = [
        202 => 'Webhook accepted and queued for processing.',
        400 => 'Malformed JSON payload.',
        401 => 'Missing or invalid X-Carrier-Signature header.',
        422 => 'Payload failed validation.',
    ];

    public function describe(OA\OpenApi $api): void
    {
        foreach (self::ENDPOINTS as $path => $endpoint) {
            $ref = $this->modelRegistry->register(new Model(Type::object($endpoint['payload'])));

            $operation = Util::getOperation(Util::getPath($api, $path), 'post');
            $operation->tags = ['Webhook'];
            $operation->summary = $endpoint['summary'];

            $signature = Util::getOperationParameter($operation, self::SIGNATURE_HEADER, 'header');
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
