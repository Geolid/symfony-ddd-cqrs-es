<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\Command\ChangePassword\ChangePassword;
use Iam\Authentication\Application\CompromisedPassword\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Doubles\StubCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

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

        $finder = $this->service(PasswordCredentialFinderInterface::class);
        $before = $finder->ofIdentity($builder['identityId']);

        // When
        $this->dispatch(new ChangePassword($builder['identityId'], self::NEW_PASSWORD));

        // Then
        $after = $finder->ofIdentity($builder['identityId']);
        self::assertNotSame($before->passwordHash, $after->passwordHash);
        self::assertNotSame(self::NEW_PASSWORD, $after->passwordHash);
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
