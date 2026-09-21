<?php

declare(strict_types=1);

namespace Storefront\Form\TwoFactorConfirm;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TwoFactorConfirmFormData>
 */
final class TwoFactorConfirmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TwoFactorConfirmFormData::class,
            'csrf_token_id' => 'two-factor-confirm',
        ]);
    }
}
