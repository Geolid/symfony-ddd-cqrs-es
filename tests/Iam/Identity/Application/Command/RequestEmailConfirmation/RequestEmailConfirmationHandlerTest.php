<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RequestEmailConfirmation;

use Iam\Identity\Application\Command\RequestEmailConfirmation\RequestEmailConfirmation;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Exception\EmailConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotPendingException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestEmailConfirmationHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($identity);

        // When
        $this->dispatch(new RequestEmailConfirmation($identity->id->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(IdentityStatus::PENDING, $result->status);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->emailConfirmationRequestedAt->format(\DateTimeInterface::ATOM),
        );
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new RequestEmailConfirmation(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new RequestEmailConfirmation($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenNotPending(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotPendingException::class);

        // When
        $this->dispatch(new RequestEmailConfirmation($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenRequestedTooRecently(): void
    {
        // Given
        $identity = IdentityBuilder::new()->emailConfirmationRequested()->create();
        $this->store($identity);

        // Then
        $this->expectException(EmailConfirmationRequestedTooRecentlyException::class);

        // When
        $this->dispatch(new RequestEmailConfirmation($identity->id->toString()));
    }
}
