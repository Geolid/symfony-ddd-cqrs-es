<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Application\Command\ReactivateIdentity\ReactivateIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ReactivateIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReactivatesASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity($identity->id()->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id()->toString());
        self::assertNotNull($result);
        self::assertSame('active', $result->status);
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ReactivateIdentity(IdentityId::generate()->toString()));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsNotSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotSuspendedException::class);

        // When
        $this->dispatch(new ReactivateIdentity($identity->id()->toString()));
    }
}
