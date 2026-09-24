<?php

declare(strict_types=1);

namespace Storefront\Form\PasswordReset;

use Iam\Authentication\Application\Validation\ValidPassword;
use Storefront\Form\VerificationCode\VerificationCodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
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
            ->add('code', VerificationCodeType::class)
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'invalid_password_mismatch',
                'first_options' => [
                    'label' => 'reset_label_new_password',
                    'label_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                    'help' => 'reset_help_new_password',
                    'help_translation_parameters' => ['%min%' => ValidPassword::MIN_LENGTH],
                ],
                'second_options' => [
                    'label' => 'reset_label_new_password_confirm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetFormData::class,
            'csrf_token_id' => 'password_reset',
            'translation_domain' => 'forgot_password',
        ]);
    }
}
