<?php



declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Command;

use CodeRhapsodie\IbexaMailingBundle\Core\Processor\TestMailingProcessorInterface as TestMailing;
use CodeRhapsodie\IbexaMailingBundle\Entity\Mailing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendTestMailingCommand extends Command
{
    /**
     * @var TestMailing
     */
    private $processor;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(TestMailing $processor, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->processor = $processor;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('ibexamailing:test:send:mailing')
            ->setDescription('Send a mailing to an specific email')
            ->setHidden(true)
            ->addArgument('mailingId', InputArgument::REQUIRED, 'The Mailing Id')
            ->addArgument('recipient', InputArgument::REQUIRED, "The recipient's email address");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mailingId = (int) $input->getArgument('mailingId');
        $recipientEmail = $input->getArgument('recipient');
        $io->title('Sending a Mailing for test');
        $io->writeln("Mailing ID: <comment>{$mailingId}</comment>");
        $io->writeln("To: <comment>{$recipientEmail}</comment>");
        $mailing = $this->entityManager->getRepository(Mailing::class)->findOneById($mailingId);
        $this->processor->execute($mailing, $recipientEmail);
        $io->success('Done.');

        return Command::SUCCESS;
    }
}
