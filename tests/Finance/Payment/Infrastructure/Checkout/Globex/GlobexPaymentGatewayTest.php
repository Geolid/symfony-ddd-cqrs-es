<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Checkout\Globex;

use Finance\Payment\Application\Checkout\PaymentGatewayStatus;
use Finance\Payment\Infrastructure\Checkout\Globex\GlobexClient;
use Finance\Payment\Infrastructure\Checkout\Globex\GlobexClientException;
use Finance\Payment\Infrastructure\Checkout\Globex\GlobexPaymentGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GlobexPaymentGatewayTest extends TestCase
{
    #[Test]
    public function itRequestsPayment(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $response = self::jsonResponse([
            'chargeReference' => 'GLBX-9F3K2M1P',
            'checkoutUrl' => 'https://checkout.globex.test/pay/GLBX-9F3K2M1P',
        ]);

        // When
        $session = $this->gateway($response)->requestPayment($orderId, 4_200, 'https://web.test/sales/orders', $this->billingAddress());

        // Then
        self::assertSame('GLBX-9F3K2M1P', $session->reference);
        self::assertSame('https://checkout.globex.test/pay/GLBX-9F3K2M1P', $session->checkoutUrl);

        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/charges', $requestUrl);
        $headers = $response->getRequestOptions()['headers'];
        self::assertContains('Idempotency-Key: '.$orderId, $headers);
        self::assertSame(
            [
                'clientReferenceId' => $orderId,
                'amountInCents' => 4_200,
                'returnUrl' => 'https://web.test/sales/orders',
                'billingAddress' => [
                    'recipientName' => 'Ada Lovelace',
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
    #[DataProvider('provideUnreachableProviders')]
    public function itThrowsWhenPaymentProviderUnreachable(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway($response)->requestPayment(Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', $this->billingAddress());
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
    public function itThrowsWhenChargeResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway($response)->requestPayment(Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', $this->billingAddress());
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableResponses(): iterable
    {
        yield 'malformed JSON body' => [self::jsonResponse('<html></html>')];
        yield 'charge reference absent' => [self::jsonResponse(['checkoutUrl' => 'https://checkout.globex.test/pay/x'])];
        yield 'charge reference blank' => [self::jsonResponse(['chargeReference' => '', 'checkoutUrl' => 'https://checkout.globex.test/pay/x'])];
        yield 'charge reference of another type' => [self::jsonResponse(['chargeReference' => 42, 'checkoutUrl' => 'https://checkout.globex.test/pay/x'])];
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
        $this->gateway($response)->void('GLBX-9F3K2M1P');

        // Then
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/void', $requestUrl);
        self::assertSame(
            ['reference' => 'GLBX-9F3K2M1P'],
            $this->requestBody($response),
        );
    }

    #[Test]
    public function itThrowsWhenVoidingAndPaymentProviderUnreachable(): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->void('GLBX-9F3K2M1P');
    }

    #[Test]
    public function itRefundsCharge(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'refunding']);

        // When
        $this->gateway($response)->refund('GLBX-9F3K2M1P');

        // Then
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/refund', $requestUrl);
        self::assertSame(
            ['reference' => 'GLBX-9F3K2M1P'],
            $this->requestBody($response),
        );
    }

    #[Test]
    public function itThrowsWhenRefundingAndPaymentProviderUnreachable(): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->refund('GLBX-9F3K2M1P');
    }

    #[Test]
    public function itChecksChargeStatus(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'authorized']);

        // When
        $status = $this->gateway($response)->checkStatus('GLBX-9F3K2M1P');

        // Then
        self::assertSame(PaymentGatewayStatus::AUTHORIZED, $status);
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/charges/GLBX-9F3K2M1P', $requestUrl);
    }

    #[Test]
    public function itThrowsWhenCheckingStatusAndPaymentProviderUnreachable(): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->checkStatus('GLBX-9F3K2M1P');
    }

    #[Test]
    #[DataProvider('provideUnreadableStatusResponses')]
    public function itThrowsWhenStatusResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway($response)->checkStatus('GLBX-9F3K2M1P');
    }

    #[Test]
    public function itThrowsWhenStatusUnrecognized(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'teleported']);

        // Then
        $this->expectException(GlobexClientException::class);

        // When
        $this->gateway($response)->checkStatus('GLBX-9F3K2M1P');
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableStatusResponses(): iterable
    {
        yield 'malformed JSON body' => [self::jsonResponse('<html></html>')];
        yield 'status absent' => [self::jsonResponse(['reference' => 'GLBX-9F3K2M1P'])];
        yield 'status blank' => [self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => ''])];
        yield 'status of another type' => [self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 42])];
    }

    private function gateway(callable|MockResponse $response): GlobexPaymentGateway
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

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of(
            'Ada Lovelace',
            Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'),
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
