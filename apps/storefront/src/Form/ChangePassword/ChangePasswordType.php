<?php

declare(strict_types=1);

namespace Storefront\Form\ChangePassword;

use Iam\Authentication\Application\Validation\ValidPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ChangePasswordFormData>
 */
final class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'change_label_current_password',
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'invalid_password_mismatch',
                'first_options' => [
                    'label' => 'change_label_new_password',
                    'label_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                    'help' => 'change_help_new_password',
                    'help_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                ],
                'second_options' => [
                    'label' => 'change_label_new_password_confirm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordFormData::class,
            'csrf_token_id' => 'change_password',
            'translation_domain' => 'account_security',
        ]);
    }
}
