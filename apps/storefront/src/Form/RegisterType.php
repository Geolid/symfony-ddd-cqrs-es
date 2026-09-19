<?php

declare(strict_types=1);

namespace Storefront\Form;

use Storefront\Form\FormData\RegisterFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RegisterFormData>
 */
final class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, ['attr' => ['data-testid' => 'register-email', 'readonly' => 'readonly']])
            ->add('fullName', TextType::class, ['attr' => ['data-testid' => 'register-full-name']])
            ->add('password', PasswordType::class, ['attr' => ['data-testid' => 'register-password']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterFormData::class,
            'csrf_token_id' => 'register',
        ]);
    }
}
