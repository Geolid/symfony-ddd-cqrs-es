<?php

declare(strict_types=1);

namespace Storefront\Form;

use Storefront\Form\FormData\TotpConfirmFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TotpConfirmFormData>
 */
final class TotpConfirmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, ['attr' => ['data-testid' => 'totp-confirm-code']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TotpConfirmFormData::class,
            'csrf_token_id' => 'totp-confirm',
        ]);
    }
}
