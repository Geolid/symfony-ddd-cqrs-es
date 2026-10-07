<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStoryTrait;

final class RequestEmailChangeTest extends AbstractStorefrontTestCase
{
    use IdentityStoryTrait;

    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        // When
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="request-email-change-form"]');
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->interceptRedirects();
        $newEmail = IdentityBuilder::sample('email')->value;

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($newEmail): void {
            $form->fillNewEmail($newEmail)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_change_email');
    }

    #[Test]
    public function itRefusesEmailAlreadyInUse(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $otherEmail = IdentityBuilder::sample('email')->value;
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($otherEmail): void {
            $identify->fillEmail($otherEmail)->submit();
        });
        $browser->click('[data-testid="create-account-button"]');
        $browser->use(static function (RegisterForm $register): void {
            $register->fillFullName(IdentityBuilder::sample('fullName')->value)
                ->fillPassword(PasswordCredentialBuilder::sample('password')->value)
                ->submit();
        });

        $credential = $this->identityEligibleForEmailChange();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_request_email_change');

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($otherEmail): void {
            $form->fillNewEmail($otherEmail)->submit();
        });

        // Then
        $browser->use(static function (RequestEmailChangeForm $form): void {
            $form->assertAlreadyInUseError();
        });
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
