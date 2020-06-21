<?php declare(strict_types=1);

namespace App\Console;

use App\AppKernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Run
 * @package App\Console
 */
class Run extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'server:run';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->addOption('host', 'bind to host this app instance', InputOption::VALUE_OPTIONAL)
            ->addOption('port', 'bind port on host', InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->hasOption('host') ? $input->getOption('host') : 'localhost';
        $port = $input->hasOption('port') ? $input->getOption('port') : '8080';
        $output->writeln('<info>Initialize kernel</info>');
        $react = new AppKernel($host, $port);
        $output->writeln('<info>Server Initialize successfully</info>');
        $output->writeln(sprintf('<info>Listen on %s:%s</info>', $host, $port));
        $react->run();
    }
}
