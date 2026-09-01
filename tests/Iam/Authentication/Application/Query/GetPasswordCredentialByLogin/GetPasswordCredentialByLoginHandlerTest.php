<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByLogin;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByLogin\GetPasswordCredentialByLogin;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetPasswordCredentialByLoginHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $hasher = $this->service(PasswordHasherInterface::class);
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($hasher);
        $credential = $factory->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetPasswordCredentialByLogin($factory['login']->value));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($factory['identityId'], $result->identityId);
        self::assertSame($factory['login']->value, $result->login);
        self::assertTrue($result->identityAuthenticatable);

        self::assertTrue($hasher->verify($result->passwordHash, $factory['password']->value));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetPasswordCredentialByLogin(PasswordCredentialTestFactory::sample('login')->value));
    }
}
