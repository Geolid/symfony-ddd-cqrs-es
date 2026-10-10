<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Support\Story\AccountTwoFactorStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class TwoFactorChallengeTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itShowsTwoFactorChallenge(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itCompletesSignIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->interceptRedirects();

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser): void {
            $twoFactor->fillCode($browser->totpCode(AccountTwoFactorStory::totpSecret()))->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

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
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (TwoFactorForm $twoFactor): void {
                $twoFactor->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser): void {
            $twoFactor->fillCode($browser->totpCode(AccountTwoFactorStory::totpSecret()))->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="two-factor-error"]', 'Too many failed login attempts');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()), trustDevice: true);
        $browser->visitRoute('_logout_main');
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(AccountTwoFactorStory::email())->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword(AccountTwoFactorStory::password())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRedemandsCodeWhenDeviceNotTrusted(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()));
        $browser->visitRoute('_logout_main');

        // When
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }
}
