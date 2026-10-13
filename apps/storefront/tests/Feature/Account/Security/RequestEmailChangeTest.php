<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Identity\Support\Factory\EmailFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\OtherConfirmedStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class RequestEmailChangeTest extends AbstractStorefrontTestCase
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
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="request-email-change-form"]');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRequests(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->interceptRedirects();
        $newEmail = EmailFactory::new()->create()->value;

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($newEmail): void {
            $form->fillNewEmail($newEmail)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_change_email');
        $this->assertEmailSent(1, $newEmail, 'Confirm your new email address');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    #[WithStory(OtherConfirmedStory::class)]
    public function itRefusesEmailAlreadyInUse(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $otherEmail = OtherConfirmedStory::persona()->email;

        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->visitRoute('storefront_account_security_request_email_change');

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($otherEmail): void {
            $form->fillNewEmail($otherEmail)->submit();
        });

        // Then
        $browser->use(static function (RequestEmailChangeForm $form): void {
            $form->assertAlreadyInUseError();
        });
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
