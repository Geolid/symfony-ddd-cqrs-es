<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Support\Story\AccountConfirmedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangeFullNameForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class ChangeFullNameTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $browser->visitRoute('storefront_account_security_change_full_name');
        $browser->interceptRedirects();
        $newFullName = FullNameFactory::new()->create()->value;

        // When
        $browser->use(static function (ChangeFullNameForm $form) use ($newFullName): void {
            $form->fillFullName($newFullName)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_full_name_flash_changed')
            ->assertSeeIn('[data-testid="full-name-value"]', $newFullName);
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_change_full_name');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
