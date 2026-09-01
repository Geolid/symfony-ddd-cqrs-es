<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Sentry;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Shared\Infrastructure\Sentry\ErrorContextMessengerMiddleware;
use Support\Doubles\DummyMessage;
use Support\Doubles\StubNextMiddleware;
use Support\Doubles\StubStack;
use Symfony\Component\Messenger\Envelope;

final class ErrorContextMessengerMiddlewareTest extends TestCase
{
    private HubInterface $previousHub;

    private Scope $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousHub = SentrySdk::getCurrentHub();
        $this->scope = new Scope();

        SentrySdk::setCurrentHub(new Hub(null, $this->scope));
    }

    protected function tearDown(): void
    {
        SentrySdk::setCurrentHub($this->previousHub);

        parent::tearDown();
    }

    #[Test]
    public function itPassesThrough(): void
    {
        // Given
        $envelope = new Envelope(new DummyMessage());

        // When
        $handled = new ErrorContextMessengerMiddleware()->handle($envelope, new StubStack(new StubNextMiddleware()));

        // Then
        self::assertSame($envelope, $handled);
        self::assertSame([], $this->messengerContext());
    }

    #[Test]
    public function itAddsContextWhenStackFails(): void
    {
        // Given
        $failure = new \RuntimeException('Handler blew up.');

        // When
        $caught = null;
        try {
            new ErrorContextMessengerMiddleware()->handle(new Envelope(new DummyMessage()), new StubStack(new StubNextMiddleware($failure)));
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        }

        // Then
        self::assertSame($failure, $caught);
        self::assertSame(['message' => DummyMessage::class], $this->messengerContext());
    }

    /**
     * @return array<string, mixed>
     */
    private function messengerContext(): array
    {
        $contexts = $this->scope->applyToEvent(Event::createEvent())?->getContexts() ?? [];

        return $contexts['messenger'] ?? [];
    }
}
