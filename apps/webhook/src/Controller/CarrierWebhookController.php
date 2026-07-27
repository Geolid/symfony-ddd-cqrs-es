<?php

declare(strict_types=1);

namespace Webhook\Controller;

use Shared\Application\Command\CommandBusInterface;
use Shipping\Shipment\Application\Command\MarkShipmentDelivered\MarkShipmentDelivered;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Inbound webhook from the (fictional) carrier tracking delivery status. Unlike the web/api/cli
 * Delivery Mechanisms, the caller here is an untrusted third party, so the signature check
 * below is not optional: it's what makes this endpoint a legitimate driving adapter for the
 * Command bus rather than an open door.
 */
final readonly class CarrierWebhookController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        #[Autowire('%env(CARRIER_WEBHOOK_SECRET)%')]
        private string $secret,
    ) {
    }

    #[Route('/webhooks/carrier', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $body = $request->getContent();

        if (!$this->hasValidSignature($request, $body)) {
            return new Response(status: Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);

        // Tolerant reader: only react to the event types we know, ignore (but 2xx-acknowledge)
        // the rest so the carrier doesn't retry-storm us over an event we simply don't handle.
        if ('shipment.delivered' === ($payload['event'] ?? null)) {
            $this->commandBus->dispatch(new MarkShipmentDelivered((string) $payload['shipmentId']));
        }

        return new Response(status: Response::HTTP_ACCEPTED);
    }

    private function hasValidSignature(Request $request, string $body): bool
    {
        $signature = $request->headers->get('X-Carrier-Signature', '');
        $expected = hash_hmac('sha256', $body, $this->secret);

        return '' !== $signature && hash_equals($expected, $signature);
    }
}
