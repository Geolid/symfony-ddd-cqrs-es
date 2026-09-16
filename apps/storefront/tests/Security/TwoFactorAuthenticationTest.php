<?php

declare(strict_types=1);

namespace Storefront\Tests\Security;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;

final class TwoFactorAuthenticationTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itChallengesWhenEnrolled(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $totpCredential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withSecret($secret)
            ->withCipher($this->service(TotpCipherInterface::class))
            ->withVerifier($this->service(TotpVerifierInterface::class))
            ->confirmed($code)
            ->create();
        $this->store($identity, $totpCredential);
        $login = $this->givenPasswordCredential($identity->id->toString());

        // When
        $this->logIn($client, $login, 'MyStr0ngP@ssw0rd123!');

        // Then
        self::assertResponseRedirects($this->path('storefront_totp_challenge'));

        $crawler = $client->followRedirect();
        $form = $crawler->filter('[data-testid="totp-form"]')->form();
        $form->setValues(['_auth_code' => $code]);
        $client->submit($form);

        self::assertResponseRedirects($this->path('storefront_account_show'));
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function itRefusesWithWrongCode(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $secret = TOTP::generate()->getSecret();
        $totpCredential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withSecret($secret)
            ->withCipher($this->service(TotpCipherInterface::class))
            ->withVerifier($this->service(TotpVerifierInterface::class))
            ->confirmed(TOTP::createFromSecret($secret, Clock::get())->now())
            ->create();
        $this->store($identity, $totpCredential);
        $login = $this->givenPasswordCredential($identity->id->toString());
        $this->logIn($client, $login, 'MyStr0ngP@ssw0rd123!');
        $crawler = $client->followRedirect();
        $form = $crawler->filter('[data-testid="totp-form"]')->form();

        // When
        $form->setValues(['_auth_code' => '000000']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('storefront_totp_challenge'));
        $crawler = $client->followRedirect();
        self::assertGreaterThan(0, $crawler->filter('[data-testid="totp-error"]')->count());
    }
}
