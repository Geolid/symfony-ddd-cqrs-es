<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Sentry;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Shared\Infrastructure\Sentry\AppIdTagger;

final class AppIdTaggerTest extends TestCase
{
    #[Test]
    public function itTags(): void
    {
        // Given
        $enricher = new AppIdTagger('test-app');

        // When
        $event = $enricher->beforeSend()(Event::createEvent());

        // Then
        self::assertInstanceOf(Event::class, $event);
        self::assertSame(['app_id' => 'test-app'], $event->getTags());
    }

    #[Test]
    public function itIgnoresWhenNoApplication(): void
    {
        // Given
        $enricher = new AppIdTagger(null);

        // When
        $event = $enricher->beforeSend()(Event::createEvent());

        // Then
        self::assertInstanceOf(Event::class, $event);
        self::assertSame([], $event->getTags());
    }
}
