<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialVerificationCodePurpose;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Support\Faker\SeededFaker;
use Symfony\Component\Clock\Clock;

final class PasswordResetControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheRequestForm(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('storefront_password_reset_request'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="password-reset-request-form"]')->count());
    }

    #[Test]
    public function itShowsAnErrorWhenEmailUnknown(): void
    {
        // Given
        $client = self::browser();
        $crawler = $client->request('GET', $this->path('storefront_password_reset_request'));
        $form = $crawler->filter('[data-testid="password-reset-request-form"]')->form();

        // When
        $form->setValues(['password_reset_request[email]' => SeededFaker::get()->unique()->safeEmail()]);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="flash-error"]')->count());
    }

    #[Test]
    public function itRequestsAndResets(): void
    {
        // Given
        $client = self::browser();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($identity);
        $this->givenPasswordCredential($identity->id->toString());
        $crawler = $client->request('GET', $this->path('storefront_password_reset_request'));
        $form = $crawler->filter('[data-testid="password-reset-request-form"]')->form();

        // When
        $form->setValues(['password_reset_request[email]' => $builder['email']->value]);
        $client->submit($form);
        $crawler = $client->followRedirect();

        // Then
        self::assertGreaterThan(0, $crawler->filter('[data-testid="password-reset-form"]')->count());

        $code = $this->service(NativeCodeChallenger::class)->issue(
            VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $identity->id->toString()),
            Clock::get()->now(),
        );
        $form = $crawler->filter('[data-testid="password-reset-form"]')->form();
        $form->setValues([
            'password_reset[code]' => $code,
            'password_reset[newPassword][first]' => 'ANewStr0ngP@ssw0rd1!',
            'password_reset[newPassword][second]' => 'ANewStr0ngP@ssw0rd1!',
        ]);
        $client->submit($form);

        self::assertResponseRedirects($this->path('security_login'));
        $this->logIn($client, $builder['email']->value, 'ANewStr0ngP@ssw0rd1!');
        self::assertResponseRedirects($this->path('storefront_account_show'));
    }

    #[Test]
    public function itRefusesToResendTooSoon(): void
    {
        // Given
        $client = self::browser();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($identity);
        $this->givenPasswordCredential($identity->id->toString());
        $crawler = $client->request('GET', $this->path('storefront_password_reset_request'));
        $form = $crawler->filter('[data-testid="password-reset-request-form"]')->form();
        $form->setValues(['password_reset_request[email]' => $builder['email']->value]);
        $client->submit($form);
        $crawler = $client->followRedirect();
        $form = $crawler->filter('[data-testid="password-reset-resend-form"]')->form();

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_password_reset', ['identityId' => $identity->id->toString()]));
        $client->followRedirect();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="flash-error"]')->count());
    }
}
