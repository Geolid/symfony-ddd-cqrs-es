<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ResetPassword;

use Iam\Authentication\Application\BreachDatabase\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\BreachDatabase\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Command\ResetPassword\ResetPassword;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialVerificationCodePurpose;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\StubCompromisedPasswordGateway;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ResetPasswordHandlerTest extends AbstractIntegrationTestCase
{
    private const string NEW_PASSWORD = 'Qm3&nJ8wXv5Tz1p!';

    private PasswordStrengthSpecificationInterface $passwordStrength;

    private PasswordHasherInterface $hasher;

    private NativeCodeChallenger $codeChallenger;

    private PasswordCredentialFinderInterface $passwordCredentialFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->codeChallenger = $this->service(NativeCodeChallenger::class);
        $this->passwordCredentialFinder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($identity, $credential);

        $code = $this->codeChallenger->issue(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString()), Clock::get()->now());

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));

        // Then
        $result = $this->passwordCredentialFinder->ofIdentityOrNull($identity->id->toString());
        self::assertNotNull($result);
        self::assertTrue($this->hasher->verify($result->passwordHash, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenIdentityNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identityId), Clock::get()->now());

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->dispatch(new ResetPassword($identityId, $code, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        $code = $this->codeChallenger->issue(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenCompromisedPassword(): void
    {
        // Given
        $this->replace(CompromisedPasswordGatewayInterface::class, new StubCompromisedPasswordGateway(compromised: true));

        $identity = IdentityBuilder::new()->confirmed()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($identity, $credential);

        $code = $this->codeChallenger->issue(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(CompromisedPasswordException::class);

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenCredentialMissing(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $this->store($identity);

        $code = $this->codeChallenger->issue(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenCodeInvalid(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($identity, $credential);

        $this->codeChallenger->issue(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(InvalidPasswordResetCodeException::class);

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), '000000', self::NEW_PASSWORD));
    }
}
