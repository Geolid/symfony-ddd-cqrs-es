<?php

declare(strict_types=1);

namespace Storefront\Form;

use Iam\Authentication\Application\Validation\ValidPassword;
use Storefront\Form\FormData\RegisterFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
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
            ->add('email', HiddenType::class)
            ->add('fullName', TextType::class, [
                'label' => 'label_full_name',
                'attr' => ['placeholder' => 'placeholder_full_name'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'invalid_password_mismatch',
                'first_options' => [
                    'label' => 'label_password',
                    'label_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                    'help' => 'help_password',
                    'help_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                ],
                'second_options' => [
                    'label' => 'label_password_confirm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterFormData::class,
            'csrf_token_id' => 'register',
            'translation_domain' => 'register',
        ]);
    }
}
