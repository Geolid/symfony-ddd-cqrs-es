<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\EnrollTotpForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\TwoFactorUnenrolledStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class EnrollTotpTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itShows(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="totp-secret"]');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itEnrolls(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(static function (EnrollTotpForm $enroll) use ($browser): void {
            $enroll->fillCode($browser->totpCode($enroll->secret()))->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="flash-success"]', 'enroll_totp_flash_enrolled')
            ->assertSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    #[WithStory(TwoFactorUnenrolledStory::class)]
    public function itEnrollsKeepingBackupCodes(): void
    {
        // Given
        $persona = TwoFactorUnenrolledStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(static function (EnrollTotpForm $enroll) use ($browser): void {
            $enroll->fillCode($browser->totpCode($enroll->secret()))->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="flash-success"]', 'enroll_totp_flash_enrolled_codes_kept')
            ->assertNotSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

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
}
