<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

final class TrustedDevicesTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()), trustDevice: true);

        // When
        $browser->visitRoute('storefront_account_security_trusted_devices');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="trusted-device"]');
    }

    #[Test]
    public function itRevokesOne(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-device-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked');
    }

    #[Test]
    public function itRefusesRevokeWithInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');

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
    public function itRevokesAll(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-devices-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked_all');
    }

    #[Test]
    public function itRefusesRevokeAllWithInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()), trustDevice: true);
        $browser->visitRoute('storefront_account_security_trusted_devices');

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
