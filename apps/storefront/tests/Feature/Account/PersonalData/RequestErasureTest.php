<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\PersonalData;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\ErasureRequestedStory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Clock\Clock;
use Zenstruck\Foundry\Attribute\WithStory;

final class RequestErasureTest extends AbstractStorefrontTestCase
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
        $browser->visitRoute('storefront_account_personal_data_request_erasure');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="erasure-consequences"]')
            ->assertSeeElement('[data-testid="request-erasure-submit"]');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRequests(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_request_erasure');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="request-erasure-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'request_erasure_flash_requested');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itNotifies(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_request_erasure');

        // When
        $browser->click('[data-testid="request-erasure-submit"]');

        // Then
        $this->assertEmailSent(1, $persona->email, 'Your account deletion request');
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        self::assertIsString($body);
        self::assertStringContainsString(Clock::get()->now()->modify('+30 days')->format('Y-m-d'), $body);
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRefusesInvalidCsrfToken(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_request_erasure');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (AbstractBrowser $client): void {
            $form = $client->getCrawler()->filter('[data-testid="request-erasure-form"]')->form(['_token' => 'invalid']);
            $client->submit($form);
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_request_erasure')
            ->assertSeeIn('[data-testid="flash-error"]', 'flash_invalid_csrf_token');
        $this->assertEmailSent(0, $persona->email, 'Your account deletion request');
    }

    #[Test]
    #[WithStory(ErasureRequestedStory::class)]
    public function itIgnoresWhenErasureRequested(): void
    {
        // Given
        $persona = ErasureRequestedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_personal_data_request_erasure');
        $browser->interceptRedirects();

        // When
        $browser->click('[data-testid="request-erasure-submit"]');

        // Then
        $browser->assertRedirectedToRoute('storefront_account_personal_data_show');
    }
}
