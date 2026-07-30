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
        $response = $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => 'customer-1', 'totalAmountInCents' => 3_500],
        ]);

        // Then
        self::assertResponseStatusCodeSame(201);
        $id = $response->toArray()['id'];

        // When — read it back
        $response = $client->request('GET', \sprintf('/v1/sales/orders/%s', $id));

        // Then
        self::assertResponseStatusCodeSame(200);
        $fetched = $response->toArray();
        self::assertSame('customer-1', $fetched['customerId']);
        self::assertSame(3_500, $fetched['totalAmountInCents']);
        self::assertSame('placed', $fetched['status']);

        // When — cancel it
        $client->request('POST', \sprintf('/v1/sales/orders/%s/cancel', $id));

        // Then
        self::assertResponseStatusCodeSame(204);

        $response = $client->request('GET', \sprintf('/v1/sales/orders/%s', $id));
        self::assertSame('cancelled', $response->toArray()['status']);
    }

    #[Test]
    public function itReturns404ForAnUnknownOrder(): void
    {
        $client = self::jsonClient();

        $client->request('GET', '/v1/sales/orders/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
