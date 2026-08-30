<?php

declare(strict_types=1);

namespace Webhook\Tests\Webhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\HttpFoundation\Response;
use Webhook\Tests\Support\AbstractWebhookTestCase;
use Webhook\Webhook\PaymentRefundedParser;

final class PaymentRefundedWebhookTest extends AbstractWebhookTestCase
{
    #[Test]
    public function itAcceptsAPaymentRefundConfirmation(): void
    {
        // Given
        $client = self::createClient();
        $order = OrderTestFactory::new()->create();
        $paymentFactory = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentFactory->create();
        $reference = $paymentFactory->attribute('reference')->value;
        $this->store($order, $orderPayment);
        $body = self::body($reference);

        // When
        $client->request('POST', $this->path(), server: $this->headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertSame(OrderPaymentStatus::REFUNDED, $this->statusOf($orderPayment->id->toString()));
    }

    #[Test]
    #[DataProvider('provideBadSignatures')]
    public function itRejectsAnUnsignedRefundConfirmation(?string $signature): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(self::anyReference());

        // When
        $client->request('POST', $this->path(), server: $this->headers($signature), content: $body);

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
    public function itFailsToAcceptAMalformedRefundConfirmation(string $body): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('POST', $this->path(), server: $this->headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

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
        $client->request($method, $this->path(), server: $this->headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRequestsNotMatchingTheWebhookShape(): iterable
    {
        yield 'method is not POST' => ['GET', self::body(self::anyReference())];
        yield 'body is not syntactically valid JSON' => ['POST', '{invalid'];
    }

    #[Test]
    public function itFailsToAcceptAnUnknownRefundConfirmation(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body('GLBX-NEVER-ISSUED');

        // When
        $client->request('POST', $this->path(), server: $this->headers(self::sign($body, 'PAYMENT_WEBHOOK_SECRET')), content: $body);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function path(): string
    {
        return \sprintf('/webhooks/%s', PaymentRefundedParser::EVENT_TYPE);
    }

    /**
     * @return array<string, string>
     */
    private function headers(?string $signature): array
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

    private static function anyReference(): string
    {
        return OrderPaymentTestFactory::new()->attribute('reference')->value;
    }

    private function statusOf(string $id): OrderPaymentStatus
    {
        return $this->service(OrderPaymentFinderInterface::class)->ofId($id)->status;
    }
}
