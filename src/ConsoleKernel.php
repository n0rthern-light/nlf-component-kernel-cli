<?php

namespace Nlf\Component\Kernel;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Dotenv\Dotenv;

class ConsoleKernel implements KernelInterface
{
    /** @var string[] */
    private array $envPaths;
    /** @var string[]  */
    private array $yamlPaths;
    private string $cliCommandTag;

    /**
     * @param string[] $envPaths
     * @param string[] $yamlPaths
     */
    public function __construct(array $envPaths, array $yamlPaths, string $cliCommandTag)
    {
        $this->envPaths = $envPaths;
        $this->yamlPaths = $yamlPaths;
        $this->cliCommandTag = $cliCommandTag;
    }

    public function boot(mixed ...$argv): void
    {
        $this->bootEnv();
        $container = $this->bootContainer();
        $this->bootApplication($container);
    }

    private function bootEnv(): void
    {
        $dotenv = new Dotenv();
        foreach($this->envPaths as $env) {
            $dotenv->load($env);
        }
    }

    private function bootContainer(): ContainerInterface
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        foreach($this->yamlPaths as $yaml) {
            $loader->load($yaml);
        }
        $container->compile(true);

        return $container;
    }

    private function bootApplication(ContainerInterface $container): void
    {
        $application = new Application();
        /** @var string $commandId */
        foreach ($container->findTaggedServiceIds($this->cliCommandTag) as $commandId => $args)
        {
            /** @var Command $command */
            $command = $container->get($commandId);
            $application->add($command);
        }
        $application->run(new ArgvInput());
    }
}