<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\DefinePasswordCredential;

use Iam\Authentication\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Authentication\Application\CompromisedPassword\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Exception\LoginAlreadyTakenException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Doubles\StubCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class DefinePasswordCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $identityId = PasswordCredentialTestFactory::sample('identityId');
        $login = PasswordCredentialTestFactory::sample('login')->value;
        $password = PasswordCredentialTestFactory::sample('password')->value;

        // When
        $this->dispatch(new DefinePasswordCredential($identityId, $login, $password));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofLogin($login);
        self::assertSame($identityId, $result->identityId);
        self::assertTrue($result->identityAuthenticatable);

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
            PasswordCredentialTestFactory::sample('identityId'),
            PasswordCredentialTestFactory::sample('login')->value,
            PasswordCredentialTestFactory::sample('password')->value,
        ));
    }

    #[Test]
    public function itFailsWhenLoginAlreadyTaken(): void
    {
        // Given
        $login = PasswordCredentialTestFactory::sample('login')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(PasswordCredentialUniqueKey::LOGIN),
            $login,
            PasswordCredentialTestFactory::sample('id')->toString(),
        );

        // Then
        $this->expectException(LoginAlreadyTakenException::class);

        // When
        $this->dispatch(new DefinePasswordCredential(
            PasswordCredentialTestFactory::sample('identityId'),
            $login,
            PasswordCredentialTestFactory::sample('password')->value,
        ));
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new DefinePasswordCredential(
            PasswordCredentialTestFactory::sample('identityId'),
            PasswordCredentialTestFactory::sample('login')->value,
            'passwordpassword',
        ));
    }
}
