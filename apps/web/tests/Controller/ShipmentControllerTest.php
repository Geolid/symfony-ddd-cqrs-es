<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Web\Tests\Support\AbstractWebTestCase;

final class ShipmentControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itShowsEmptyState(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/fulfilment/shipments');

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="shipments-empty"]');
    }

    #[Test]
    public function itShowsShipmentsFilteredByStatus(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/fulfilment/shipments?status=pending&page=1&itemsPerPage=5');

        // Then
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function itRefusesAnUnknownStatus(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/fulfilment/shipments?status=teleported');

        // Then
        self::assertResponseStatusCodeSame(422);
    }
}
