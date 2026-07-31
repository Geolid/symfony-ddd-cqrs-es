<?php

declare(strict_types=1);

namespace Webhook\Tests\Webhook;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $shipment = ShipmentTestFactory::new()->dispatched()->create();
        $this->store($shipment);
        $body = self::body($shipment->id()->toString());

        // When
        $client->request('POST', self::PATH, server: self::headers(self::sign($body)), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertSame('delivered', $this->statusOf($shipment->id()->toString()));
    }

    #[Test]
    #[DataProvider('provideBadSignatures')]
    public function itRejectsAnUnsignedDelivery(?string $signature): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(Uuid::uuid7()->toString());

        // When
        $client->request('POST', self::PATH, server: self::headers($signature), content: $body);

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
        $client->request('POST', self::PATH, server: self::headers(self::sign($body)), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideBadPayloads(): iterable
    {
        yield 'shipment out of format' => [self::body('not-a-uuid')];
        yield 'shipment absent' => [json_encode(['unexpected' => 'field'], \JSON_THROW_ON_ERROR)];
    }

    #[Test]
    public function itFailsToAcceptAnUnknownShipment(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(Uuid::uuid7()->toString());

        // When
        $client->request('POST', self::PATH, server: self::headers(self::sign($body)), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string, string>
     */
    private static function headers(?string $signature): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if (null !== $signature) {
            $headers['HTTP_X_CARRIER_SIGNATURE'] = $signature;
        }

        return $headers;
    }

    private static function body(string $shipmentId): string
    {
        return json_encode(['shipmentId' => $shipmentId], \JSON_THROW_ON_ERROR);
    }

    private function statusOf(string $id): string
    {
        foreach ($this->service(ShipmentFinderInterface::class) as $shipment) {
            if ($id === $shipment->id) {
                return $shipment->status;
            }
        }

        self::fail(\sprintf('Shipment "%s" was not projected.', $id));
    }
}
