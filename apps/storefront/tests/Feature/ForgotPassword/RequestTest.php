<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\RequestForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class RequestTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

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
        $identity = $this->unconfirmedIdentity();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($identity): void {
            $request->fillEmail($identity->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $identity->id]);
    }

    #[Test]
    public function itRedirectsToReset(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $credential = $this->confirmedIdentity();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($credential): void {
            $request->fillEmail($credential->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => $credential->id]);
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
        $browser = $this->activeBrowser();
        $credential = $this->suspendedIdentity();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($credential): void {
            $request->fillEmail($credential->email)->submit();
        });

        // Then
        $browser->assertStatus(409);
    }
}
