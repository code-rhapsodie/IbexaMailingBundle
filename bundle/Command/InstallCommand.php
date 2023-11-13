<?php



declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('ibexamailing:install')
            ->setDescription('Install what necessary in the DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Update the Database with Custom Ibexa Mailing Table');
        $command = $this->getApplication()->find('doctrine:schema:update');
        $arguments = [
            'command' => 'doctrine:schema:update',
            '--dump-sql' => true,
            '--force' => true,
        ];
        $arrayInput = new ArrayInput($arguments);
        $command->run($arrayInput, $output);

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
