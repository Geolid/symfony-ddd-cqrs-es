<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class SecurityControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheLoginForm(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('security_login'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="login-form"]')->count());
    }

    #[Test]
    public function itLogsIn(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $login = $this->givenPasswordCredential($identity->id->toString());

        // When
        $this->logIn($client, $login, 'MyStr0ngP@ssw0rd123!');

        // Then
        self::assertResponseRedirects($this->path('storefront_account_show'));
    }

    #[Test]
    public function itRefusesWithWrongPassword(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $login = $this->givenPasswordCredential($identity->id->toString());

        // When
        $this->logIn($client, $login, 'WrongPassword123!');

        // Then
        self::assertResponseRedirects($this->path('security_login'));
        $client->followRedirect();
        self::assertGreaterThan(0, $client->getCrawler()->filter('[data-testid="login-error"]')->count());
    }
}
