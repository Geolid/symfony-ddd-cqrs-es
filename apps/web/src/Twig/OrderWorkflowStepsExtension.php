<?php

declare(strict_types=1);

namespace Web\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class OrderWorkflowStepsExtension extends AbstractExtension
{
    /** @var list<string> */
    private const array STEPS = ['placed', 'paid', 'shipped', 'delivered'];

    /** @var array<string, int> */
    private const array CURRENT_STEP_BY_STATUS = [
        'placed' => 0,
        'payment_pending' => 1,
        'paid' => 2,
        'preparing' => 2,
        'dispatched' => 3,
        'delivered' => 4,
    ];

    public function getFunctions(): array
    {
        return [new TwigFunction('order_workflow_steps', $this->steps(...))];
    }

    /**
     * @return list<array{key: string, state: string}>
     */
    public function steps(string $status): array
    {
        if ('cancelled' === $status) {
            return [];
        }

        $current = self::CURRENT_STEP_BY_STATUS[$status] ?? 0;

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
