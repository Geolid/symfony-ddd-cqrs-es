<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\TotpEnabledIdentityStoryTrait;

final class TwoFactorChallengeTest extends AbstractStorefrontTestCase
{
    use TotpEnabledIdentityStoryTrait;

    #[Test]
    public function itShowsTwoFactorChallenge(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotp();

        // When
        $browser->signInAs($credential->email, $credential->password);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }

    #[Test]
    public function itCompletesSignIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotp();
        $browser->signInAs($credential->email, $credential->password);

        $browser->interceptRedirects();

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser, $credential): void {
            $twoFactor->fillCode($browser->totpCode($credential->totpSecret))->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotp();
        $browser->signInAs($credential->email, $credential->password);

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
        $credential = $this->confirmedIdentityWithTotp();
        $browser->signInAs($credential->email, $credential->password);

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (TwoFactorForm $twoFactor): void {
                $twoFactor->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser, $credential): void {
            $twoFactor->fillCode($browser->totpCode($credential->totpSecret))->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="two-factor-error"]', 'Too many failed login attempts');
    }

    #[Test]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotp();
        $browser->signInAs($credential->email, $credential->password);

        $browser->completeTwoFactorChallenge($browser->totpCode($credential->totpSecret), trustDevice: true);
        $browser->visitRoute('_logout_main');
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($credential): void {
            $identify->fillEmail($credential->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($credential): void {
            $verify->fillPassword($credential->password)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    public function itRedemandsCodeWhenDeviceNotTrusted(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotp();
        $browser->signInAs($credential->email, $credential->password);

        $browser->completeTwoFactorChallenge($browser->totpCode($credential->totpSecret));
        $browser->visitRoute('_logout_main');

        // When
        $browser->signInAs($credential->email, $credential->password);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }
}
