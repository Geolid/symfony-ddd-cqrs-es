<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\RateLimiter\DefaultLoginRateLimiter;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class SignInControllerTest extends AbstractStorefrontTestCase
{
    private const string PASSWORD = 'MyStr0ngP@ssw0rd123!';
    private const string CSRF_TOKEN = 'test-csrf-token';

    #[Test]
    public function itShows(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $this->path('storefront_signin_identify'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="identify-form"]');
    }

    #[Test]
    public function itShowsCreateAccount(): void
    {
        // Given
        $client = self::browser();

        // When
        $crawler = $client->request('GET', $this->path('storefront_signin_identify'));
        $form = $crawler->filter('[data-testid="identify-form"]')->form();
        $form->setValues(['identify[email]' => 'unknown@example.com']);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="create-account-button"]');
    }

    #[Test]
    public function itRedirectsToConfirmation(): void
    {
        // Given
        $client = self::browser();
        $email = 'unconfirmed@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->create();
        $this->store($identity);

        // When
        $crawler = $client->request('GET', $this->path('storefront_signin_identify'));
        $form = $crawler->filter('[data-testid="identify-form"]')->form();
        $form->setValues(['identify[email]' => $email]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_registration_confirm', ['identityId' => $identity->id->toString()]));
    }

    #[Test]
    public function itRedirectsToVerify(): void
    {
        // Given
        $client = self::browser();
        $email = 'confirmed@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $this->store($identity);

        // When
        $crawler = $client->request('GET', $this->path('storefront_signin_identify'));
        $form = $crawler->filter('[data-testid="identify-form"]')->form();
        $form->setValues(['identify[email]' => $email]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_signin_verify'));
    }

    #[Test]
    public function itShowsVerify(): void
    {
        // Given
        $client = self::browser();
        $email = 'confirmed@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $this->store($identity);

        // When
        $crawler = $client->request('GET', $this->path('storefront_signin_identify'));
        $form = $crawler->filter('[data-testid="identify-form"]')->form();
        $form->setValues(['identify[email]' => $email]);
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="verify-form"]');
    }

    #[Test]
    public function itRedirectsToIdentify(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $this->path('storefront_signin_verify'));

        // Then
        self::assertResponseRedirects($this->path('storefront_signin_identify'));
    }

    #[Test]
    public function itLogsIn(): void
    {
        // Given
        $client = self::browser();
        $email = 'login-success@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);

        // Then
        self::assertResponseRedirects($this->path('storefront_home_show'));
        self::assertNotNull($client->getCookieJar()->get('REMEMBERME'));
    }

    #[Test]
    public function itRedirectsToTwoFactorChallenge(): void
    {
        // Given
        $client = self::browser();
        $email = 'two-factor@example.com';
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

        // Then
        self::assertResponseRedirects($this->path('storefront_two_factor_challenge'));
    }

    #[Test]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $client = self::browser();
        $email = 'wrong-password@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => 'wrong password',
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Invalid credentials.');
        self::assertInputValueSame('email', $email);
    }

    #[Test]
    public function itRefusesUnconfirmedAccount(): void
    {
        // Given
        $client = self::browser();
        $email = 'unconfirmed-login@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Your account is not confirmed.');
    }

    #[Test]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $client = self::browser();
        $email = 'suspended@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->suspended()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Your account is suspended.');
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $client = self::browser();
        $email = 'throttled@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);
        $this->seedCsrfToken($client, 'authenticate', self::CSRF_TOKEN);
        $this->exhaustLoginAttempts($email);

        // When
        $client->request('POST', $this->path('storefront_signin_verify'), [
            'email' => $email,
            'password' => self::PASSWORD,
            '_csrf_token' => self::CSRF_TOKEN,
        ]);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Too many failed login attempts');
    }

    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $client = self::browser();
        $email = 'logout@example.com';
        $identity = IdentityBuilder::new()->withEmail($email)->confirmed()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword(self::PASSWORD)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);
        $this->loginAs($client, $email);

        // When
        $client->request('GET', '/logout');

        // Then
        self::assertResponseRedirects($this->path('storefront_signin_identify'));
    }

    private function exhaustLoginAttempts(string $email): void
    {
        $limiter = $this->serviceAs('security.login_throttling.main.limiter', DefaultLoginRateLimiter::class);
        $request = Request::create('/');
        $request->attributes->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        $maxAttempts = $limiter->peek($request)->getRemainingTokens();
        for ($i = 0; $i < $maxAttempts; ++$i) {
            $limiter->consume($request);
        }
    }
}
