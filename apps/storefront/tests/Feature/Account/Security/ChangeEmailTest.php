<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangeEmailForm;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\VerificationCodeTrait;

final class ChangeEmailTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $newEmail = IdentityFactory::sample('email')->value;
        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm) use ($newEmail): void {
            $requestEmailChangeForm->fillNewEmail($newEmail)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(function (ChangeEmailForm $form): void {
            $form->fillCode($this->verificationCode())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_email_flash_changed');

        $browser->followRedirects()
            ->signInAs($newEmail, $account->password())
            ->assertSignedIn();

        $browser->visitRoute('storefront_account_security_show');
        $browser->assertSeeIn('[data-testid="email-value"]', $newEmail);
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm): void {
            $requestEmailChangeForm->fillNewEmail(IdentityFactory::sample('email')->value)->submit();
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
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $newEmail = IdentityFactory::sample('email')->value;
        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm) use ($newEmail): void {
            $requestEmailChangeForm->fillNewEmail($newEmail)->submit();
        });
        $this->advanceClock('+2 minutes');

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->clickResend();
        });

        // Then
        $this->assertEmailSent(2, $newEmail, 'Confirm your new email address');
    }

    #[Test]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm): void {
            $requestEmailChangeForm->fillNewEmail(IdentityFactory::sample('email')->value)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_change_email')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    public function itRefusesResendWhenEmailAlreadyInUse(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $targetEmail = IdentityFactory::sample('email')->value;
        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm) use ($targetEmail): void {
            $requestEmailChangeForm->fillNewEmail($targetEmail)->submit();
        });

        $otherBrowser = $this->activeBrowser();
        $otherBrowser->visitRoute('storefront_signin_identify');
        $otherBrowser->use(static function (IdentifyForm $identify) use ($targetEmail): void {
            $identify->fillEmail($targetEmail)->submit();
        });
        $otherBrowser->click('[data-testid="create-account-button"]');
        $otherBrowser->use(static function (RegisterForm $register): void {
            $register->fillFullName(IdentityFactory::sample('fullName')->value)
                ->fillPassword(PasswordCredentialBuilder::sample('password')->value)
                ->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->clickResend();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_request_email_change')
            ->assertSeeIn('[data-testid="flash-error"]', 'change_email_error_already_in_use');
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
}
