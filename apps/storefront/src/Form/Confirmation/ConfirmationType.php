<?php

declare(strict_types=1);

namespace Storefront\Form\Confirmation;

use Storefront\Form\VerificationCode\VerificationCodeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ConfirmationFormData>
 */
final class ConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', VerificationCodeType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfirmationFormData::class,
            'csrf_token_id' => 'confirmation',
        ]);
    }
}
