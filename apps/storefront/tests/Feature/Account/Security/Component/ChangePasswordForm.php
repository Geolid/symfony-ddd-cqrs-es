<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security\Component;

use Zenstruck\Browser\Component;

final class ChangePasswordForm extends Component
{
    public function fillCurrentPassword(string $password): self
    {
        $this->browser()->fillField('change_password_currentPassword', $password);

        return $this;
    }

    public function fillNewPassword(string $password): self
    {
        $this->browser()->fillField('change_password_newPassword_first', $password)
            ->fillField('change_password_newPassword_second', $password);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="change-password-submit"]');

        return $this;
    }

    public function assertInvalidCurrentPasswordError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="change_password_currentPassword-error"]', 'change_error_invalid_current_password');

        return $this;
    }

    public function assertSamePasswordError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="change_password_newPassword-error"]', 'change_error_same_password');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="change-password-form"]');
    }
}
