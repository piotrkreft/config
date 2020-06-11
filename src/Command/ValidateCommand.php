<?php

declare(strict_types=1);

namespace PK\Config\Command;

use PK\Config\ConfigInterface;
use PK\Config\Exception\ExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateCommand extends Command
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ConfigInterface $config, ?string $name = null)
    {
        parent::__construct($name);
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Validates configuration entries.')
            ->addArgument('env', InputArgument::OPTIONAL, 'Environment for validation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$env = $input->getArgument('env')) {
            $output->writeln('<error>env argument missing</error>');

            return 1;
        }
        try {
            $invalid = $this->config->validate($env);
        } catch (ExceptionInterface $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return 2;
        }

        if (empty($invalid)) {
            $output->writeln('<info>Configuration valid</info>');

            return 0;
        }

        $output->writeln('<error>Following variables missing:</error>');
        foreach ($invalid as $variable) {
            $output->writeln($variable);
        }

        return 3;
    }
}
