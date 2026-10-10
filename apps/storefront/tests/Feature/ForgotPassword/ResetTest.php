<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Support\Story\AccountPasswordResetRequestedStory;
use Iam\Tests\Support\Story\AccountSuspendedPasswordResetRequestedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\ResetForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\VerificationCodeTrait;
use Zenstruck\Foundry\Attribute\WithStory;

final class ResetTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itShowsReset(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="reset-password-form"]');
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itResets(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'reset_flash_reset');
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itRefusesPasswordMismatch(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())
                ->fillMismatchedNewPassword('Flamingo-73-Juniper!', 'Different-99-Value!')
                ->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertPasswordMismatchError();
        });
    }

    #[Test]
    #[WithStory(AccountSuspendedPasswordResetRequestedStory::class)]
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountSuspendedPasswordResetRequestedStory::id()]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->fillCode('000000')->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertInvalidCodeError();
        });
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (ResetForm $reset): void {
                $reset->fillCode('000000')->fillNewPassword('Flamingo-73-Juniper!')->submit();
            });
        }

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertAttemptsExceededError();
        });
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itRefusesSamePassword(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword(AccountPasswordResetRequestedStory::password())->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertSamePasswordError();
        });
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);
        $this->advanceClock('+4 days');

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->clickResend();
        });

        // Then
        $this->assertEmailSent(2, AccountPasswordResetRequestedStory::email(), 'Reset your password');
    }

    #[Test]
    #[WithStory(AccountPasswordResetRequestedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()]);

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => AccountPasswordResetRequestedStory::id()])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }
}
