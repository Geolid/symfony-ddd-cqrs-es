<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Support\Story\TwoFactorAccountStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class TwoFactorChallengeTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(TwoFactorAccountStory::class)]
    public function itShowsTwoFactorChallenge(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = TwoFactorAccountStory::account();

        // When
        $browser->signInAs($account->email, $account->password());

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }

    #[Test]
    #[WithStory(TwoFactorAccountStory::class)]
    public function itCompletesSignIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = TwoFactorAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        $browser->interceptRedirects();

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser, $account): void {
            $twoFactor->fillCode($browser->totpCode($account->totpSecret()))->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(TwoFactorAccountStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = TwoFactorAccountStory::account();
        $browser->signInAs($account->email, $account->password());

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
    #[WithStory(TwoFactorAccountStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = TwoFactorAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (TwoFactorForm $twoFactor): void {
                $twoFactor->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser, $account): void {
            $twoFactor->fillCode($browser->totpCode($account->totpSecret()))->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="two-factor-error"]', 'Too many failed login attempts');
    }

    #[Test]
    #[WithStory(TwoFactorAccountStory::class)]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = TwoFactorAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()), trustDevice: true);
        $browser->visitRoute('_logout_main');
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($account): void {
            $identify->fillEmail($account->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($account): void {
            $verify->fillPassword($account->password())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(TwoFactorAccountStory::class)]
    public function itRedemandsCodeWhenDeviceNotTrusted(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = TwoFactorAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()));
        $browser->visitRoute('_logout_main');

        // When
        $browser->signInAs($account->email, $account->password());

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }
}
