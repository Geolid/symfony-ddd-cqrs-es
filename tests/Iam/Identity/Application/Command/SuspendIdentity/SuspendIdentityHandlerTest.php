<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Status\IdentityStatus;
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
        $identity = IdentityTestFactory::new()->store();

        // When
        $this->dispatch(new SuspendIdentity($identity->id()->toString(), 'Suspected fraudulent activity'));

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
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new SuspendIdentity($id, 'Suspected fraudulent activity'));
    }

    #[Test]
    public function itIgnoresAnAlreadySuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $this->dispatch(new SuspendIdentity($identity->id()->toString(), 'Suspected fraudulent activity'));

        // Then
        self::expectNotToPerformAssertions();
    }
}
