<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\PersonalData;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ErasureRequestedStory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class CancelErasureTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ErasureRequestedStory::class)]
    public function itCancels(): void
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
    #[WithStory(ErasureRequestedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
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
