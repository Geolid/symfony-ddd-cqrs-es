<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Carrier\Acme;

use Fulfilment\Shipment\Infrastructure\Carrier\Acme\AcmeCarrierGateway;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\AcmeClient;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\Exception\AcmeClientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AcmeCarrierGatewayTest extends TestCase
{
    #[Test]
    public function itBooksAPickupAndReadsTheCarrierReference(): void
    {
        // Given
        $shipmentId = Uuid::uuid7()->toString();
        $response = self::jsonResponse(['trackingNumber' => 'ACME-4Q7X2K9']);

        // When
        $trackingReference = self::gateway($response)->requestPickup($shipmentId, '12 Baker Street');

        // Then
        self::assertSame('ACME-4Q7X2K9', $trackingReference);
        self::assertSame('https://carrier.acme.test/pickups', $response->getRequestUrl());
        self::assertSame(
            ['reference' => $shipmentId, 'destination' => '12 Baker Street'],
            json_decode((string) $response->getRequestOptions()['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    #[DataProvider('provideUnreachableCarriers')]
    public function itThrowsOnACarrierItCannotReach(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        self::gateway($response)->requestPickup(Uuid::uuid7()->toString(), '12 Baker Street');
    }

    /**
     * @return iterable<string, array{callable|MockResponse}>
     */
    public static function provideUnreachableCarriers(): iterable
    {
        yield 'connection refused' => [static fn () => throw new TransportException('Connection refused')];
        yield 'carrier out of order' => [self::jsonResponse([], 500)];
    }

    #[Test]
    #[DataProvider('provideUnreadableResponses')]
    public function itThrowsOnAPickupResponseItCannotRead(MockResponse $response): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        self::gateway($response)->requestPickup(Uuid::uuid7()->toString(), '12 Baker Street');
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableResponses(): iterable
    {
        yield 'body that is not JSON' => [self::jsonResponse('<html></html>')];
        yield 'tracking number absent' => [self::jsonResponse(['status' => 'booked'])];
        yield 'tracking number blank' => [self::jsonResponse(['trackingNumber' => ''])];
        yield 'tracking number of another type' => [self::jsonResponse(['trackingNumber' => 42])];
    }

    private static function gateway(callable|MockResponse $response): AcmeCarrierGateway
    {
        return new AcmeCarrierGateway(new AcmeClient(new MockHttpClient($response, 'https://carrier.acme.test')));
    }

    private static function jsonResponse(mixed $body, int $statusCode = 200): MockResponse
    {
        return new MockResponse(
            \is_string($body) ? $body : json_encode($body, \JSON_THROW_ON_ERROR),
            [
                'http_code' => $statusCode,
                'response_headers' => ['content-type' => 'application/json'],
            ],
        );
    }
}
