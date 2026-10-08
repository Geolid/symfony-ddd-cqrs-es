<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\ConfirmForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\VerificationCodeTrait;

final class ConfirmTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmationRequested()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->verificationCode())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_flash_confirmed');
    }

    #[Test]
    public function itRefusesWhenErased(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->erased()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->fillCode('000000')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'confirm_error_expired');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmationRequested()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->fillCode('000000')->submit();
        });

        // Then
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->assertInvalidCodeError();
        });
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmationRequested()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (ConfirmForm $confirm): void {
                $confirm->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->verificationCode())->submit();
        });

        // Then
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->assertAttemptsExceededError();
        });
    }

    #[Test]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmationRequested()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);
        $this->advanceClock('+2 minutes');

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $this->assertEmailSent(2, $account->email, 'Confirm your email address');
    }

    #[Test]
    public function itResendsWhenAlreadyConfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmationRequested()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->verificationCode())->submit();
        });

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        // When
        $browser->interceptRedirects();
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_resend_flash_already_confirmed');
    }

    #[Test]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmationRequested()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $account->id])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    public function itRefusesResendWhenErased(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->erased()->create();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $account->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'confirm_error_expired');
    }
}
