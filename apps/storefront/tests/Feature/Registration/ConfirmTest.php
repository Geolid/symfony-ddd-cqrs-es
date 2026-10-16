<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\ConfirmForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\ErasedStory;
use Storefront\Tests\Support\Story\PendingStory;
use Storefront\Tests\Support\VerificationCodeTrait;
use Zenstruck\Foundry\Attribute\WithStory;

final class ConfirmTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    #[WithStory(PendingStory::class)]
    public function itConfirms(): void
    {
        // Given
        $persona = PendingStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->verificationCode())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_flash_confirmed');
    }

    #[Test]
    #[WithStory(ErasedStory::class)]
    public function itRefusesWhenErased(): void
    {
        // Given
        $persona = ErasedStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->fillCode('000000')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'confirm_error_expired');
    }

    #[Test]
    #[WithStory(PendingStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $persona = PendingStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);

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
    #[WithStory(PendingStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $persona = PendingStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);

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
    #[WithStory(PendingStory::class)]
    public function itResends(): void
    {
        // Given
        $persona = PendingStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);
        $sent = $this->sentEmailCount($persona->email, 'Confirm your email address');

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $this->assertEmailSent($sent + 1, $persona->email, 'Confirm your email address');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itResendsWhenAlreadyConfirmed(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_resend_flash_already_confirmed');
    }

    #[Test]
    #[WithStory(PendingStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $persona = PendingStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $persona->id])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    #[WithStory(ErasedStory::class)]
    public function itRefusesResendWhenErased(): void
    {
        // Given
        $persona = ErasedStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $persona->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'confirm_error_expired');
    }
}
