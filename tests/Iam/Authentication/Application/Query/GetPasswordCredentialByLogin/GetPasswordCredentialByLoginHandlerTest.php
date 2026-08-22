<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByLogin;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByLogin\GetPasswordCredentialByLogin;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetPasswordCredentialByLoginHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $hasher = $this->service(PasswordHasherInterface::class);
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('ada.lovelace')
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($hasher)
            ->store();

        // When
        $result = $this->ask(new GetPasswordCredentialByLogin('ada.lovelace'));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame('ada.lovelace', $result->login);
        self::assertTrue($hasher->verify($result->passwordHash, 'Xk9$mQ2vLp7&zR4w'));
        self::assertTrue($result->identityAuthenticatable);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetPasswordCredentialByLogin('unknown.login'));
    }
}
