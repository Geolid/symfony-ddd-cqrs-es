<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Infrastructure\Security\ActingIdentityMessengerMiddleware;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\ActingIdentityAware;
use Shared\Application\Exception\ActingIdentityNotActiveException;
use Support\AbstractIntegrationTestCase;
use Support\Doubles\DummyMessage;
use Support\Doubles\StubNextMiddleware;
use Support\Doubles\StubStack;
use Symfony\Component\Messenger\Envelope;

final class ActingIdentityMessengerMiddlewareTest extends AbstractIntegrationTestCase
{
    private ActingIdentityMessengerMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ActingIdentityMessengerMiddleware($this->service(IdentityFinderInterface::class));
    }

    #[Test]
    public function itSkipsUnawareMessage(): void
    {
        // Given
        $envelope = new Envelope(new DummyMessage());

        // When
        $handled = $this->middleware->handle($envelope, new StubStack(new StubNextMiddleware()));

        // Then
        self::assertSame($envelope, $handled);
    }

    #[Test]
    public function itPassesThroughWhenActive(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $envelope = new Envelope(new StubActingIdentityCommand($identity->id->toString()));

        // When
        $handled = $this->middleware->handle($envelope, new StubStack(new StubNextMiddleware()));

        // Then
        self::assertSame($envelope, $handled);
    }

    #[Test]
    public function itThrowsWhenSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();
        $envelope = new Envelope(new StubActingIdentityCommand($identity->id->toString()));

        // Then
        $this->expectException(ActingIdentityNotActiveException::class);

        // When
        $this->middleware->handle($envelope, new StubStack(new StubNextMiddleware()));
    }

    #[Test]
    public function itThrowsWhenErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->store();
        $envelope = new Envelope(new StubActingIdentityCommand($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->middleware->handle($envelope, new StubStack(new StubNextMiddleware()));
    }
}

final readonly class StubActingIdentityCommand implements ActingIdentityAware
{
    public function __construct(private string $identityId)
    {
    }

    public function actingIdentityId(): string
    {
        return $this->identityId;
    }
}
