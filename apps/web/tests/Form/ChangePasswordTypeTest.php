<?php

declare(strict_types=1);

namespace Web\Tests\Form;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Form\FormFactoryInterface;
use Web\Form\ChangePasswordType;
use Web\Form\FormData\ChangePasswordFormData;
use Web\Tests\Support\AbstractWebTestCase;

final class ChangePasswordTypeTest extends AbstractWebTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::browser();
        $this->formFactory = $this->service(FormFactoryInterface::class);
    }

    #[Test]
    public function itBindsSubmittedDataOntoAChangePasswordFormData(): void
    {
        // Given
        $form = $this->formFactory->create(ChangePasswordType::class);

        // When
        $form->submit(['password' => 'a brand new password']);

        // Then
        self::assertInstanceOf(ChangePasswordFormData::class, $form->getData());
        self::assertSame('a brand new password', $form->getData()->password);
    }

    #[Test]
    public function itLabelsThePasswordField(): void
    {
        // Given
        $form = $this->formFactory->create(ChangePasswordType::class);

        // Then
        self::assertSame(
            'sales.customer.profile.label_password',
            $form->get('password')->getConfig()->getOption('label'),
        );
    }
}
