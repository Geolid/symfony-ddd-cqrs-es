<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Monitoring\Sentry;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Shared\Infrastructure\Monitoring\Sentry\SentryContextProviderInterface;
use Shared\Infrastructure\Monitoring\Sentry\SentryEventEnricher;

final class SentryEventEnricherTest extends TestCase
{
    #[Test]
    public function itTagsTheReportWithTheApplicationAndEveryContextOffered(): void
    {
        // Given
        $enricher = new SentryEventEnricher('web', [
            new DummyContextProvider('billing', ['plan' => 'free']),
            new DummyContextProvider('silent', null),
        ]);

        // When
        $event = $enricher->beforeSend()(Event::createEvent());

        // Then
        self::assertSame(['app_id' => 'web'], $event->getTags());
        self::assertSame(['plan' => 'free'], $event->getContext('billing'));
        self::assertSame([], $event->getContext('silent'));
    }

    #[Test]
    public function itTagsNothingWithoutAnApplication(): void
    {
        // Given
        $enricher = new SentryEventEnricher(null, []);

        // When
        $event = $enricher->beforeSend()(Event::createEvent());

        // Then
        self::assertSame([], $event->getTags());
    }
}

final readonly class DummyContextProvider implements SentryContextProviderInterface
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function __construct(
        private string $name,
        private ?array $context,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function provide(): ?array
    {
        return $this->context;
    }
}
