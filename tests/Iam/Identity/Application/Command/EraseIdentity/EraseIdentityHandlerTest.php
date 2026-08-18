<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class EraseIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));
        $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
    }

    #[Test]
    public function itIgnoresAnAlreadyErasedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->store();

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new EraseIdentity($id));
    }
}
