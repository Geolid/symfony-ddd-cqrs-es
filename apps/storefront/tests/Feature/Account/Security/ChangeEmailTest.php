<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangeEmailForm;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class ChangeEmailTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        $newEmail = IdentityBuilder::sample('email')->value;
        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm) use ($newEmail): void {
            $requestEmailChangeForm->fillNewEmail($newEmail)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(function (ChangeEmailForm $form): void {
            $form->fillCode($this->confirmationCode())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_email_flash_changed');

        $browser->followRedirects()
            ->signInAs($newEmail, $credential->password)
            ->assertSignedIn();

        $browser->visitRoute('storefront_account_security_show');
        $browser->assertSeeIn('[data-testid="email-value"]', $newEmail);
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm): void {
            $requestEmailChangeForm->fillNewEmail(IdentityBuilder::sample('email')->value)->submit();
        });

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->fillCode('000000')->submit();
        });

        // Then
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->assertInvalidCodeError();
        });
    }

    #[Test]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        $newEmail = IdentityBuilder::sample('email')->value;
        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm) use ($newEmail): void {
            $requestEmailChangeForm->fillNewEmail($newEmail)->submit();
        });

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->clickResend();
        });

        // Then
        $this->mailer()
            ->assertSentEmailCount(2)
            ->sentEmails()->whereTo($newEmail)->last()
            ->assertSubject('Confirm your new email address');
    }

    #[Test]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm): void {
            $requestEmailChangeForm->fillNewEmail(IdentityBuilder::sample('email')->value)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_change_email')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_failed');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_change_email');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
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
