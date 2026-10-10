<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RequestEmailChange;

use Iam\Identity\Application\Command\RequestEmailChange\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Command\RequestEmailChange\RequestEmailChange;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\EmailChangeRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
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
        $identity = IdentityFactory::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($identity);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), EmailFactory::new()->create()->value));

        // Then
        $this->expectException(EmailChangeRequestedTooRecentlyException::class);

        $this->dispatch(new RequestEmailChange($identity->id->toString(), EmailFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenEmailAlreadyInUse(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->store($identity);

        $email = EmailFactory::new()->create()->value;
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(IdentityUniqueKey::EMAIL),
            $email,
            IdentityIdFactory::new()->create()->toString(),
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
        $this->dispatch(new RequestEmailChange(IdentityIdFactory::new()->create()->toString(), EmailFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenErased(): void
    {
        // Given
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), EmailFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenTooRecent(): void
    {
        // Given
        $identity = IdentityFactory::new()->emailChangeRequested(EmailFactory::new()->create())->create();
        $this->store($identity);

        // Then
        $this->expectException(EmailChangeRequestedTooRecentlyException::class);

        // When
        $this->dispatch(new RequestEmailChange($identity->id->toString(), EmailFactory::new()->create()->value));
    }
}
