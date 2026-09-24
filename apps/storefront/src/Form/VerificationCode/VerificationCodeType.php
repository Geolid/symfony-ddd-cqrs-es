<?php

declare(strict_types=1);

namespace Storefront\Form\VerificationCode;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<string>
 */
final class VerificationCodeType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'label_code',
            'translation_domain' => 'verification_code',
            'attr' => [
                'inputmode' => 'numeric',
                'pattern' => '\d{6}',
                'maxlength' => 6,
                'autocomplete' => 'one-time-code',
            ],
        ]);
    }
}
