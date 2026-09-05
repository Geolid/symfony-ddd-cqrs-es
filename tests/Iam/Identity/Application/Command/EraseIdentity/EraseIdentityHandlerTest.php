<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erased()->create();
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
        $this->dispatch(new EraseIdentity(IdentityId::generate()->toString()));
    }
}
