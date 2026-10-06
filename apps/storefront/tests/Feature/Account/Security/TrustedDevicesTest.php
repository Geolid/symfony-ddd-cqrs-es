<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Identity\Domain\Identity;
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

final class TrustedDevicesTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInWithTrustedDevice($browser);

        // When
        $browser->visit('/account/security/2fa/devices');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="trusted-device"]');
    }

    #[Test]
    public function itRevokesOne(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInWithTrustedDevice($browser);
        $browser->visit('/account/security/2fa/devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-device-submit"]');

        // Then
        $browser->assertRedirectedTo('/account/security/2fa/devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked');
    }

    #[Test]
    public function itRevokesAll(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInWithTrustedDevice($browser);
        $browser->visit('/account/security/2fa/devices');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="revoke-trusted-devices-submit"]');

        // Then
        $browser->assertRedirectedTo('/account/security/2fa/devices')
            ->assertSeeIn('[data-testid="flash-success"]', 'trusted_devices_flash_revoked_all');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/account/security/2fa/devices');

        // Then
        $browser->assertRedirectedTo('/signin');
    }

    private function signInWithTrustedDevice(Browser&AuthenticationExtensionInterface $browser): void
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $totpBuilder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);

        $secret = $totpBuilder['secret'];
        \assert('' !== $secret);
        $browser->use(static function (TwoFactorForm $twoFactor) use ($secret): void {
            $twoFactor->fillCode(TOTP::createFromSecret($secret, Clock::get())->now())
                ->checkTrustDevice()
                ->submit();
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
