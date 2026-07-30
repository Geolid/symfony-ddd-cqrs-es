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
        $client->request('GET', '/shipments');

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'No shipments yet');
    }
}
