<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\TwoFactorStory;
use Storefront\Tests\Support\Story\TwoFactorUnenrolledStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class TwoFactorChallengeTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(TwoFactorStory::class)]
    public function itShowsTwoFactorChallenge(): void
    {
        // Given
        $persona = TwoFactorStory::persona();
        $browser = $this->activeBrowser();

        // When
        $browser->signInAs($persona->email, $persona->password());

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="two-factor-form"]');
    }

    #[Test]
    #[WithStory(TwoFactorStory::class)]
    public function itCompletesSignIn(): void
    {
        // Given
        $persona = TwoFactorStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->interceptRedirects();

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser, $persona): void {
            $twoFactor->fillCode($browser->totpCode($persona->totpSecret()))->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(TwoFactorStory::class)]
    public function itCompletesSignInWithBackupCode(): void
    {
        // Given
        $persona = TwoFactorStory::persona();
        $backupCode = TwoFactorStory::getRandom('backupCodes');
        \assert(\is_string($backupCode));
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_two_factor_challenge', ['preferProvider' => 'backup_code']);
        $browser->interceptRedirects();

        // When
        $browser->completeTwoFactorChallenge($backupCode);

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(TwoFactorStory::class)]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $persona = TwoFactorStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($persona->totpSecret()), trustDevice: true);
        $browser->visitRoute('_logout_main');
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($persona): void {
            $identify->fillEmail($persona->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($persona): void {
            $verify->fillPassword($persona->password())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(TwoFactorUnenrolledStory::class)]
    public function itSkipsChallengeWithoutTotp(): void
    {
        // Given
        $persona = TwoFactorUnenrolledStory::persona();
        $browser = $this->activeBrowser();

        // When
        $browser->signInAs($persona->email, $persona->password());

        // Then
        $browser->assertSignedIn();
    }

    #[Test]
    #[WithStory(TwoFactorStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $persona = TwoFactorStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

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
    #[WithStory(TwoFactorStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $persona = TwoFactorStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (TwoFactorForm $twoFactor): void {
                $twoFactor->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(static function (TwoFactorForm $twoFactor) use ($browser, $persona): void {
            $twoFactor->fillCode($browser->totpCode($persona->totpSecret()))->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="two-factor-error"]', 'Too many failed login attempts');
    }
}
