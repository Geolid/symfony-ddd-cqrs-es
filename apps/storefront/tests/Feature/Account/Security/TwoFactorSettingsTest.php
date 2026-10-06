<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;
use Zenstruck\Browser;

final class TwoFactorSettingsTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInWithTotp($browser);

        // When
        $browser->visit('/account/security/2fa/settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="backup-codes-remaining"]');
    }

    #[Test]
    public function itRegeneratesBackupCodes(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInWithTotp($browser);
        $browser->visit('/account/security/2fa/settings');

        // When
        $browser->click('[data-testid="regenerate-backup-codes-submit"]');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itUnenrolls(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInWithTotp($browser);
        $browser->visit('/account/security/2fa/settings');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="unenroll-totp-submit"]');

        // Then
        $browser->assertRedirectedTo('/account/security')
            ->assertSeeIn('[data-testid="flash-success"]', 'two_factor_settings_flash_unenrolled');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/account/security/2fa/settings');

        // Then
        $browser->assertRedirectedTo('/signin');
    }

    private function signInWithTotp(Browser&AuthenticationExtensionInterface $browser): void
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $totpBuilder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
        $backupCodeBuilder = BackupCodeCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class));
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create(), $backupCodeBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);

        $secret = $totpBuilder['secret'];
        \assert('' !== $secret);
        $browser->use(static function (TwoFactorForm $twoFactor) use ($secret): void {
            $twoFactor->fillCode(TOTP::createFromSecret($secret, Clock::get())->now())->submit();
        });
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }
}
