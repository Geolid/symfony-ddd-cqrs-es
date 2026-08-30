<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByLogin;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByLogin\GetPasswordCredentialByLogin;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
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
        $identity = IdentityTestFactory::new()->create();
        $hasher = $this->service(PasswordHasherInterface::class);
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('ada.lovelace')
            ->withPassword('Marmoset-42-Zephyr!')
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($hasher)
            ->create();
        $this->store($identity, $credential);

        // When
        $result = $this->ask(new GetPasswordCredentialByLogin('ada.lovelace'));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame('ada.lovelace', $result->login);
        self::assertTrue($hasher->verify($result->passwordHash, 'Marmoset-42-Zephyr!'));
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
