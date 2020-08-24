<?php

declare(strict_types=1);

namespace PK\Config\Console;

use PK\Config\DependencyInjection\ContainerFactoryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication
{
    /**
     * @var bool
     */
    private $commandsRegistered = false;

    /**
     * @var ContainerFactoryInterface
     */
    protected $containerFactory;

    public function __construct(ContainerFactoryInterface $containerFactory)
    {
        $this->containerFactory = $containerFactory;

        parent::__construct();

        $inputDefinition = $this->getDefinition();
        $inputDefinition->addOption(
            new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'YAML configuration file path.')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find($name): Command
    {
        $this->registerCommands();

        return parent::find($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name): Command
    {
        $this->registerCommands();

        return parent::get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function all($namespace = null): array
    {
        $this->registerCommands();

        return parent::all($namespace);
    }

    protected function getCommandName(InputInterface $input): ?string
    {
        $this->registerCommands($input);

        return parent::getCommandName($input);
    }

    private function registerCommands(?InputInterface $input = null): void
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $configuration = $input ? $input->getOption('configuration') : null;
        $container = $this->containerFactory->create($configuration);
        $commandLoader = $container->get('console.command_loader');

        if (!$commandLoader instanceof CommandLoaderInterface) {
            throw new InvalidConfigurationException(sprintf(
                'Command loader should be instance of %s. %s given.',
                CommandLoaderInterface::class,
                get_debug_type($commandLoader)
            ));
        }

        $this->setCommandLoader($commandLoader);
    }
}
