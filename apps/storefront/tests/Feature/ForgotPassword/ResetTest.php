<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\ResetForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class ResetTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsReset(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested();

        // When
        $browser->visit("/forgot-password/{$identityId}/reset");

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="reset-password-form"]');
    }

    #[Test]
    public function itResets(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->resetCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedTo('/signin')
            ->assertSeeIn('[data-testid="flash-success"]', 'reset_flash_reset');
    }

    #[Test]
    public function itRefusesPasswordMismatch(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->resetCode())
                ->fillMismatchedNewPassword('Flamingo-73-Juniper!', 'Different-99-Value!')
                ->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertPasswordMismatchError();
        });
    }

    #[Test]
    public function itRefusesSamePassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->resetCode())->fillNewPassword('Marmoset-42-Zephyr!')->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertSamePasswordError();
        });
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->fillCode('000000')->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertInvalidCodeError();
        });
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (ResetForm $reset): void {
                $reset->fillCode('000000')->fillNewPassword('Flamingo-73-Juniper!')->submit();
            });
        }

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->resetCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->use(static function (ResetForm $reset): void {
            $reset->assertAttemptsExceededError();
        });
    }

    #[Test]
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested(suspended: true);
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(function (ResetForm $reset): void {
            $reset->fillCode($this->resetCode())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }

    #[Test]
    public function itResends(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->clickResend();
        });

        // Then
        $this->mailer()->assertSentEmailCount(2);
    }

    #[Test]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identityId = $this->givenResetRequested();
        $browser->visit("/forgot-password/{$identityId}/reset");

        // When
        $browser->use(static function (ResetForm $reset): void {
            $reset->submitResendWithInvalidToken();
        });

        // Then
        $browser->assertRedirectedTo("/forgot-password/{$identityId}/reset")
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_failed');
    }

    private function givenResetRequested(bool $suspended = false): string
    {
        $identityBuilder = $suspended
            ? IdentityBuilder::new()->confirmed()->suspended()
            : IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $credentialBuilder = $this->passwordCredentialFor($identity)->resetRequested();
        $this->store($identity, $credentialBuilder->create());

        return $identity->id->toString();
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }

    private function resetCode(): string
    {
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        \assert(\is_string($body));

        $matched = preg_match('/(\d{6})/', $body, $matches);
        \assert(1 === $matched);

        return $matches[1];
    }
}
