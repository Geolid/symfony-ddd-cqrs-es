<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\ResetForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\VerificationCodeTrait;

final class ResetTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    public function itShowsReset(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        // When
        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="reset-password-form"]');
    }

    #[Test]
    public function itResets(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'reset_flash_reset');
    }

    #[Test]
    public function itRefusesPasswordMismatch(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

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
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->suspended()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

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
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

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
    public function itRefusesSamePassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword(PasswordCredentialBuilder::sample('password')->value)->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertSamePasswordError();
        });
    }

    #[Test]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);
        $this->advanceClock('+4 days');

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->clickResend();
        });

        // Then
        $this->assertEmailSent(2, $account->email, 'Reset your password');
    }

    #[Test]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->passwordResetRequested()->create();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => $account->id])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }
}
