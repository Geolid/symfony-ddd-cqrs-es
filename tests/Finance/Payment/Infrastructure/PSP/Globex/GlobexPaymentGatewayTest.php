<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\PSP\Globex;

use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Infrastructure\PSP\Globex\GlobexClient;
use Finance\Payment\Infrastructure\PSP\Globex\GlobexPaymentGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\Clock\Clock;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GlobexPaymentGatewayTest extends TestCase
{
    #[Test]
    public function itRequestsPayment(): void
    {
        // Given
        $paymentId = Uuid::uuid7()->toString();
        $checkoutSessionId = Uuid::uuid7()->toString();
        $expiresAt = $this->expiresAt();
        $response = self::jsonResponse([
            'id' => 'GLBX-9F3K2M1P',
            'url' => 'https://checkout.globex.test/pay/GLBX-9F3K2M1P',
        ]);

        // When
        $session = $this->gateway($response)->requestPayment($paymentId, $checkoutSessionId, 4_200, 'https://web.test/sales/orders', $this->billingAddress(), $expiresAt);

        // Then
        self::assertSame('GLBX-9F3K2M1P', $session->reference);
        self::assertSame('https://checkout.globex.test/pay/GLBX-9F3K2M1P', $session->checkoutUrl);

        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/checkout/sessions', $requestUrl);
        $headers = $response->getRequestOptions()['headers'];
        self::assertContains('Idempotency-Key: '.$paymentId, $headers);
        self::assertSame(
            [
                'client_reference_id' => $checkoutSessionId,
                'amountInCents' => 4_200,
                'mode' => 'payment',
                'success_url' => 'https://web.test/sales/orders',
                'billingAddress' => PostalAddressMapper::toArray($this->billingAddress()),
                'expiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
            ],
            $this->requestBody($response),
        );
    }

    #[Test]
    #[DataProvider('provideTransientFailures')]
    public function itThrowsTransientWhenPaymentProviderUnreachable(callable|MockResponse $response): void
    {
        // Then
        $this->expectException(PaymentTransientFailureException::class);

        // When
        $this->gateway($response)->requestPayment(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', $this->billingAddress(), $this->expiresAt());
    }

    /**
     * @return iterable<string, array{callable|MockResponse}>
     */
    public static function provideTransientFailures(): iterable
    {
        yield 'connection refused' => [static fn () => throw new TransportException('Connection refused')];
        yield 'provider out of order' => [self::jsonResponse([], 500)];
    }

    #[Test]
    public function itThrowsFatalWhenPaymentRequestRejected(): void
    {
        // Then
        $this->expectException(PaymentFatalFailureException::class);

        // When
        $this->gateway(self::jsonResponse(['error' => 'invalid amount'], 400))->requestPayment(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', $this->billingAddress(), $this->expiresAt());
    }

    #[Test]
    #[DataProvider('provideUnreadableResponses')]
    public function itThrowsFatalWhenSessionResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(PaymentFatalFailureException::class);

        // When
        $this->gateway($response)->requestPayment(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 4_200, 'https://web.test/sales/orders', $this->billingAddress(), $this->expiresAt());
    }

    /**
     * @return iterable<string, array{MockResponse}>
     */
    public static function provideUnreadableResponses(): iterable
    {
        yield 'malformed JSON body' => [self::jsonResponse('<html></html>')];
        yield 'id absent' => [self::jsonResponse(['url' => 'https://checkout.globex.test/pay/x'])];
        yield 'id blank' => [self::jsonResponse(['id' => '', 'url' => 'https://checkout.globex.test/pay/x'])];
        yield 'id of another type' => [self::jsonResponse(['id' => 42, 'url' => 'https://checkout.globex.test/pay/x'])];
        yield 'url absent' => [self::jsonResponse(['id' => 'GLBX-9F3K2M1P'])];
        yield 'url blank' => [self::jsonResponse(['id' => 'GLBX-9F3K2M1P', 'url' => ''])];
        yield 'url of another type' => [self::jsonResponse(['id' => 'GLBX-9F3K2M1P', 'url' => 42])];
    }

    #[Test]
    public function itCapturesSession(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'captured']);

        // When
        $status = $this->gateway($response)->capture('GLBX-9F3K2M1P');

        // Then
        self::assertSame(PaymentGatewayStatus::CAPTURED, $status);
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/checkout/sessions/GLBX-9F3K2M1P/capture', $requestUrl);
        self::assertSame([], $this->requestBody($response));
        $headers = $response->getRequestOptions()['headers'];
        self::assertContains('Idempotency-Key: GLBX-9F3K2M1P:capture', $headers);
    }

    #[Test]
    public function itVoidsSession(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'voided']);

        // When
        $status = $this->gateway($response)->void('GLBX-9F3K2M1P');

        // Then
        self::assertSame(PaymentGatewayStatus::VOIDED, $status);
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/checkout/sessions/GLBX-9F3K2M1P/cancel', $requestUrl);
        self::assertSame([], $this->requestBody($response));
        $headers = $response->getRequestOptions()['headers'];
        self::assertContains('Idempotency-Key: GLBX-9F3K2M1P:cancel', $headers);
    }

    #[Test]
    public function itThrowsTransientWhenVoidingAndPaymentProviderUnreachable(): void
    {
        // Then
        $this->expectException(PaymentTransientFailureException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->void('GLBX-9F3K2M1P');
    }

    #[Test]
    public function itChecksSessionStatus(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'authorized']);

        // When
        $status = $this->gateway($response)->checkStatus('GLBX-9F3K2M1P');

        // Then
        self::assertSame(PaymentGatewayStatus::AUTHORIZED, $status);
        $requestUrl = $response->getRequestUrl();
        self::assertSame('https://payments.globex.test/checkout/sessions/GLBX-9F3K2M1P', $requestUrl);
    }

    #[Test]
    public function itThrowsTransientWhenCheckingStatusAndPaymentProviderUnreachable(): void
    {
        // Then
        $this->expectException(PaymentTransientFailureException::class);

        // When
        $this->gateway(static fn () => throw new TransportException('Connection refused'))->checkStatus('GLBX-9F3K2M1P');
    }

    #[Test]
    #[DataProvider('provideUnreadableStatusResponses')]
    public function itThrowsFatalWhenStatusResponseUnreadable(MockResponse $response): void
    {
        // Then
        $this->expectException(PaymentFatalFailureException::class);

        // When
        $this->gateway($response)->checkStatus('GLBX-9F3K2M1P');
    }

    #[Test]
    public function itThrowsFatalWhenStatusUnrecognized(): void
    {
        // Given
        $response = self::jsonResponse(['reference' => 'GLBX-9F3K2M1P', 'status' => 'teleported']);

        // Then
        $this->expectException(PaymentFatalFailureException::class);

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

    private function expiresAt(): \DateTimeImmutable
    {
        return Clock::get()->now()->modify('+30 minutes');
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
