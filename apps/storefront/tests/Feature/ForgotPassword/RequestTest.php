<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\RequestForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

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
    public function itRedirectsToConfirmWhenUnconfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->create();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($account): void {
            $request->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $account->id]);
    }

    #[Test]
    public function itRedirectsToReset(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->create();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($account): void {
            $request->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);
        $this->assertEmailSent(1, $account->email, 'Reset your password');
    }

    #[Test]
    public function itRefusesUnknownEmail(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(IdentityBuilder::sample('email')->value)->submit();
        });

        // Then
        $browser->use(static function (RequestForm $request): void {
            $request->assertEmailNotFoundError();
        });
    }

    #[Test]
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->suspended()->withPassword()->create();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($account): void {
            $request->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }
}
