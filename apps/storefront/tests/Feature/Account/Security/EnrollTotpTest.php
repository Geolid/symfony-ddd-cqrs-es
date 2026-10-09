<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Support\Story\ConfirmedAccountStory;
use Iam\Tests\Support\Story\ConfirmedWithBackupCodesAccountStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\EnrollTotpForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class EnrollTotpTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="totp-secret"]');
    }

    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itEnrolls(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

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
    #[WithStory(ConfirmedWithBackupCodesAccountStory::class)]
    public function itEnrollsKeepingBackupCodes(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedWithBackupCodesAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($account->plainBackupCodes()[0]);
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
    #[WithStory(ConfirmedAccountStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

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
