<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\ConfirmForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class ConfirmTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identity = $this->confirmationRequested();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->confirmationCode())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_flash_confirmed');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identity = $this->confirmationRequested();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);

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
        $identity = $this->confirmationRequested();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (ConfirmForm $confirm): void {
                $confirm->fillCode('000000')->submit();
            });
        }

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->confirmationCode())->submit();
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
        $identity = $this->confirmationRequested();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $this->mailer()
            ->assertSentEmailCount(2)
            ->sentEmails()->whereTo($identity->email)->last()
            ->assertSubject('Confirm your email address');
    }

    #[Test]
    public function itResendsWhenAlreadyConfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identity = $this->confirmationRequested();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->confirmationCode())->submit();
        });

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);

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
        $identity = $this->confirmationRequested();

        $browser->visitRoute('storefront_registration_confirm', ['identityId' => $identity->id]);

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $identity->id])
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_failed');
    }

    private function confirmationCode(): string
    {
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        \assert(\is_string($body));

        $matched = preg_match('/(\d{6})/', $body, $matches);
        \assert(1 === $matched);

        return $matches[1];
    }
}
