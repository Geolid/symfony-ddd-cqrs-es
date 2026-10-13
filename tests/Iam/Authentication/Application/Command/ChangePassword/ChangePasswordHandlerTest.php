<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\BreachDatabase\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\BreachDatabase\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Command\ChangePassword\ChangePassword;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidCurrentPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Double\StubCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ChangePasswordHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordStrengthSpecificationInterface $passwordStrength;

    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
        $this->hasher = $this->service(PasswordHasherInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $newPassword = PasswordFactory::new()->create()->value;
        $password = PasswordFactory::new()->create()->value;
        $credential = PasswordCredentialFactory::new()
            ->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        // When
        $this->dispatch(new ChangePassword($credential->identityId, $password, $newPassword));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityOrNull($credential->identityId);
        self::assertNotNull($result);
        self::assertTrue($this->hasher->verify($result->passwordHash, $newPassword));
    }

    #[Test]
    public function itFailsWhenCompromisedPassword(): void
    {
        // Given
        $newPassword = PasswordFactory::new()->create()->value;
        $this->replace(CompromisedPasswordGatewayInterface::class, new StubCompromisedPasswordGateway(compromised: true));
        $password = PasswordFactory::new()->create()->value;

        $credential = PasswordCredentialFactory::new()
            ->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        // Then
        $this->expectException(CompromisedPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($credential->identityId, $password, $newPassword));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->dispatch(
            new ChangePassword(
                Uuid::uuid7()->toString(),
                PasswordFactory::new()->create()->value,
                PasswordFactory::new()->create()->value,
            ),
        );
    }

    #[Test]
    public function itFailsWhenInvalidCurrentPassword(): void
    {
        // Given
        $newPassword = PasswordFactory::new()->create()->value;
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        // Then
        $this->expectException(InvalidCurrentPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($credential->identityId, 'wrong-current-password', $newPassword));
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Given
        $password = PasswordFactory::new()->create()->value;
        $credential = PasswordCredentialFactory::new()
            ->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $this->store($credential);

        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($credential->identityId, $password, 'passwordpassword'));
    }

    #[Test]
    public function itFailsWhenSamePassword(): void
    {
        // Given
        $password = PasswordFactory::new()->create()->value;
        $credential = PasswordCredentialFactory::new()
            ->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $this->store($credential);

        // Then
        $this->expectException(SamePasswordException::class);

        // When
        $this->dispatch(new ChangePassword($credential->identityId, $password, $password));
    }
}
