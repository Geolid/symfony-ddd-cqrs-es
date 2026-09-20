<?php

declare(strict_types=1);

namespace Storefront\Form;

use Storefront\Form\FormData\PasswordResetFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PasswordResetFormData>
 */
final class PasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, ['attr' => [
                'data-testid' => 'password-reset-code',
                'inputmode' => 'numeric',
                'pattern' => '\d{6}',
                'maxlength' => 6,
                'autocomplete' => 'one-time-code',
            ]])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'first_options' => ['attr' => ['data-testid' => 'password-reset-new-password']],
                'second_options' => ['attr' => ['data-testid' => 'password-reset-new-password-confirm']],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetFormData::class,
            'csrf_token_id' => 'password_reset',
        ]);
    }
}
