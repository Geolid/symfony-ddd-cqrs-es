<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\ConfirmForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class ConfirmTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itConfirms(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identityId = $this->givenPendingConfirmation();
        $browser->visit("/register/{$identityId}/confirm");

        // When
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->confirmationCode())->submit();
        });

        // Then
        $browser->assertRedirectedTo('/signin')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_flash_confirmed');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenPendingConfirmation();
        $browser->visit("/register/{$identityId}/confirm");

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
        $identityId = $this->givenPendingConfirmation();
        $browser->visit("/register/{$identityId}/confirm");

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
        $identityId = $this->givenPendingConfirmation();
        $browser->visit("/register/{$identityId}/confirm");

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $this->mailer()->assertSentEmailCount(2);
    }

    #[Test]
    public function itResendsWhenAlreadyConfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenPendingConfirmation();
        $browser->visit("/register/{$identityId}/confirm");
        $browser->use(function (ConfirmForm $confirm): void {
            $confirm->fillCode($this->confirmationCode())->submit();
        });
        $browser->visit("/register/{$identityId}/confirm");

        // When
        $browser->interceptRedirects();
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->clickResend();
        });

        // Then
        $browser->assertRedirectedTo('/signin')
            ->assertSeeIn('[data-testid="flash-success"]', 'confirm_resend_flash_already_confirmed');
    }

    #[Test]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identityId = $this->givenPendingConfirmation();
        $browser->visit("/register/{$identityId}/confirm");

        // When
        $browser->use(static function (ConfirmForm $confirm): void {
            $confirm->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedTo("/register/{$identityId}/confirm")
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_failed');
    }

    private function givenPendingConfirmation(): string
    {
        $identityBuilder = IdentityBuilder::new()->confirmationRequested();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return $identity->id->toString();
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
