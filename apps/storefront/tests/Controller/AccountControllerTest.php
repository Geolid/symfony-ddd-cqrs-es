<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class AccountControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsTheAccount(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', $this->path('storefront_account_show'));

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($identity->id->toString(), $client->getCrawler()->filter('[data-testid="account-identity-id"]')->text());
    }

    #[Test]
    public function itRefusesWhenNotAuthenticated(): void
    {
        // When
        $client = self::browser();
        $client->request('GET', $this->path('storefront_account_show'));

        // Then
        self::assertResponseRedirects($this->path('security_login'));
    }
}
