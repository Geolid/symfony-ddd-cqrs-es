<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use Iam\Tests\Support\Story\AccountConfirmationRequestedStory;
use Iam\Tests\Support\Story\AccountErasedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\ConfirmForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\VerificationCodeTrait;
use Zenstruck\Foundry\Attribute\WithStory;

final class ConfirmTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    #[WithStory(AccountConfirmationRequestedStory::class)]
    public function itConfirms(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->verificationCode())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_flash_confirmed');
    }

    #[Test]
    #[WithStory(AccountErasedStory::class)]
    public function itRefusesWhenErased(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountErasedStory::id()]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->fillCode('000000')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'confirm_error_expired');
    }

    #[Test]
    #[WithStory(AccountConfirmationRequestedStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);

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
    #[WithStory(AccountConfirmationRequestedStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);

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
    #[WithStory(AccountConfirmationRequestedStory::class)]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);
        $this->advanceClock('+2 minutes');

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $this->assertEmailSent(2, AccountConfirmationRequestedStory::email(), 'Confirm your email address');
    }

    #[Test]
    #[WithStory(AccountConfirmationRequestedStory::class)]
    public function itResendsWhenAlreadyConfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->verificationCode())->submit();
        });

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);

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
    #[WithStory(AccountConfirmationRequestedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => AccountConfirmationRequestedStory::id()])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    #[WithStory(AccountErasedStory::class)]
    public function itRefusesResendWhenErased(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => AccountErasedStory::id()]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'confirm_error_expired');
    }
}
