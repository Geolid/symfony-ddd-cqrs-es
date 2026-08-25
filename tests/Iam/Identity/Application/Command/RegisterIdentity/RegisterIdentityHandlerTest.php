<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\ValueObject\IdentityId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RegisterIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = IdentityId::generate()->toString();

        // When
        $this->dispatch(new RegisterIdentity($id));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($id);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
    }
}
