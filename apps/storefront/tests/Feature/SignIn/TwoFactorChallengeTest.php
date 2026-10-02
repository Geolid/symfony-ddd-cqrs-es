<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;

final class TwoFactorChallengeTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTwoFactorChallenge(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();

        // When
        $browser->signInAs($given->email, $given->password);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }

    #[Test]
    public function itCompletesSignIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();
        $browser->signInAs($given->email, $given->password);
        $browser->interceptRedirects();

        // When
        $browser->use(function (TwoFactorForm $twoFactor) use ($given): void {
            $twoFactor->fillCode($this->totpCode($given->totpSecret))->submit();
        });

        // Then
        $browser->assertRedirectedTo('/home');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();
        $browser->signInAs($given->email, $given->password);

        // When
        $browser->use(static function (TwoFactorForm $twoFactor): void {
            $twoFactor->fillCode('000000')->submit();
        });

        // Then
        $browser->use(static function (TwoFactorForm $twoFactor): void {
            $twoFactor->assertInvalidCodeError();
        });
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();
        $browser->signInAs($given->email, $given->password);

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (TwoFactorForm $twoFactor): void {
                $twoFactor->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(function (TwoFactorForm $twoFactor) use ($given): void {
            $twoFactor->fillCode($this->totpCode($given->totpSecret))->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="two-factor-error"]', 'Too many failed login attempts');
    }

    #[Test]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();
        $browser->signInAs($given->email, $given->password);
        $browser->use(function (TwoFactorForm $twoFactor) use ($given): void {
            $twoFactor->fillCode($this->totpCode($given->totpSecret))->checkTrustDevice()->submit();
        });
        $browser->visit('/logout');
        $browser->visit('/signin');
        $browser->use(static function (IdentifyForm $identify) use ($given): void {
            $identify->fillEmail($given->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($given): void {
            $verify->fillPassword($given->password)->submit();
        });

        // Then
        $browser->assertRedirectedTo('/home');
    }

    #[Test]
    public function itRedemandsCodeWhenDeviceNotTrusted(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();
        $browser->signInAs($given->email, $given->password);
        $browser->use(function (TwoFactorForm $twoFactor) use ($given): void {
            $twoFactor->fillCode($this->totpCode($given->totpSecret))->submit();
        });
        $browser->visit('/logout');

        // When
        $browser->signInAs($given->email, $given->password);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }

    private function totpCode(string $secret): string
    {
        \assert('' !== $secret);

        return TOTP::createFromSecret($secret, Clock::get())->now();
    }

    private function givenConfirmedIdentityWithPasswordAndTotp(): GivenTotpCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $totpBuilder = $this->totpCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create());

        return new GivenTotpCredential(
            $identityBuilder['email']->value,
            $passwordBuilder['password']->value,
            $totpBuilder['secret'],
        );
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }

    private function totpCredentialFor(Identity $identity): TotpCredentialBuilder
    {
        return TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
    }
}

/**
 * @internal
 */
final readonly class GivenTotpCredential
{
    public function __construct(
        public string $email,
        public string $password,
        public string $totpSecret,
    ) {
    }
}
