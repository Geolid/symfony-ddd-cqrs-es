<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\Command\ChangePassword\ChangePassword;
use Iam\Authentication\Application\CompromisedPassword\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Password\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\StubCompromisedPasswordGateway;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ChangePasswordHandlerTest extends AbstractIntegrationTestCase
{
    private const string NEW_PASSWORD = 'Qm3&nJ8wXv5Tz1p!';

    private PasswordStrengthInterface $passwordStrength;

    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthInterface::class);
        $this->hasher = $this->service(PasswordHasherInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $this->dispatch(new ChangePassword($builder['identityId'], self::NEW_PASSWORD));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentity($builder['identityId']);
        self::assertTrue($this->hasher->verify($result->passwordHash, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenCompromisedPassword(): void
    {
        // Given
        $this->replace(CompromisedPasswordGatewayInterface::class, new StubCompromisedPasswordGateway(compromised: true));

        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential);

        // Then
        $this->expectException(CompromisedPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($builder['identityId'], self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->dispatch(
            new ChangePassword(
                PasswordCredentialBuilder::sample('identityId'),
                PasswordCredentialBuilder::sample('password')->value,
            ),
        );
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Given
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();

        $this->store($credential);

        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($builder['identityId'], 'passwordpassword'));
    }

    #[Test]
    public function itFailsWhenSamePassword(): void
    {
        // Given
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();

        $this->store($credential);

        // Then
        $this->expectException(SamePasswordException::class);

        // When
        $this->dispatch(new ChangePassword($builder['identityId'], $builder['password']->value));
    }
}
