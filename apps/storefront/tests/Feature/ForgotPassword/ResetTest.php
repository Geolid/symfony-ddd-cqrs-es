<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\ResetForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ResetRequestedStory;
use Storefront\Tests\Support\Story\SuspendedResetRequestedStory;
use Storefront\Tests\Support\VerificationCodeTrait;
use Zenstruck\Foundry\Attribute\WithStory;

final class ResetTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    #[WithStory(ResetRequestedStory::class)]
    public function itShowsReset(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="reset-password-form"]');
    }

    #[Test]
    #[WithStory(ResetRequestedStory::class)]
    public function itResets(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'reset_flash_reset');
    }

    #[Test]
    #[WithStory(ResetRequestedStory::class)]
    public function itRefusesPasswordMismatch(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

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
    #[WithStory(SuspendedResetRequestedStory::class)]
    public function itRefusesWhenSuspended(): void
    {
        // Given
        $persona = SuspendedResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }

    #[Test]
    #[WithStory(ResetRequestedStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

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
    #[WithStory(ResetRequestedStory::class)]
    public function itRefusesWhenAttemptsExceeded(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

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
    #[WithStory(ResetRequestedStory::class)]
    public function itRefusesSamePassword(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

        // When
        $browser->use(function (ResetForm $reset) use ($persona): void {
            $reset->fillCode($this->verificationCode())->fillNewPassword($persona->password())->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertSamePasswordError();
        });
    }

    #[Test]
    #[WithStory(ResetRequestedStory::class)]
    public function itResends(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);
        $expectedCount = $this->sentEmailCount($persona->email, 'Reset your password') + 1;

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->clickResend();
        });

        // Then
        $this->assertEmailSent($expectedCount, $persona->email, 'Reset your password');
    }

    #[Test]
    #[WithStory(ResetRequestedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $persona = ResetRequestedStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => $persona->id])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }
}
