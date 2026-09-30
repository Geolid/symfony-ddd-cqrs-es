<?php

declare(strict_types=1);

namespace Storefront\Tests\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final class TwoFactorChallengeTest extends AbstractStorefrontTestCase
{
    private const string PASSWORD = 'MyStr0ngP@ssw0rd123!';
    private const string CSRF_TOKEN = 'test-csrf-token';

    #[Test]
    public function itShows(): void
    {
        // Given
        $client = self::browser();
        $email = 'two-factor-show@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $totpCredential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class))
            ->create();
        $this->store($identity, $passwordCredential, $totpCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $client->followRedirect();

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="two-factor-form"]');
    }

    #[Test]
    public function itCompletesLogin(): void
    {
        // Given
        $client = self::browser();
        $email = 'two-factor-complete@example.com';
        $secret = TOTP::generate()->getSecret();
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $totpCredential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withSecret($secret)
            ->withCipher($this->service(TotpCipherInterface::class))
            ->create();
        $this->store($identity, $passwordCredential, $totpCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $crawler = $client->followRedirect();
        $form = $crawler->filter('[data-testid="two-factor-form"]')->form();
        $form->setValues(['_auth_code' => $code]);

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_home_show'));
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $client = self::browser();
        $email = 'two-factor-refuse@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $totpCredential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class))
            ->create();
        $this->store($identity, $passwordCredential, $totpCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $crawler = $client->followRedirect();
        $form = $crawler->filter('[data-testid="two-factor-form"]')->form();
        $form->setValues(['_auth_code' => '000000']);

        // When
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertSelectorExists('[data-testid="two-factor-error"]');
    }

    #[Test]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $client = self::browser();
        $email = 'two-factor-trusted@example.com';
        $secret = TOTP::generate()->getSecret();
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $totpCredential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withSecret($secret)
            ->withCipher($this->service(TotpCipherInterface::class))
            ->create();
        $this->store($identity, $passwordCredential, $totpCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $crawler = $client->followRedirect();
        $form = $crawler->filter('[data-testid="two-factor-form"]')->form();
        $trustedField = $form['_trusted'];
        \assert($trustedField instanceof ChoiceFormField);
        $trustedField->tick();
        $form->setValues(['_auth_code' => $code]);
        $client->submit($form);
        $client->request('GET', '/logout');
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);

        // Then
        self::assertResponseRedirects($this->path('storefront_home_show'));
    }
}
