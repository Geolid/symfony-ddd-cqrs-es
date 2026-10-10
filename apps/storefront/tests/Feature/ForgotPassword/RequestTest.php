<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Support\Story\AccountConfirmedStory;
use Iam\Tests\Support\Story\AccountRegisteredStory;
use Iam\Tests\Support\Story\AccountSuspendedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\RequestForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class RequestTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsRequest(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_forgot_password_request');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="forgot-password-request-form"]');
    }

    #[Test]
    #[WithStory(AccountRegisteredStory::class)]
    public function itRedirectsToConfirmWhenUnconfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(AccountRegisteredStory::email())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => AccountRegisteredStory::id()]);
    }

    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itRedirectsToReset(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(AccountConfirmedStory::email())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => AccountConfirmedStory::id()]);
        $this->assertEmailSent(1, AccountConfirmedStory::email(), 'Reset your password');
    }

    #[Test]
    public function itRefusesUnknownEmail(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(EmailFactory::new()->create()->value)->submit();
        });

        // Then
        $browser->use(static function (RequestForm $request): void {
            $request->assertEmailNotFoundError();
        });
    }

    #[Test]
    #[WithStory(AccountSuspendedStory::class)]
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(AccountSuspendedStory::email())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }
}
