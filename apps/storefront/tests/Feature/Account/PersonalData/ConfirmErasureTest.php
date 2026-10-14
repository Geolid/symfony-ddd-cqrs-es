<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\PersonalData;

use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\ErasureRequestedStory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class ConfirmErasureTest extends AbstractStorefrontTestCase
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
        $browser->visitRoute('storefront_account_personal_data_confirm_erasure');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="confirm-erasure-consequences"]');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_personal_data_confirm_erasure');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRequests(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_confirm_erasure');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="confirm-erasure-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'request_erasure_flash_requested');
        $this->assertEmailSent(1, $persona->email, 'Your account deletion request');
        $erasure = $this->service(ErasureFinderInterface::class)->requestedOfIdentityOrNull($persona->id);
        self::assertNotNull($erasure);
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        self::assertIsString($body);
        self::assertStringContainsString($erasure->effectiveAt->format('Y-m-d'), $body);
    }

    #[Test]
    #[WithStory(ErasureRequestedStory::class)]
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $persona = ErasureRequestedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_confirm_erasure');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="confirm-erasure-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_confirm_erasure');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="confirm-erasure-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_confirm_erasure')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
        $this->assertEmailSent(0, $persona->email, 'Your account deletion request');
    }
}
