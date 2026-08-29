<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Application\Command\SuspendIdentity\SuspendIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class SuspendIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itSuspends(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), 'Suspected fraudulent activity'));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(IdentityStatus::SUSPENDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), 'Suspected fraudulent activity'));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = IdentityId::generate()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new SuspendIdentity($id, 'Suspected fraudulent activity'));
    }

    #[Test]
    public function itFailsWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new SuspendIdentity($identity->id->toString(), 'Suspected fraudulent activity'));
    }
}
