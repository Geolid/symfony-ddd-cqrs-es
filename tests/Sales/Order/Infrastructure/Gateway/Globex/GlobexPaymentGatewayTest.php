<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Gateway\Globex;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Order\Infrastructure\Gateway\Globex\GlobexPaymentGateway;
use Shared\Infrastructure\Gateway\Globex\Exception\GlobexClientException;
use Shared\Infrastructure\Gateway\Globex\GlobexClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GlobexPaymentGatewayTest extends TestCase
{
    #[Test]
    public function itChargesAnOrderAndReadsTheProviderReference(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $response = self::jsonResponse(['chargeReference' => 'GLBX-9F3K2M1P']);

        // When
        $chargeReference = self::gateway($response)->requestPayment($orderId, 4_200);

        // Then
        self::assertSame('GLBX-9F3K2M1P', $chargeReference);
        self::assertSame('https://payments.globex.test/charges', $response->getRequestUrl());
        self::assertSame(
            ['reference' => $orderId, 'amountInCents' => 4_200],
            json_decode((string) $response->getRequestOptions()['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    #[DataProvider('provideUnreachableProviders')]
    public function itThrowsOnAProviderItCannotReach(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        self::gateway($response)->requestPayment(Uuid::uuid7()->toString(), 4_200);
    }

    /**
     * @return iterable<string, array{callable|MockResponse}>
     */
    public static function provideUnreachableProviders(): iterable
    {
        yield 'connection refused' => [static fn () => throw new TransportException('Connection refused')];
        yield 'provider out of order' => [self::jsonResponse([], 500)];
    }

    #[Test]
    #[DataProvider('provideUnreadableResponses')]
    public function itThrowsOnAChargeResponseItCannotRead(MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        self::gateway($response)->requestPayment(Uuid::uuid7()->toString(), 4_200);
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableResponses(): iterable
    {
        yield 'body that is not JSON' => [self::jsonResponse('<html></html>')];
        yield 'charge reference absent' => [self::jsonResponse(['status' => 'charged'])];
        yield 'charge reference blank' => [self::jsonResponse(['chargeReference' => ''])];
        yield 'charge reference of another type' => [self::jsonResponse(['chargeReference' => 42])];
    }

    private static function gateway(callable|MockResponse $response): GlobexPaymentGateway
    {
        return new GlobexPaymentGateway(new GlobexClient(new MockHttpClient($response, 'https://payments.globex.test')));
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
