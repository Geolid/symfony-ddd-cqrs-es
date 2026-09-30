<?php

declare(strict_types=1);

namespace Storefront\Tests\Scenario;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Clock\Clock;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\RateLimiter\DefaultLoginRateLimiter;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Covers the full sign-in surface: identify, verify (password), the two-factor
 * challenge, and logout.
 */
final class SignInTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsIdentify(): void
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
        $form = $this->identifyForm($client, IdentityBuilder::sample('email')->value);

        // When
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
        $given = $this->givenUnconfirmedIdentity();

        $form = $this->identifyForm($client, $given->email);

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_registration_confirm', ['identityId' => $given->id]));
    }

    #[Test]
    public function itRedirectsToVerify(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentity();

        $form = $this->identifyForm($client, $given->email);

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_signin_verify'));
    }

    #[Test]
    public function itRefusesMalformedEmail(): void
    {
        // Given
        $client = self::browser();
        $form = $this->identifyForm($client, 'not-an-email');

        // When
        $client->submit($form);

        // Then
        self::assertSelectorExists('[data-testid="identify_email-error"]');
    }

    #[Test]
    public function itShowsVerify(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentity();

        $form = $this->identifyForm($client, $given->email);

        // When
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
    public function itSignsIn(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPassword();

        $form = $this->verifyForm($client, $given->email, $given->requirePassword());

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_home_show'));
        self::assertNotNull($client->getCookieJar()->get('REMEMBERME'));
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPassword();
        $this->exhaustLoginAttempts($given->email);

        $form = $this->verifyForm($client, $given->email, $given->requirePassword());

        // When
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Too many failed login attempts');
    }

    #[Test]
    public function itRefusesUnconfirmedAccount(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenUnconfirmedIdentityWithPassword();

        $form = $this->verifyForm($client, $given->email, $given->requirePassword());

        // When
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Your account is not confirmed.');
    }

    #[Test]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenSuspendedIdentityWithPassword();

        $form = $this->verifyForm($client, $given->email, $given->requirePassword());

        // When
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Your account is suspended.');
    }

    #[Test]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPassword();

        $form = $this->verifyForm($client, $given->email, 'wrong password');

        // When
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertSelectorTextContains('[data-testid="verify-error"]', 'Invalid credentials.');
        self::assertInputValueSame('email', $given->email);
    }

    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPassword();

        $this->loginAs($client, $given->email);

        // When
        $client->request('GET', '/logout');

        // Then
        self::assertResponseRedirects($this->path('storefront_signin_identify'));
    }

    #[Test]
    public function itShowsTwoFactorChallenge(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();

        $form = $this->verifyForm($client, $given->email, $given->requirePassword());

        // When
        $client->submit($form);
        $client->followRedirect();

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="two-factor-form"]');
    }

    #[Test]
    public function itCompletesSignIn(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();

        $verifyForm = $this->verifyForm($client, $given->email, $given->requirePassword());
        $client->submit($verifyForm);
        $crawler = $client->followRedirect();

        $twoFactorForm = $this->twoFactorForm($crawler, $this->totpCode($given->requireTotpSecret()));

        // When
        $client->submit($twoFactorForm);

        // Then
        self::assertResponseRedirects($this->path('storefront_home_show'));
    }

    #[Test]
    public function itSkipsOnTrustedDevice(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();

        $verifyForm = $this->verifyForm($client, $given->email, $given->requirePassword());
        $client->submit($verifyForm);
        $crawler = $client->followRedirect();

        $twoFactorForm = $this->twoFactorForm($crawler, $this->totpCode($given->requireTotpSecret()), trust: true);
        $client->submit($twoFactorForm);

        $client->request('GET', '/logout');

        $secondVerifyForm = $this->verifyForm($client, $given->email, $given->requirePassword());

        // When
        $client->submit($secondVerifyForm);

        // Then
        self::assertResponseRedirects($this->path('storefront_home_show'));
    }

    #[Test]
    public function itRefusesIncorrectCode(): void
    {
        // Given
        $client = self::browser();
        $given = $this->givenConfirmedIdentityWithPasswordAndTotp();

        $verifyForm = $this->verifyForm($client, $given->email, $given->requirePassword());
        $client->submit($verifyForm);
        $crawler = $client->followRedirect();

        $twoFactorForm = $this->twoFactorForm($crawler, '000000');

        // When
        $client->submit($twoFactorForm);
        $client->followRedirect();

        // Then
        self::assertSelectorExists('[data-testid="two-factor-error"]');
    }

    private function identifyForm(KernelBrowser $client, string $email): Form
    {
        $crawler = $client->request('GET', $this->path('storefront_signin_identify'));
        $form = $crawler->filter('[data-testid="identify-form"]')->form();
        $form->setValues(['identify[email]' => $email]);

        return $form;
    }

    private function verifyForm(KernelBrowser $client, string $email, string $password): Form
    {
        $session = $client->getSession();
        \assert($session instanceof SessionInterface);
        $session->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        $session->save();

        $crawler = $client->request('GET', $this->path('storefront_signin_verify'));
        $form = $crawler->filter('[data-testid="verify-form"]')->form();
        $form->setValues(['password' => $password]);

        return $form;
    }

    private function twoFactorForm(Crawler $crawler, string $code, bool $trust = false): Form
    {
        $form = $crawler->filter('[data-testid="two-factor-form"]')->form();
        if ($trust) {
            $trustedField = $form['_trusted'];
            \assert($trustedField instanceof ChoiceFormField);
            $trustedField->tick();
        }
        $form->setValues(['_auth_code' => $code]);

        return $form;
    }

    private function totpCode(string $secret): string
    {
        \assert('' !== $secret);

        return TOTP::createFromSecret($secret, Clock::get())->now();
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

    private function givenUnconfirmedIdentity(): GivenAccount
    {
        $identityBuilder = IdentityBuilder::new();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new GivenAccount($identity->id->toString(), $identityBuilder['email']->value);
    }

    private function givenConfirmedIdentity(): GivenAccount
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new GivenAccount($identity->id->toString(), $identityBuilder['email']->value);
    }

    private function givenConfirmedIdentityWithPassword(): GivenAccount
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create());

        return new GivenAccount($identity->id->toString(), $identityBuilder['email']->value, $passwordBuilder['password']->value);
    }

    private function givenUnconfirmedIdentityWithPassword(): GivenAccount
    {
        $identityBuilder = IdentityBuilder::new();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create());

        return new GivenAccount($identity->id->toString(), $identityBuilder['email']->value, $passwordBuilder['password']->value);
    }

    private function givenSuspendedIdentityWithPassword(): GivenAccount
    {
        $identityBuilder = IdentityBuilder::new()->confirmed()->suspended();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create());

        return new GivenAccount($identity->id->toString(), $identityBuilder['email']->value, $passwordBuilder['password']->value);
    }

    private function givenConfirmedIdentityWithPasswordAndTotp(): GivenAccount
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialFor($identity);
        $totpBuilder = $this->totpCredentialFor($identity);
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create());

        return new GivenAccount(
            $identity->id->toString(),
            $identityBuilder['email']->value,
            $passwordBuilder['password']->value,
            $totpBuilder['secret'],
        );
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }

    private function totpCredentialFor(Identity $identity): TotpCredentialBuilder
    {
        return TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
    }
}

final readonly class GivenAccount
{
    public function __construct(
        public string $id,
        public string $email,
        public ?string $password = null,
        public ?string $totpSecret = null,
    ) {
    }

    public function requirePassword(): string
    {
        \assert(null !== $this->password);

        return $this->password;
    }

    public function requireTotpSecret(): string
    {
        \assert(null !== $this->totpSecret);

        return $this->totpSecret;
    }
}
