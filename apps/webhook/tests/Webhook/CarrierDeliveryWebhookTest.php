<?php

declare(strict_types=1);

namespace Webhook\Tests\Webhook;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Webhook\Tests\Support\AbstractWebhookTestCase;

final class CarrierDeliveryWebhookTest extends AbstractWebhookTestCase
{
    private const string PATH = '/webhooks/carrier-delivery';

    #[Test]
    public function itAcceptsACarrierDelivery(): void
    {
        // Given
        $client = self::createClient();
        $shipmentFactory = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $shipment = $shipmentFactory->create();
        $trackingNumber = $shipmentFactory['trackingNumber']->value;
        $this->store($shipment);
        $body = self::body($trackingNumber);

        // When
        $client->request('POST', self::PATH, server: $this->headers(self::sign($body, 'CARRIER_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    #[DataProvider('provideBadSignatures')]
    public function itRejectsAnUnsignedDelivery(?string $signature): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(self::anyTrackingNumber());

        // When
        $client->request('POST', self::PATH, server: $this->headers($signature), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function provideBadSignatures(): iterable
    {
        yield 'header absent' => [null];
        yield 'value forged' => ['sha256=0000000000000000000000000000000000000000000000000000000000000000'];
    }

    #[Test]
    #[DataProvider('provideBadPayloads')]
    public function itFailsToAcceptAMalformedDelivery(string $body): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('POST', self::PATH, server: $this->headers(self::sign($body, 'CARRIER_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideBadPayloads(): iterable
    {
        yield 'reference blank' => [self::body('')];
        yield 'reference longer than the carrier can issue' => [self::body(str_repeat('A', 65))];
        // No value at all is mapped to `trackingNumber` — COLLECT_DENORMALIZATION_ERRORS
        // folds this into the same PartialDenormalizationException as a type mismatch below.
        yield 'reference absent' => [json_encode(['unexpected' => 'field'], \JSON_THROW_ON_ERROR)];
        // A value is mapped to `trackingNumber` but of an incompatible type.
        yield 'reference not a string' => [json_encode(['trackingNumber' => ['nested' => 'object']], \JSON_THROW_ON_ERROR)];
    }

    #[Test]
    #[DataProvider('provideRequestsNotMatchingTheWebhookShape')]
    public function itRejectsARequestNotMatchingTheWebhookShape(string $method, string $body): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request($method, self::PATH, server: $this->headers(self::sign($body, 'CARRIER_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRequestsNotMatchingTheWebhookShape(): iterable
    {
        yield 'method is not POST' => ['GET', self::body(self::anyTrackingNumber())];
        yield 'body is not syntactically valid JSON' => ['POST', '{invalid'];
    }

    #[Test]
    public function itFailsToAcceptAnUntrackedDelivery(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body('ACME-NEVER-ISSUED');

        // When
        $client->request('POST', self::PATH, server: $this->headers(self::sign($body, 'CARRIER_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string, string>
     */
    private function headers(?string $signature): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if (null !== $signature) {
            $headers['HTTP_X_CARRIER_SIGNATURE'] = $signature;
        }

        return $headers;
    }

    private static function body(string $trackingNumber): string
    {
        return json_encode(['trackingNumber' => $trackingNumber], \JSON_THROW_ON_ERROR);
    }

    private static function anyTrackingNumber(): string
    {
        return ShipmentBuilder::sample('trackingNumber')->value;
    }
}
