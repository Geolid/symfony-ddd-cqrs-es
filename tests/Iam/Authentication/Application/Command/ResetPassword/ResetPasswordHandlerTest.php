<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ResetPassword;

use Iam\Authentication\Application\AuthenticationVerificationCodePurpose;
use Iam\Authentication\Application\Command\ResetPassword\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Application\Command\ResetPassword\ResetPassword;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\VerificationCode\VerificationCode;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ResetPasswordHandlerTest extends AbstractIntegrationTestCase
{
    private const string NEW_PASSWORD = 'Qm3&nJ8wXv5Tz1p!';

    private PasswordStrengthSpecificationInterface $passwordStrength;

    private PasswordHasherInterface $hasher;

    private VerificationCode $verificationCode;

    private PasswordCredentialFinderInterface $passwordCredentialFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->verificationCode = $this->service(VerificationCode::class);
        $this->passwordCredentialFinder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($identity, $credential);

        $code = $this->verificationCode->issue(AuthenticationVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString(), Clock::get()->now());

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));

        // Then
        $result = $this->passwordCredentialFinder->ofIdentity($identity->id->toString());
        self::assertTrue($this->hasher->verify($result->passwordHash, self::NEW_PASSWORD));
    }

    #[Test]
    public function itDefinesWhenCredentialMissing(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $this->store($identity);

        $code = $this->verificationCode->issue(AuthenticationVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString(), Clock::get()->now());

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));

        // Then
        $result = $this->passwordCredentialFinder->ofIdentity($identity->id->toString());
        self::assertTrue($this->hasher->verify($result->passwordHash, self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenCodeInvalid(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $this->store($identity);

        $this->verificationCode->issue(AuthenticationVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString(), Clock::get()->now());

        // Then
        $this->expectException(InvalidPasswordResetCodeException::class);

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), '000000', self::NEW_PASSWORD));
    }

    #[Test]
    public function itFailsWhenIdentityNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $code = $this->verificationCode->issue(AuthenticationVerificationCodePurpose::PASSWORD_RESET, $identityId, Clock::get()->now());

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

        $code = $this->verificationCode->issue(AuthenticationVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString(), Clock::get()->now());

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->dispatch(new ResetPassword($identity->id->toString(), $code, self::NEW_PASSWORD));
    }
}
