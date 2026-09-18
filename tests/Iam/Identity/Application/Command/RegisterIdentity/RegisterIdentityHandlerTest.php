<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Application\Command\RegisterIdentity\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RegisterIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $fullName = IdentityBuilder::sample('fullName')->value;
        $email = IdentityBuilder::sample('email')->value;
        $now = Clock::get()->now();

        // When
        $this->dispatch(new RegisterIdentity($id, $fullName, $email));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($fullName, $result->fullName);
        self::assertSame($email, $result->email);
        self::assertSame(IdentityVerificationStatus::PENDING, $result->verificationStatus);
        self::assertNull($result->reason);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->suspendedAt);
        self::assertNull($result->reactivatedAt);
    }

    #[Test]
    public function itFailsWhenEmailAlreadyInUse(): void
    {
        // Given
        $email = IdentityBuilder::sample('email')->value;
        $this->dispatch(new RegisterIdentity(
            Uuid::uuid7()->toString(),
            IdentityBuilder::sample('fullName')->value,
            $email,
        ));

        // Then
        $this->expectException(IdentityEmailAlreadyInUseException::class);

        // When
        $this->dispatch(new RegisterIdentity(
            Uuid::uuid7()->toString(),
            IdentityBuilder::sample('fullName')->value,
            $email,
        ));
    }
}
