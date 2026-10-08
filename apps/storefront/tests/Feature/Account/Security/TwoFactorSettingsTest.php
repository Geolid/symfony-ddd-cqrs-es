<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

final class TwoFactorSettingsTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->withBackupCodes()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()));

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="backup-codes-remaining"]');
    }

    #[Test]
    public function itRegeneratesBackupCodes(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->withBackupCodes()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()));
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->click('[data-testid="regenerate-backup-codes-submit"]');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itRefusesRegenerateBackupCodesWithInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->withBackupCodes()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()));
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="regenerate-backup-codes-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_two_factor_settings')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    public function itUnenrolls(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->withBackupCodes()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()));
        $browser->visitRoute('storefront_account_security_two_factor_settings');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="unenroll-totp-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'two_factor_settings_flash_unenrolled');
    }

    #[Test]
    public function itRefusesUnenrollWithInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->withPassword()->withTotp()->withBackupCodes()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->completeTwoFactorChallenge($browser->totpCode($account->totpSecret()));
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="unenroll-totp-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_two_factor_settings')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_two_factor_settings');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
