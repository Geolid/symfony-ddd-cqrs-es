<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Browser;

final class VerifyTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsVerify(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPassword();

        // When
        $this->goToVerify($browser, $given->email);

        // Then
        $browser->assertSuccessful()
            ->use(static function (VerifyForm $verify) use ($given): void {
                $verify->assertEmailPrefilled($given->email);
            });
    }

    #[Test]
    public function itRedirectsToIdentifyWhenAccessedDirectly(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/signin/verify');

        // Then
        $browser->assertRedirectedTo('/signin');
    }

    #[Test]
    public function itSignsIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPassword();
        $this->goToVerify($browser, $given->email);
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($given): void {
            $verify->fillPassword($given->password)->submit();
        });

        // Then
        $browser->assertRedirectedTo('/home');
    }

    #[Test]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPassword();
        $this->goToVerify($browser, $given->email);

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword('wrong password')->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify) use ($given): void {
            $verify->assertInvalidCredentialsError()->assertEmailPrefilled($given->email);
        });
    }

    #[Test]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenSuspendedIdentityWithPassword();
        $this->goToVerify($browser, $given->email);

        // When
        $browser->use(static function (VerifyForm $verify) use ($given): void {
            $verify->fillPassword($given->password)->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertSuspendedError();
        });
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPassword();
        $this->goToVerify($browser, $given->email);

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (VerifyForm $verify): void {
                $verify->fillPassword('wrong password')->submit();
            });
        }

        // When
        $browser->use(static function (VerifyForm $verify) use ($given): void {
            $verify->fillPassword($given->password)->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertThrottledError();
        });
    }

    private function goToVerify(Browser $browser, string $email): void
    {
        $browser->visit('/signin');
        $browser->use(static function (IdentifyForm $identify) use ($email): void {
            $identify->fillEmail($email)->submit();
        });
    }

    private function givenConfirmedIdentityWithPassword(): GivenCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create());

        return new GivenCredential($identityBuilder['email']->value, $passwordBuilder['password']->value);
    }

    private function givenSuspendedIdentityWithPassword(): GivenCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed()->suspended();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create());

        return new GivenCredential($identityBuilder['email']->value, $passwordBuilder['password']->value);
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }
}

/**
 * @internal
 */
final readonly class GivenCredential
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
