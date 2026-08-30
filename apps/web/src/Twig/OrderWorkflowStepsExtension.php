<?php

declare(strict_types=1);

namespace Web\Twig;

use Sales\OrderSummary\Application\OrderSummaryStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class OrderWorkflowStepsExtension extends AbstractExtension
{
    /** @var list<string> */
    private const array STEPS = ['placed', 'paid', 'shipped', 'delivered'];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('order_workflow_steps', $this->steps(...)),
            new TwigFunction('order_status_variant', $this->statusVariant(...)),
        ];
    }

    public function statusVariant(OrderSummaryStatus $status): string
    {
        return match (true) {
            $status->isDelivered() => 'success',
            $status->isPaymentPending() => 'warning',
            $status->isCancelled() => 'error',
            default => 'info',
        };
    }

    /**
     * @return list<array{key: string, state: string}>
     */
    public function steps(OrderSummaryStatus $status): array
    {
        if ($status->isCancelled()) {
            return [];
        }

        $current = match (true) {
            $status->isPlaced() => 0,
            $status->isPaymentPending() => 1,
            $status->isPreparing() => 2,
            $status->isDispatched() => 3,
            $status->isDelivered() => 4,
            default => 0,
        };

        return array_map(
            static fn (int $index, string $step): array => [
                'key' => $step,
                'state' => match (true) {
                    $index < $current => 'done',
                    $index === $current => 'current',
                    default => 'upcoming',
                },
            ],
            array_keys(self::STEPS),
            self::STEPS,
        );
    }
}
