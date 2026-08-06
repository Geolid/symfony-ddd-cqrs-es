<?php

declare(strict_types=1);

namespace Ui\Twig\Components;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ui:ConfirmModal', template: '@ui/components/ConfirmModal.html.twig')]
final class ConfirmModal
{
    public string $id;
    public string $actionUrl;
    public string $message;
    public bool $dangerous = false;
    public bool $disabled = false;

    public string $triggerLabel;
    public string $title;
    public string $confirmLabel;
    public string $cancelLabel;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function mount(
        string $triggerLabel,
        ?string $title = null,
        ?string $confirmLabel = null,
        ?string $cancelLabel = null,
    ): void {
        $this->triggerLabel = $triggerLabel;
        $this->title = $title ?? $triggerLabel;
        $this->confirmLabel = $confirmLabel ?? $triggerLabel;
        $this->cancelLabel = $cancelLabel ?? $this->translator->trans('confirm_modal.button_cancel');
    }

    public function getTriggerClass(): string
    {
        return $this->dangerous ? 'pico-background-red-600 pico-color-white' : 'outline contrast';
    }

    public function getConfirmClass(): string
    {
        return $this->dangerous ? 'pico-background-red-600 pico-color-white' : '';
    }
}
