<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($identity->id->toString());
    }

    #[Test]
    public function itErasesWhenNotYetRequested(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($identity->id->toString());
    }

    #[Test]
    public function itReleasesEmailUniqueness(): void
    {
        // Given — dispatched, not built via the Builder, so the email is genuinely claimed first.
        $email = IdentityBuilder::sample('email')->value;
        $identityId = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($identityId, IdentityBuilder::sample('fullName')->value, $email));
        $this->dispatch(new EraseIdentity($identityId));

        // When
        $this->dispatch(new RegisterIdentity(
            Uuid::uuid7()->toString(),
            IdentityBuilder::sample('fullName')->value,
            $email,
        ));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new EraseIdentity(Uuid::uuid7()->toString()));
    }
}
