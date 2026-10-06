<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security\Component;

use Zenstruck\Browser\Component;

final class ChangeFullNameForm extends Component
{
    public function fillFullName(string $fullName): self
    {
        $this->browser()->fillField('change_full_name_fullName', $fullName);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="change-full-name-submit"]');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="change-full-name-form"]');
    }
}
