<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Carrier\Acme;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\AcmeCarrierGateway;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\AcmeClient;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\AcmeClientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AcmeCarrierGatewayTest extends TestCase
{
    #[Test]
    public function itManifests(): void
    {
        // Given
        $shipmentId = Uuid::uuid7()->toString();
        $response = self::jsonResponse(['trackingNumber' => 'ACME-4Q7X2K9']);

        // When
        $trackingReference = $this->gateway($response)->manifest($shipmentId, $this->deliveryAddress());

        // Then
        self::assertSame('ACME-4Q7X2K9', $trackingReference);

        self::assertSame('https://carrier.acme.test/shipments', $response->getRequestUrl());
        self::assertContains('Idempotency-Key: '.$shipmentId, $response->getRequestOptions()['headers']);
        self::assertSame(
            [
                'clientReferenceId' => $shipmentId,
                'destination' => [
                    'recipient' => 'Ada Lovelace',
                    'street' => '12 rue des Lilas',
                    'postalCode' => '75001',
                    'city' => 'Paris',
                    'countryCode' => 'FR',
                ],
            ],
            $this->requestBody($response),
        );
    }

    #[Test]
    public function itManifestsReturn(): void
    {
        // Given
        $shipmentId = Uuid::uuid7()->toString();
        $response = self::jsonResponse(['trackingNumber' => 'ACME-RETURN-4Q7X2K9']);

        // When
        $returnTrackingReference = $this->gateway($response)->manifestReturn($shipmentId, $this->deliveryAddress());

        // Then
        self::assertSame('ACME-RETURN-4Q7X2K9', $returnTrackingReference);

        self::assertSame('https://carrier.acme.test/returns', $response->getRequestUrl());
        self::assertContains('Idempotency-Key: '.$shipmentId, $response->getRequestOptions()['headers']);
        self::assertSame(
            [
                'clientReferenceId' => $shipmentId,
                'origin' => [
                    'recipient' => 'Ada Lovelace',
                    'street' => '12 rue des Lilas',
                    'postalCode' => '75001',
                    'city' => 'Paris',
                    'countryCode' => 'FR',
                ],
                'destination' => [
                    'recipient' => 'Returns Department',
                    'street' => "1 rue de l'Entrepot",
                    'postalCode' => '75012',
                    'city' => 'Paris',
                    'countryCode' => 'FR',
                ],
            ],
            $this->requestBody($response),
        );
    }

    #[Test]
    #[DataProvider('provideUnreachableCarriers')]
    public function itThrowsWhenCarrierUnreachable(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        $this->gateway($response)->manifest(Uuid::uuid7()->toString(), $this->deliveryAddress());
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
    public function itThrowsWhenManifestResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        $this->gateway($response)->manifest(Uuid::uuid7()->toString(), $this->deliveryAddress());
    }

    #[Test]
    #[DataProvider('provideUnreadableResponses')]
    public function itThrowsWhenReturnManifestResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        $this->gateway($response)->manifestReturn(Uuid::uuid7()->toString(), $this->deliveryAddress());
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

    #[Test]
    public function itChecksTrackingStatus(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => 'dispatched']);

        // When
        $status = $this->gateway($response)->checkStatus('ACME-4Q7X2K9');

        // Then
        self::assertSame(CarrierGatewayStatus::DISPATCHED, $status);
        self::assertSame('https://carrier.acme.test/trackers/ACME-4Q7X2K9', $response->getRequestUrl());
    }

    #[Test]
    public function itThrowsWhenCheckingStatusAndCarrierUnreachable(): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->checkStatus('ACME-4Q7X2K9');
    }

    #[Test]
    #[DataProvider('provideUnreadableStatusResponses')]
    public function itThrowsWhenStatusResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(AcmeClientException::class);

        // When
        $this->gateway($response)->checkStatus('ACME-4Q7X2K9');
    }

    #[Test]
    public function itThrowsWhenStatusUnrecognized(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => 'teleported']);

        // Then
        $this->expectException(AcmeClientException::class);

        // When
        $this->gateway($response)->checkStatus('ACME-4Q7X2K9');
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableStatusResponses(): iterable
    {
        yield 'body that is not JSON' => [self::jsonResponse('<html></html>')];
        yield 'status absent' => [self::jsonResponse(['reference' => 'ACME-4Q7X2K9'])];
        yield 'status blank' => [self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => ''])];
        yield 'status of another type' => [self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => 42])];
    }

    private function gateway(callable|MockResponse $response): AcmeCarrierGateway
    {
        return new AcmeCarrierGateway(
            new AcmeClient(new MockHttpClient($response, 'https://carrier.acme.test')),
            'Returns Department',
            "1 rue de l'Entrepot",
            '75012',
            'Paris',
            'FR',
        );
    }

    private function deliveryAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
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

    /**
     * @return array<string, mixed>
     */
    private function requestBody(MockResponse $response): array
    {
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getRequestOptions()['body'], true, 512, \JSON_THROW_ON_ERROR);

        return $body;
    }
}
