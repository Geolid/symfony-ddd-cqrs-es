<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class SecurityControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheIdentifyForm(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('security_login'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="identify-form"]')->count());
    }

    #[Test]
    public function itShowsTheUnknownEmailScreen(): void
    {
        // Given
        $builder = IdentityBuilder::new();

        // When
        $client = self::browser();
        $client->request('GET', $this->path('security_login', ['email' => $builder['email']->value]));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="identify-unknown"]')->count());
    }

    #[Test]
    public function itShowsThePasswordForm(): void
    {
        // Given
        $client = self::browser();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $client->request('GET', $this->path('security_login', ['email' => $builder['email']->value]));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="password-form"]')->count());
    }

    #[Test]
    public function itRedirectsToConfirmationWhenPending(): void
    {
        // Given
        $client = self::browser();
        $builder = IdentityBuilder::new();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $client->request('GET', $this->path('security_login', ['email' => $builder['email']->value]));

        // Then
        self::assertResponseRedirects($this->path('storefront_register_confirm', ['id' => $identity->id->toString()]));
    }

    #[Test]
    public function itLogsIn(): void
    {
        // Given
        $client = self::browser();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($identity);
        $this->givenPasswordCredential($identity->id->toString());

        // When
        $this->logIn($client, $builder['email']->value, 'MyStr0ngP@ssw0rd123!');

        // Then
        self::assertResponseRedirects($this->path('storefront_account_show'));
    }

    #[Test]
    public function itRefusesWithWrongPassword(): void
    {
        // Given
        $client = self::browser();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($identity);
        $this->givenPasswordCredential($identity->id->toString());

        // When
        $this->logIn($client, $builder['email']->value, 'WrongPassword123!');

        // Then
        self::assertResponseRedirects($this->path('security_login', ['email' => $builder['email']->value]));
        $client->followRedirect();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="login-error"]')->count());
    }
}
