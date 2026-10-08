<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Bootstrap\Kernel;
use Storefront\Tests\Browser\StorefrontKernelBrowser;
use Storefront\Tests\Browser\StorefrontPlaywrightBrowser;
use Storefront\Tests\Support\Builder\AccountBuilder;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * @method StorefrontKernelBrowser     browser()
 * @method StorefrontPlaywrightBrowser playwrightBrowser()
 */
abstract class AbstractStorefrontTestCase extends WebTestCase
{
    use EventSourcingTrait;
    use HasBrowser;
    use InteractsWithMailer;
    use ServiceLocatorTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $_SERVER['KERNEL_BROWSER_CLASS'] = StorefrontKernelBrowser::class;
        $_SERVER['PLAYWRIGHT_BROWSER_CLASS'] = StorefrontPlaywrightBrowser::class;
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? (bool) ($_SERVER['APP_DEBUG'] ?? true),
            'storefront',
        );
    }

    protected function activeBrowser(): StorefrontKernelBrowser|StorefrontPlaywrightBrowser
    {
        if ('playwright' === getenv('ACTIVE_BROWSER')) {
            return $this->playwrightBrowser();
        }

        /* `disableReboot()` preserves in-memory event store between KernelBrowser requests. */
        return $this->browser()->disableReboot();
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function path(string $route, array $params = []): string
    {
        return $this->service(UrlGeneratorInterface::class)->generate($route, $params);
    }

    protected function assertEmailSent(int $count, string $recipient, string $subject): void
    {
        $this->mailer()
            ->sentEmails()->whereTo($recipient)->whereSubject($subject)
            ->assertCount($count);
    }

    protected function account(): AccountBuilder
    {
        return new AccountBuilder(service: $this->service(...), store: $this->store(...));
    }

    protected function advanceClock(string $modifier): void
    {
        Clock::set(new MockClock(Clock::get()->now()->modify($modifier)));
    }
}
