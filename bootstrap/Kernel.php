<?php

declare(strict_types=1);

namespace Bootstrap;

use Bootstrap\DependencyInjection\CompilerPass\RegisterDoctrineSchemaConfiguratorsPass;
use Bootstrap\DependencyInjection\CompilerPass\RegisterEnvVarProcessorsPass;
use Bootstrap\DependencyInjection\CompilerPass\RegisterMessageBusHandlersPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(
        string $environment,
        bool $debug,
        private readonly ?string $appId = null,
    ) {
        parent::__construct($environment, $debug);
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return \sprintf(
            '%s/var/cache/%s%s',
            $this->getProjectDir(),
            $this->environment,
            $this->appId ? '/'.$this->appId : '',
        );
    }

    public function getLogDir(): string
    {
        return \sprintf('%s/var/log/%s', $this->getProjectDir(), $this->appId ?? '');
    }

    public function registerBundles(): iterable
    {
        $configDir = $this->getProjectDir().'/config/bundles.php';
        $appConfig = \sprintf('%s/apps/%s/config/bundles.php', $this->getProjectDir(), $this->appId);

        $bundles = array_merge(
            require $configDir,
            ($this->appId && is_file($appConfig)) ? require $appConfig : [],
        );

        foreach ($bundles as $class => $envs) {
            if ($envs['all'] ?? $envs[$this->environment] ?? false) {
                /** @var BundleInterface $bundle */
                $bundle = new $class();

                yield $bundle;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        if ($this->appId) {
            $parameters['.kernel.config_dir'] = \sprintf('%s/apps/%s/config', $this->getProjectDir(), $this->appId);
        }

        return $parameters;
    }

    protected function build(ContainerBuilder $container): void
    {
        // Priority > 100: Must run before Symfony's ResolveInstanceofConditionalsPass /
        // AttributeAutoconfigurationPass (priority 100) to ensure our rules apply.
        $container->addCompilerPass(new RegisterMessageBusHandlersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 200);
        $container->addCompilerPass(new RegisterDoctrineSchemaConfiguratorsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 200);
        $container->addCompilerPass(new RegisterEnvVarProcessorsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 200);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->parameters()->set('kernel.app_id', $this->appId);

        $this->importConfigs($container, $this->getProjectDir().'/config');

        if ($this->appId) {
            $this->importConfigs($container, \sprintf('%s/apps/%s/config', $this->getProjectDir(), $this->appId));
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->importRoutes($routes, $this->getProjectDir().'/config');

        if ($this->appId) {
            $this->importRoutes($routes, \sprintf('%s/apps/%s/config', $this->getProjectDir(), $this->appId));
        }
    }

    private function importConfigs(ContainerConfigurator $container, string $dir): void
    {
        $container->import($dir.'/{packages}/*.php');
        $container->import($dir.'/{services}/*.php');

        if (is_file($dir.'/services.php')) {
            $container->import($dir.'/services.php');
        }
    }

    private function importRoutes(RoutingConfigurator $routes, string $dir): void
    {
        $routes->import($dir.'/{routes}/*.php');

        if (is_file($dir.'/routes.php')) {
            $routes->import($dir.'/routes.php');
        }
    }
}
