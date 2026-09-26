<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RequestEmailChange;

use Iam\Identity\Application\Command\RequestEmailChange\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Command\RequestEmailChange\RequestEmailChange;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\EmailChangeRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestEmailChangeHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($identity);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), IdentityBuilder::sample('email')->value));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenEmailAlreadyInUse(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        $email = IdentityBuilder::sample('email')->value;
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(IdentityUniqueKey::EMAIL),
            $email,
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(IdentityEmailAlreadyInUseException::class);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), $email));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new RequestEmailChange(Uuid::uuid7()->toString(), IdentityBuilder::sample('email')->value));
    }

    #[Test]
    public function itFailsWhenErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), IdentityBuilder::sample('email')->value));
    }

    #[Test]
    public function itFailsWhenTooRecent(): void
    {
        // Given
        $identity = IdentityBuilder::new()->emailChangeRequested(IdentityBuilder::sample('email')->value)->create();
        $this->store($identity);

        // Then
        $this->expectException(EmailChangeRequestedTooRecentlyException::class);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), IdentityBuilder::sample('email')->value));
    }
}
