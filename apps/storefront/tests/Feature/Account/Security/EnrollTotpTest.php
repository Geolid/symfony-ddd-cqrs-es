<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\EnrollTotpForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;
use Symfony\Component\Clock\Clock;

final class EnrollTotpTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="totp-secret"]');
    }

    #[Test]
    public function itEnrolls(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(function (EnrollTotpForm $enroll): void {
            $enroll->fillCode($this->totpCode($enroll->secret()))->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="flash-success"]', 'enroll_totp_flash_enrolled')
            ->assertSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itEnrollsKeepingBackupCodes(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentityWithBackupCodes();
        $browser->signInAs($credential->email, $credential->password);

        $browser->use(static function (TwoFactorForm $twoFactor) use ($credential): void {
            $twoFactor->fillCode($credential->plainBackupCodes[0])->submit();
        });
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(function (EnrollTotpForm $enroll): void {
            $enroll->fillCode($this->totpCode($enroll->secret()))->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="flash-success"]', 'enroll_totp_flash_enrolled_codes_kept')
            ->assertNotSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(static function (EnrollTotpForm $enroll): void {
            $enroll->fillCode('000000')->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="flash-error"]', 'enroll_totp_flash_invalid_code');
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
