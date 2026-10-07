<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\TotpEnabledIdentityStory;
use Symfony\Component\Clock\Clock;

final class TwoFactorSettingsTest extends AbstractStorefrontTestCase
{
    use TotpEnabledIdentityStory;

    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotpAndBackupCodes();
        $browser->signInAs($credential->email, $credential->password);

        $browser->use(function (TwoFactorForm $twoFactor) use ($credential): void {
            $twoFactor->fillCode($this->totpCode($credential->totpSecret))->submit();
        });

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="backup-codes-remaining"]');
    }

    #[Test]
    public function itRegeneratesBackupCodes(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotpAndBackupCodes();
        $browser->signInAs($credential->email, $credential->password);

        $browser->use(function (TwoFactorForm $twoFactor) use ($credential): void {
            $twoFactor->fillCode($this->totpCode($credential->totpSecret))->submit();
        });
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->click('[data-testid="regenerate-backup-codes-submit"]');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itUnenrolls(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithTotpAndBackupCodes();
        $browser->signInAs($credential->email, $credential->password);

        $browser->use(function (TwoFactorForm $twoFactor) use ($credential): void {
            $twoFactor->fillCode($this->totpCode($credential->totpSecret))->submit();
        });
        $browser->visitRoute('storefront_account_security_two_factor_settings');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="unenroll-totp-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'two_factor_settings_flash_unenrolled');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }

    private function totpCode(string $secret): string
    {
        \assert('' !== $secret);

        return TOTP::createFromSecret($secret, Clock::get())->now();
    }
}
