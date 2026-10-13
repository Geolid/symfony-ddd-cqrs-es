<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account;

use Iam\Tests\Support\Story\ConfirmedAccountStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class ShowTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        // When
        $browser->visitRoute('storefront_account_show');

        // Then
        $browser->assertSuccessful();
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_show');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
