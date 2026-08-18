<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Payment\Globex;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Order\Infrastructure\Payment\Globex\Exception\GlobexClientException;
use Sales\Order\Infrastructure\Payment\Globex\GlobexClient;
use Sales\Order\Infrastructure\Payment\Globex\GlobexPaymentGateway;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GlobexPaymentGatewayTest extends TestCase
{
    #[Test]
    public function itChargesOrderAndReadsProviderSession(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $response = self::jsonResponse([
            'chargeReference' => 'GLBX-9F3K2M1P',
            'checkoutUrl' => 'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
        ]);

        // When
        $session = self::gateway($response)->requestPayment($orderId, 4_200, 'https://web.test/sales/orders', self::billingAddress());

        // Then
        self::assertSame('GLBX-9F3K2M1P', $session->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $session->checkoutUrl);
        self::assertSame('https://payments.globex.test/charges', $response->getRequestUrl());
        self::assertSame(
            [
                'reference' => $orderId,
                'amountInCents' => 4_200,
                'returnUrl' => 'https://web.test/sales/orders',
                'billingAddress' => [
                    'firstName' => 'Ada',
                    'lastName' => 'Lovelace',
                    'street' => '12 rue des Lilas',
                    'postalCode' => '75001',
                    'city' => 'Paris',
                ],
            ],
            json_decode((string) $response->getRequestOptions()['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    #[DataProvider('provideUnreachableProviders')]
    public function itThrowsOnProviderItCannotReach(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        self::gateway($response)->requestPayment(Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', self::billingAddress());
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
    public function itThrowsOnChargeResponseItCannotRead(MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        self::gateway($response)->requestPayment(Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', self::billingAddress());
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableResponses(): iterable
    {
        yield 'body that is not JSON' => [self::jsonResponse('<html></html>')];
        yield 'charge reference absent' => [self::jsonResponse(['checkoutUrl' => 'https://fake-checkout.test/?ref=x'])];
        yield 'charge reference blank' => [self::jsonResponse(['chargeReference' => '', 'checkoutUrl' => 'https://fake-checkout.test/?ref=x'])];
        yield 'charge reference of another type' => [self::jsonResponse(['chargeReference' => 42, 'checkoutUrl' => 'https://fake-checkout.test/?ref=x'])];
        yield 'checkout url absent' => [self::jsonResponse(['chargeReference' => 'GLBX-9F3K2M1P'])];
        yield 'checkout url blank' => [self::jsonResponse(['chargeReference' => 'GLBX-9F3K2M1P', 'checkoutUrl' => ''])];
        yield 'checkout url of another type' => [self::jsonResponse(['chargeReference' => 'GLBX-9F3K2M1P', 'checkoutUrl' => 42])];
    }

    #[Test]
    public function itVoidsCharge(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'voided']);

        // When
        self::gateway($response)->void('GLBX-9F3K2M1P');

        // Then
        self::assertSame('https://payments.globex.test/void', $response->getRequestUrl());
        self::assertSame(
            ['reference' => 'GLBX-9F3K2M1P'],
            json_decode((string) $response->getRequestOptions()['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function itThrowsWhenVoidingOnProviderItCannotReach(): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        self::gateway(static fn () => throw new TransportException('Connection refused'))->void('GLBX-9F3K2M1P');
    }

    #[Test]
    public function itRefundsCharge(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'refunding']);

        // When
        self::gateway($response)->refund('GLBX-9F3K2M1P');

        // Then
        self::assertSame('https://payments.globex.test/refund', $response->getRequestUrl());
        self::assertSame(
            ['reference' => 'GLBX-9F3K2M1P'],
            json_decode((string) $response->getRequestOptions()['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function itThrowsWhenRefundingOnProviderItCannotReach(): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        self::gateway(static fn () => throw new TransportException('Connection refused'))->refund('GLBX-9F3K2M1P');
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

    private static function billingAddress(): PostalAddress
    {
        return PostalAddress::of(
            FullName::of('Ada', 'Lovelace'),
            Address::of('12 rue des Lilas', '75001', 'Paris'),
        );
    }
}
