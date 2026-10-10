<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Support\Story\AccountConfirmedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangeEmailForm;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\VerificationCodeTrait;
use Zenstruck\Foundry\Attribute\WithStory;

final class ChangeEmailTest extends AbstractStorefrontTestCase
{
    use VerificationCodeTrait;

    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $newEmail = EmailFactory::new()->create()->value;
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
            ->signInAs($newEmail, AccountConfirmedStory::password())
            ->assertSignedIn();

        $browser->visitRoute('storefront_account_security_show');
        $browser->assertSeeIn('[data-testid="email-value"]', $newEmail);
    }

    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm): void {
            $requestEmailChangeForm->fillNewEmail(EmailFactory::new()->create()->value)->submit();
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
    #[WithStory(AccountConfirmedStory::class)]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $newEmail = EmailFactory::new()->create()->value;
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
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->use(static function (RequestEmailChangeForm $requestEmailChangeForm): void {
            $requestEmailChangeForm->fillNewEmail(EmailFactory::new()->create()->value)->submit();
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
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesResendWhenEmailAlreadyInUse(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $targetEmail = EmailFactory::new()->create()->value;
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
            $register->fillFullName(FullNameFactory::new()->create()->value)
                ->fillPassword(PasswordFactory::new()->create()->value)
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
