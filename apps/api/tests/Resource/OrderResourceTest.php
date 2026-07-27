<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Tests\Support\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OrderResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itPlacesGetsAndCancelsAnOrder(): void
    {
        $client = self::jsonClient();

        // Given/When — place an order
        $client->request('POST', '/v1/ordering/orders', [
            'json' => ['customerId' => 'customer-1', 'totalAmountInCents' => 3_500],
        ]);

        // Then
        self::assertSame(201, $client->getResponse()->getStatusCode());
        /** @var array{id: string} $created */
        $created = json_decode((string) $client->getResponse()->getContent(), associative: true, flags: \JSON_THROW_ON_ERROR);
        $id = $created['id'];

        // When — read it back
        $client->request('GET', \sprintf('/v1/ordering/orders/%s', $id));

        // Then
        self::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{customerId: string, totalAmountInCents: int, status: string} $fetched */
        $fetched = json_decode((string) $client->getResponse()->getContent(), associative: true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('customer-1', $fetched['customerId']);
        self::assertSame(3_500, $fetched['totalAmountInCents']);
        self::assertSame('placed', $fetched['status']);

        // When — cancel it
        $client->request('POST', \sprintf('/v1/ordering/orders/%s/cancel', $id));

        // Then
        self::assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', \sprintf('/v1/ordering/orders/%s', $id));
        /** @var array{status: string} $cancelled */
        $cancelled = json_decode((string) $client->getResponse()->getContent(), associative: true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('cancelled', $cancelled['status']);
    }

    #[Test]
    public function itReturns404ForAnUnknownOrder(): void
    {
        $client = self::jsonClient();

        $client->request('GET', '/v1/ordering/orders/00000000-0000-0000-0000-000000000000');

        self::assertSame(404, $client->getResponse()->getStatusCode());
    }
}
