<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class SuspendIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itSuspendsAnIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $this->dispatch(new SuspendIdentity($identity->id()->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id()->toString());
        self::assertNotNull($result);
        self::assertSame(IdentityStatus::SUSPENDED, $result->status);
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Given
        $id = IdentityId::generate()->toString();

        // Then
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->dispatch(new SuspendIdentity($id));
    }

    #[Test]
    public function itIgnoresAnAlreadySuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $this->dispatch(new SuspendIdentity($identity->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
