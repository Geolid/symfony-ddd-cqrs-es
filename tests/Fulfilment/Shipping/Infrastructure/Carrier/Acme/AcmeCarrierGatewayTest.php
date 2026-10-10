<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Carrier\Acme;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierFatalFailureException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierTransientFailureException;
use Fulfilment\Shipping\Infrastructure\Carrier\Acme\AcmeCarrierGateway;
use Fulfilment\Shipping\Infrastructure\Carrier\Acme\AcmeClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Tests\Support\Factory\PostalAddressFactory;
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
        $response = self::jsonResponse(['tracking_number' => 'ACME-4Q7X2K9']);
        $origin = PostalAddressFactory::new()->create();
        $destination = PostalAddressFactory::new()->create();

        // When
        $trackingNumber = $this->gateway($response)->manifest($shipmentId, $origin, $destination);

        // Then
        self::assertSame('ACME-4Q7X2K9', $trackingNumber);

        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://carrier.acme.test/shipments/ship', $requestUrl);
        $headers = $response->getRequestOptions()['headers'];
        self::assertContains('Idempotency-Key: '.$shipmentId, $headers);
        $requestBody = $this->requestBody($response);
        self::assertSame(
            [
                'reference_number' => $shipmentId,
                'shipper' => PostalAddressMapper::toArray($origin),
                'ship_to' => PostalAddressMapper::toArray($destination),
            ],
            $requestBody,
        );
    }

    #[Test]
    #[DataProvider('provideTransientFailures')]
    public function itThrowsTransientWhenCarrierUnreachable(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(CarrierTransientFailureException::class);

        // When
        $this->gateway($response)->manifest(Uuid::uuid7()->toString(), PostalAddressFactory::new()->create(), PostalAddressFactory::new()->create());
    }

    /**
     * @return iterable<string, array{callable|MockResponse}>
     */
    public static function provideTransientFailures(): iterable
    {
        yield 'connection refused' => [static fn () => throw new TransportException('Connection refused')];
        yield 'carrier out of order' => [self::jsonResponse([], 500)];
    }

    #[Test]
    public function itThrowsFatalWhenManifestRequestRejected(): void
    {
        // Then
        $this->expectException(CarrierFatalFailureException::class);

        // When
        $this->gateway(self::jsonResponse(['error' => 'invalid address'], 400))->manifest(Uuid::uuid7()->toString(), PostalAddressFactory::new()->create(), PostalAddressFactory::new()->create());
    }

    #[Test]
    #[DataProvider('provideUnreadableResponses')]
    public function itThrowsFatalWhenManifestResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(CarrierFatalFailureException::class);

        // When
        $this->gateway($response)->manifest(Uuid::uuid7()->toString(), PostalAddressFactory::new()->create(), PostalAddressFactory::new()->create());
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableResponses(): iterable
    {
        yield 'malformed JSON body' => [self::jsonResponse('<html></html>')];
        yield 'tracking number absent' => [self::jsonResponse(['status' => 'booked'])];
        yield 'tracking number blank' => [self::jsonResponse(['tracking_number' => ''])];
        yield 'tracking number of another type' => [self::jsonResponse(['tracking_number' => 42])];
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
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://carrier.acme.test/track/details/ACME-4Q7X2K9', $requestUrl);
    }

    #[Test]
    public function itThrowsTransientWhenCheckingStatusAndCarrierUnreachable(): void
    {
        // Then
        $this->expectException(CarrierTransientFailureException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->checkStatus('ACME-4Q7X2K9');
    }

    #[Test]
    #[DataProvider('provideUnreadableStatusResponses')]
    public function itThrowsFatalWhenStatusResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(CarrierFatalFailureException::class);

        // When
        $this->gateway($response)->checkStatus('ACME-4Q7X2K9');
    }

    #[Test]
    public function itThrowsFatalWhenStatusUnrecognized(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => 'teleported']);

        // Then
        $this->expectException(CarrierFatalFailureException::class);

        // When
        $this->gateway($response)->checkStatus('ACME-4Q7X2K9');
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableStatusResponses(): iterable
    {
        yield 'malformed JSON body' => [self::jsonResponse('<html></html>')];
        yield 'status absent' => [self::jsonResponse(['reference' => 'ACME-4Q7X2K9'])];
        yield 'status blank' => [self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => ''])];
        yield 'status of another type' => [self::jsonResponse(['reference' => 'ACME-4Q7X2K9', 'status' => 42])];
    }

    private function gateway(callable|MockResponse $response): AcmeCarrierGateway
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
