<?php

declare(strict_types=1);

namespace Storefront\Form\ChangeEmail;

use Storefront\Form\VerificationCode\VerificationCodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ChangeEmailFormData>
 */
final class ChangeEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', VerificationCodeType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangeEmailFormData::class,
            'csrf_token_id' => 'change_email',
        ]);
    }
}
