<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\DefinePasswordCredential;

use Iam\Authentication\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Exception\LoginAlreadyTakenException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\PasswordCredential\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Doubles\StubCompromisedPasswordGateway;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class DefinePasswordCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id->toString(), 'ada.lovelace', 'Xk9$mQ2vLp7&zR4w'));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofLogin('ada.lovelace');
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertTrue($result->identityAuthenticatable);
    }

    #[Test]
    public function itFailsWhenLoginAlreadyTaken(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'ada.lovelace', Uuid::uuid7()->toString());

        // Then
        $this->expectException(LoginAlreadyTakenException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id->toString(), 'ada.lovelace', 'Xk9$mQ2vLp7&zR4w'));
    }

    #[Test]
    public function itFailsWhenCompromisedPassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        self::getContainer()->set(CompromisedPasswordGatewayInterface::class, new StubCompromisedPasswordGateway(compromised: true));

        // Then
        $this->expectException(CompromisedPasswordException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id->toString(), 'ada.lovelace', 'Xk9$mQ2vLp7&zR4w'));
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id->toString(), 'ada.lovelace', 'aaaaaaaaaaaa'));
    }
}
