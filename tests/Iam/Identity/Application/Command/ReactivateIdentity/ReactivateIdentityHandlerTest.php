<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Application\Command\ReactivateIdentity\ReactivateIdentity;
use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class ReactivateIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReactivatesASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $this->dispatch(new ReactivateIdentity($identity->id()->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id()->toString());
        self::assertNotNull($result);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Given
        $id = IdentityId::generate()->toString();

        // Then
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->dispatch(new ReactivateIdentity($id));
    }

    #[Test]
    public function itIgnoresAnIdentityThatIsNotSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $this->dispatch(new ReactivateIdentity($identity->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
