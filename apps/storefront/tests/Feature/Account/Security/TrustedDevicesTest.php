<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Support\Story\AccountTwoFactorStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class TrustedDevicesTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()), trustDevice: true);

        // When
        $browser->visitRoute('storefront_account_security_trusted_devices');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="trusted-device"]');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRevokesOne(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-device-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRefusesRevokeWithInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="revoke-trusted-device-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRevokesAll(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-devices-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked_all');
    }

    #[Test]
    #[WithStory(AccountTwoFactorStory::class)]
    public function itRefusesRevokeAllWithInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountTwoFactorStory::email(), AccountTwoFactorStory::password());

        $browser->completeTwoFactorChallenge($browser->totpCode(AccountTwoFactorStory::totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="revoke-trusted-devices-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_trusted_devices');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
