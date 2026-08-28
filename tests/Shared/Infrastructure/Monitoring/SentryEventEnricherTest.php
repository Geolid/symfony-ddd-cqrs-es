<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Monitoring;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Shared\Infrastructure\Monitoring\SentryEventEnricher;

final class SentryEventEnricherTest extends TestCase
{
    #[Test]
    public function itTagsTheReportWithTheApplication(): void
    {
        // Given
        $enricher = new SentryEventEnricher('web');

        // When
        $event = $enricher->beforeSend()(Event::createEvent());

        // Then
        self::assertInstanceOf(Event::class, $event);
        self::assertSame(['app_id' => 'web'], $event->getTags());
    }

    #[Test]
    public function itTagsNothingWithoutAnApplication(): void
    {
        // Given
        $enricher = new SentryEventEnricher(null);

        // When
        $event = $enricher->beforeSend()(Event::createEvent());

        // Then
        self::assertInstanceOf(Event::class, $event);
        self::assertSame([], $event->getTags());
    }
}
