<?php

declare(strict_types=1);

namespace Storefront\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FormExceptionMapper
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @template TData
     *
     * @param FormInterface<TData> $form
     */
    public function map(FormInterface $form, \Throwable $exception): bool
    {
        $dataClass = $form->getConfig()->getDataClass();

        if (null === $dataClass) {
            return false;
        }

        \assert(class_exists($dataClass));

        foreach (new \ReflectionClass($dataClass)->getProperties() as $property) {
            foreach ($property->getAttributes(MapsError::class) as $attribute) {
                $mapsError = $attribute->newInstance();

                if ($exception::class !== $mapsError->exceptionClass) {
                    continue;
                }

                $form->get($property->getName())->addError(
                    new FormError($this->translator->trans($mapsError->translationId, domain: $mapsError->translationDomain)),
                );

                return true;
            }
        }

        return false;
    }
}
