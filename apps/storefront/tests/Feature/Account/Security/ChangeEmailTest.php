<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Feature\Account\Security\Component\ChangeEmailForm;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;
use Zenstruck\Browser;

final class ChangeEmailTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        [$newEmail, $password] = $this->goToChangeEmail($browser);
        $browser->interceptRedirects();

        // When
        $browser->use(function (ChangeEmailForm $form): void {
            $form->fillCode($this->confirmationCode())->submit();
        });

        // Then — the credential change signs the session out; sign back in to confirm it took effect
        $browser->assertRedirectedTo('/signin')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_email_flash_changed');
        $browser->followRedirects()
            ->signInAs($newEmail, $password)
            ->assertSuccessful();
        $browser->visit('/account/security');
        $browser->assertSeeIn('[data-testid="email-value"]', $newEmail);
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->goToChangeEmail($browser);

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->fillCode('000000')->submit();
        });

        // Then
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->assertInvalidCodeError();
        });
    }

    #[Test]
    public function itResends(): void
    {
        // Given
        $browser = $this->playwrightBrowser();
        $this->goToChangeEmail($browser);

        // When
        $browser->use(static function (ChangeEmailForm $form): void {
            $form->clickResend();
        });

        // Then
        $this->mailer()->assertSentEmailCount(2);
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/account/security/email/change/confirm');

        // Then
        $browser->assertRedirectedTo('/signin');
    }

    /**
     * @return array{0: string, 1: string} new email, current password
     */
    private function goToChangeEmail(Browser&AuthenticationExtensionInterface $browser): array
    {
        $identityBuilder = IdentityBuilder::new()
            ->withRegisteredAt(Clock::get()->now()->modify('-1 hour'))
            ->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $this->store($identity, $passwordBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);

        $newEmail = IdentityBuilder::sample('email')->value;
        $browser->visit('/account/security/email/change');
        $browser->use(static function (RequestEmailChangeForm $form) use ($newEmail): void {
            $form->fillNewEmail($newEmail)->submit();
        });

        return [$newEmail, $passwordBuilder['password']->value];
    }

    private function confirmationCode(): string
    {
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        \assert(\is_string($body));

        $matched = preg_match('/(\d{6})/', $body, $matches);
        \assert(1 === $matched);

        return $matches[1];
    }
}
