<?php

declare(strict_types=1);

namespace Storefront\Form;

use Iam\Authentication\Application\Validation\ValidPassword;
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
            ->add('code', TextType::class, [
                'label' => 'label_code',
                'attr' => [
                    'inputmode' => 'numeric',
                    'pattern' => '\d{6}',
                    'maxlength' => 6,
                    'autocomplete' => 'one-time-code',
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'invalid_password_mismatch',
                'first_options' => [
                    'label' => 'label_new_password',
                    'label_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                    'help' => 'help_new_password',
                    'help_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                ],
                'second_options' => [
                    'label' => 'label_new_password_confirm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetFormData::class,
            'csrf_token_id' => 'password_reset',
            'translation_domain' => 'password_reset',
        ]);
    }
}
