<?php

declare(strict_types=1);

namespace Webhook\Tests\Webhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Enum\OrderPaymentStatus;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Symfony\Component\HttpFoundation\Response;
use Webhook\Tests\Support\AbstractWebhookTestCase;
use Webhook\Webhook\PaymentCapturedParser;

final class PaymentCapturedWebhookTest extends AbstractWebhookTestCase
{
    private const string REFERENCE = 'GLBX-9F3K2M1P';

    #[Test]
    public function itAcceptsAPaymentCapture(): void
    {
        // Given
        $client = self::createClient();
        $orderPayment = OrderPaymentTestFactory::new()->withReference(self::REFERENCE)->create();
        $this->store($orderPayment);
        $body = self::body(self::REFERENCE);

        // When
        $client->request('POST', self::path(), server: self::headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertSame(OrderPaymentStatus::CAPTURED, $this->statusOf($orderPayment->id()->toString()));
    }

    #[Test]
    #[DataProvider('provideBadSignatures')]
    public function itRejectsAnUnsignedCapture(?string $signature): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(self::REFERENCE);

        // When
        $client->request('POST', self::path(), server: self::headers($signature), content: $body);

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
    public function itFailsToAcceptAMalformedCapture(string $body): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('POST', self::path(), server: self::headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideBadPayloads(): iterable
    {
        yield 'reference blank' => [self::body('')];
        yield 'reference longer than the provider can issue' => [self::body(str_repeat('A', 65))];
        // No value at all is mapped to `paymentReference` — COLLECT_DENORMALIZATION_ERRORS
        // folds this into the same PartialDenormalizationException as a type mismatch below.
        yield 'reference absent' => [json_encode(['unexpected' => 'field'], \JSON_THROW_ON_ERROR)];
        // A value is mapped to `paymentReference` but of an incompatible type.
        yield 'reference not a string' => [json_encode(['paymentReference' => ['nested' => 'object']], \JSON_THROW_ON_ERROR)];
    }

    #[Test]
    #[DataProvider('provideRequestsNotMatchingTheWebhookShape')]
    public function itRejectsARequestNotMatchingTheWebhookShape(string $method, string $body): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request($method, self::path(), server: self::headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRequestsNotMatchingTheWebhookShape(): iterable
    {
        yield 'method is not POST' => ['GET', self::body(self::REFERENCE)];
        yield 'body is not syntactically valid JSON' => ['POST', '{invalid'];
    }

    #[Test]
    public function itFailsToAcceptAnUnknownCapture(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body('GLBX-NEVER-ISSUED');

        // When
        $client->request('POST', self::path(), server: self::headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private static function path(): string
    {
        return \sprintf('/webhooks/%s', PaymentCapturedParser::EVENT_TYPE);
    }

    /**
     * @return array<string, string>
     */
    private static function headers(?string $signature): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if (null !== $signature) {
            $headers['HTTP_X_PAYMENT_SIGNATURE'] = $signature;
        }

        return $headers;
    }

    private static function body(string $paymentReference): string
    {
        return json_encode(['paymentReference' => $paymentReference], \JSON_THROW_ON_ERROR);
    }

    private function statusOf(string $id): OrderPaymentStatus
    {
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference(self::REFERENCE);

        if ($id !== $result->id) {
            self::fail(\sprintf('OrderPayment "%s" was not projected.', $id));
        }

        return $result->status;
    }
}
