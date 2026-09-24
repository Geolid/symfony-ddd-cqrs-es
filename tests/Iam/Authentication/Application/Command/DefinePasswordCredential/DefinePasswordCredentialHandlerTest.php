<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\DefinePasswordCredential;

use Iam\Authentication\Application\BreachDatabase\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\BreachDatabase\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\StubCompromisedPasswordGateway;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DefinePasswordCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $identityId = PasswordCredentialBuilder::sample('identityId');
        $password = PasswordCredentialBuilder::sample('password')->value;
        $now = Clock::get()->now();

        // When
        $this->dispatch(new DefinePasswordCredential($identityId, $password));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame(PasswordCredentialId::forIdentity($identityId)->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->definedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->changedAt->format(\DateTimeInterface::ATOM),
        );
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
        $this->dispatch(new DefinePasswordCredential(
            PasswordCredentialBuilder::sample('identityId'),
            PasswordCredentialBuilder::sample('password')->value,
        ));
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new DefinePasswordCredential(
            PasswordCredentialBuilder::sample('identityId'),
            'passwordpassword',
        ));
    }
}
