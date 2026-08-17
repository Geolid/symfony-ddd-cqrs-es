<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Monitoring\Sentry;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Shared\Infrastructure\Monitoring\Sentry\SentryMessengerMiddleware;
use Support\Doubles\DummyMessage;
use Support\Doubles\StubNextMiddleware;
use Support\Doubles\StubStack;
use Symfony\Component\Messenger\Envelope;

final class SentryMessengerMiddlewareTest extends TestCase
{
    private HubInterface $previousHub;

    private Scope $scope;

    protected function setUp(): void
    {
        $this->previousHub = SentrySdk::getCurrentHub();
        $this->scope = new Scope();

        SentrySdk::setCurrentHub(new Hub(null, $this->scope));
    }

    protected function tearDown(): void
    {
        SentrySdk::setCurrentHub($this->previousHub);
    }

    #[Test]
    public function itHandsTheMessageDownUntouched(): void
    {
        // Given
        $envelope = new Envelope(new DummyMessage());

        // When
        $handled = new SentryMessengerMiddleware()->handle($envelope, new StubStack(new StubNextMiddleware()));

        // Then
        self::assertSame($envelope, $handled);
        self::assertSame([], $this->contextOfAReport());
    }

    #[Test]
    public function itNamesTheMessageInFlightWhenItFails(): void
    {
        // Given
        $failure = new \RuntimeException('Handler blew up.');

        // Then
        $this->expectExceptionObject($failure);

        // When
        try {
            new SentryMessengerMiddleware()->handle(new Envelope(new DummyMessage()), new StubStack(new StubNextMiddleware($failure)));
        } finally {
            self::assertSame(['message' => DummyMessage::class], $this->contextOfAReport());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function contextOfAReport(): array
    {
        $contexts = $this->scope->applyToEvent(Event::createEvent())?->getContexts() ?? [];

        /** @var array<string, mixed> */
        return $contexts['messenger'] ?? [];
    }
}
