<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\PersonalData;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\ErasureRequestedStory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class ShowTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itShows(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        // When
        $browser->visitRoute('storefront_account_personal_data_show');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="request-erasure-link"]')
            ->assertNotSeeElement('[data-testid="erasure-pending"]');
    }

    #[Test]
    #[WithStory(ErasureRequestedStory::class)]
    public function itShowsWhenErasureRequested(): void
    {
        // Given
        $persona = ErasureRequestedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        // When
        $browser->visitRoute('storefront_account_personal_data_show');

        // Then
        $browser->assertSeeElement('[data-testid="erasure-pending"]')
            ->assertSeeElement('[data-testid="cancel-erasure-submit"]')
            ->assertNotSeeElement('[data-testid="request-erasure-link"]');
    }

    #[Test]
    #[WithStory(ErasureRequestedStory::class)]
    public function itCancelsErasure(): void
    {
        // Given
        $persona = ErasureRequestedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_show');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="cancel-erasure-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'cancel_erasure_flash_cancelled');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_personal_data_show');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }

    #[Test]
    #[WithStory(ErasureRequestedStory::class)]
    public function itRefusesCancelWithInvalidCsrfToken(): void
    {
        // Given
        $persona = ErasureRequestedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_show');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="cancel-erasure-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
    }
}
