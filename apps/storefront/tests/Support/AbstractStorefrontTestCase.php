<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Bootstrap\Kernel;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Browser\RegistrationExtensionInterface;
use Storefront\Tests\Browser\StorefrontKernelBrowser;
use Storefront\Tests\Browser\StorefrontPlaywrightBrowser;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Browser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * @method StorefrontKernelBrowser browser()
 */
abstract class AbstractStorefrontTestCase extends WebTestCase
{
    use EventSourcingTrait;
    use HasBrowser {
        playwrightBrowser as private traitPlaywrightBrowser;
    }
    use InteractsWithMailer;
    use ServiceLocatorTrait;

    private ?StorefrontPlaywrightBrowser $playwrightBrowser = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $_SERVER['KERNEL_BROWSER_CLASS'] = StorefrontKernelBrowser::class;
        $_SERVER['PLAYWRIGHT_BROWSER_CLASS'] = StorefrontPlaywrightBrowser::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (false !== getenv('PLAYWRIGHT_HEADLESS')) {
            $this->playwrightBrowser()->client()->context()?->tracing()->start([
                'screenshots' => true,
                'snapshots' => true,
            ]);
        }
    }

    protected function tearDown(): void
    {
        if (false !== getenv('PLAYWRIGHT_HEADLESS')) {
            $this->playwrightBrowser()->client()->context()?->tracing()->stop([
                'path' => \sprintf('%s/var/traces/%s__%s.zip', getcwd(), str_replace('\\', '_', static::class), $this->name()),
            ]);
        }

        parent::tearDown();
    }

    /**
     * Memoized: the trait's own method builds a brand-new session on every call, which would give
     * setUp()'s tracing start and the test's own actions two unrelated contexts.
     */
    protected function playwrightBrowser(): StorefrontPlaywrightBrowser
    {
        if (null === $this->playwrightBrowser) {
            $browser = $this->traitPlaywrightBrowser();
            \assert($browser instanceof StorefrontPlaywrightBrowser);
            $this->playwrightBrowser = $browser;
        }

        return $this->playwrightBrowser;
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

    /**
     * Uniform across backends (`StorefrontKernelBrowser`/`StorefrontPlaywrightBrowser` both carry
     * every Extension's own interface) — any value set for `PLAYWRIGHT_HEADLESS` switches to real
     * Chromium, auto-traced to `var/traces/` (replayable with `npx playwright show-trace`); unset,
     * everyday/CI runs stay on the fast KernelBrowser.
     */
    protected function activeBrowser(): Browser&AuthenticationExtensionInterface&RegistrationExtensionInterface
    {
        if (false !== getenv('PLAYWRIGHT_HEADLESS')) {
            return $this->playwrightBrowser();
        }

        return $this->browser()->disableReboot();
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function path(string $route, array $params = []): string
    {
        return $this->service(UrlGeneratorInterface::class)->generate($route, $params);
    }
}
