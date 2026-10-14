<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\PersonalData;

use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\ErasureRequestedStory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Clock\Clock;
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
        $effectiveDate = Clock::get()->now()->modify(\sprintf('+%d days', ErasureFactory::RETENTION_DAYS))->format('Y-m-d');

        // When
        $browser->click('[data-testid="confirm-erasure-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'request_erasure_flash_requested');
        $this->assertEmailSent(1, $persona->email, 'Your account deletion request');
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        self::assertIsString($body);
        self::assertStringContainsString($effectiveDate, $body);
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
