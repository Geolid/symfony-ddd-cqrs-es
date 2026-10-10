<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\DefinePassword;

use Iam\Authentication\Application\BreachDatabase\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\BreachDatabase\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Command\DefinePassword\DefinePassword;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Double\StubCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DefinePasswordHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $password = PasswordFactory::new()->create()->value;
        $now = Clock::get()->now();

        // When
        $this->dispatch(new DefinePassword($identityId, $password));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame(PasswordCredentialId::forIdentity($identityId)->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSameDate($now, $result->definedAt);
        self::assertSameDate($now, $result->changedAt);
        self::assertNotSame($password, $result->passwordHash);
    }

    #[Test]
    public function itFailsWhenCompromisedPassword(): void
    {
        // Given
        $this->replace(CompromisedPasswordGatewayInterface::class, new StubCompromisedPasswordGateway(compromised: true));

        // Then
        $this->expectException(CompromisedPasswordException::class);

        // When
        $this->dispatch(new DefinePassword(
            Uuid::uuid7()->toString(),
            PasswordFactory::new()->create()->value,
        ));
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new DefinePassword(
            Uuid::uuid7()->toString(),
            'passwordpassword',
        ));
    }
}
