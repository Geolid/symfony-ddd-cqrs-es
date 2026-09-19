<?php

declare(strict_types=1);

namespace Storefront\Form;

use Storefront\Form\FormData\PasswordResetRequestFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PasswordResetRequestFormData>
 */
final class PasswordResetRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', TextType::class, ['attr' => ['data-testid' => 'password-reset-request-email']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetRequestFormData::class,
            'csrf_token_id' => 'password_reset_request',
        ]);
    }
}
