<?php

declare(strict_types=1);

namespace Storefront\Form\ChangeFullName;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ChangeFullNameFormData>
 */
final class ChangeFullNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fullName', TextType::class, [
            'label' => 'change_full_name_label',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangeFullNameFormData::class,
            'csrf_token_id' => 'change_full_name',
            'translation_domain' => 'account_security',
        ]);
    }
}
