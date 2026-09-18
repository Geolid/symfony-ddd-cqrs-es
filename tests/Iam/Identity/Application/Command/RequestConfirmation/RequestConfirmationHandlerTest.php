<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RequestConfirmation;

use Iam\Identity\Application\Command\RequestConfirmation\RequestConfirmation;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Domain\Exception\ConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyConfirmedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestConfirmationHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($identity);

        // When
        $this->dispatch(new RequestConfirmation($identity->id->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(IdentityVerificationStatus::PENDING, $result->verificationStatus);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->confirmationRequestedAt->format(\DateTimeInterface::ATOM),
        );
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new RequestConfirmation(Uuid::uuid7()->toString()));
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
        $this->dispatch(new RequestConfirmation($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenAlreadyConfirmed(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyConfirmedException::class);

        // When
        $this->dispatch(new RequestConfirmation($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenRequestedTooRecently(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmationRequested()->create();
        $this->store($identity);

        // Then
        $this->expectException(ConfirmationRequestedTooRecentlyException::class);

        // When
        $this->dispatch(new RequestConfirmation($identity->id->toString()));
    }
}
