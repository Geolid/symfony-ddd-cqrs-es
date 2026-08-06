<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityAlreadySuspendedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class SuspendIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itSuspendsAnIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id()->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id()->toString());
        self::assertNotNull($result);
        self::assertSame('suspended', $result->status);
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new SuspendIdentity(IdentityId::generate()->toString()));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsAlreadySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadySuspendedException::class);

        // When
        $this->dispatch(new SuspendIdentity($identity->id()->toString()));
    }
}
