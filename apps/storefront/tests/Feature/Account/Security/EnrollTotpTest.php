<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Feature\Account\Security\Component\EnrollTotpForm;
use Storefront\Tests\Feature\SignIn\Component\TwoFactorForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;
use Zenstruck\Browser;

final class EnrollTotpTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAs($browser);

        // When
        $browser->visit('/account/security/2fa/settings');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="totp-secret"]');
    }

    #[Test]
    public function itEnrolls(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAs($browser);
        $browser->visit('/account/security/2fa/settings');

        // When
        $browser->use(function (EnrollTotpForm $enroll): void {
            $enroll->fillCode($this->totpCode($enroll->secret()))->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="flash-success"]', 'enroll_totp_flash_enrolled')
            ->assertSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itEnrollsKeepingBackupCodes(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAsWithExistingBackupCodes($browser);
        $browser->visit('/account/security/2fa/settings');

        // When
        $browser->use(function (EnrollTotpForm $enroll): void {
            $enroll->fillCode($this->totpCode($enroll->secret()))->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="flash-success"]', 'enroll_totp_flash_enrolled_codes_kept')
            ->assertNotSeeElement('[data-testid="backup-codes"]');
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAs($browser);
        $browser->visit('/account/security/2fa/settings');

        // When
        $browser->use(static function (EnrollTotpForm $enroll): void {
            $enroll->fillCode('000000')->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="flash-error"]', 'enroll_totp_flash_invalid_code');
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

    private function totpCode(string $secret): string
    {
        \assert('' !== $secret);

        return TOTP::createFromSecret($secret, Clock::get())->now();
    }

    private function signInAs(Browser&AuthenticationExtensionInterface $browser): void
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);
    }

    private function signInAsWithExistingBackupCodes(Browser&AuthenticationExtensionInterface $browser): void
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $backupCodeBuilder = BackupCodeCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class));
        $this->store($identity, $passwordBuilder->create(), $backupCodeBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);

        $browser->use(static function (TwoFactorForm $twoFactor) use ($backupCodeBuilder): void {
            $twoFactor->fillCode($backupCodeBuilder['plainBackupCodes'][0])->submit();
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
