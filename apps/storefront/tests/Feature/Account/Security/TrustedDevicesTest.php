<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\TrustedDeviceStory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class TrustedDevicesTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(TrustedDeviceStory::class)]
    public function itShows(): void
    {
        // Given
        $persona = TrustedDeviceStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($persona->totpSecret()));

        // When
        $browser->visitRoute('storefront_account_security_trusted_devices');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="trusted-device"]');
    }

    #[Test]
    #[WithStory(TrustedDeviceStory::class)]
    public function itRevokesOne(): void
    {
        // Given
        $persona = TrustedDeviceStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($persona->totpSecret()));
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-device-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked');
    }

    #[Test]
    #[WithStory(TrustedDeviceStory::class)]
    public function itRevokesAll(): void
    {
        // Given
        $persona = TrustedDeviceStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($persona->totpSecret()));
        $browser->visitRoute('storefront_account_security_trusted_devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-devices-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_trusted_devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked_all');
    }

    #[Test]
    #[WithStory(TrustedDeviceStory::class)]
    public function itRefusesRevokeWithInvalidCsrfToken(): void
    {
        // Given
        $persona = TrustedDeviceStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($persona->totpSecret()));
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
    #[WithStory(TrustedDeviceStory::class)]
    public function itRefusesRevokeAllWithInvalidCsrfToken(): void
    {
        // Given
        $persona = TrustedDeviceStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($persona->totpSecret()));
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
